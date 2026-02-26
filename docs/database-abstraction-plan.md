# Database Abstraction Plan: mysqli to PDO (MySQL + SQLite)

## Context

The application currently uses a `Database` singleton class backed by mysqli, hardcoded to MySQL. The goal is to support **both MySQL and SQLite** using PDO, while keeping the same simple public API so most files need zero changes. This enables local development without MySQL and embedded/lightweight deployments.

---

## Architecture: Single Class, PDO Internals

**One `Database.php` file. No interfaces, no abstract classes, no driver subclasses.**

PDO already abstracts 95% of MySQL/SQLite differences. The remaining 5% is handled by a `$this->driver` property and a few `if` branches in private methods. A class hierarchy for ~5 conditional lines would be over-engineering.

### What stays the same
- Singleton pattern (`getInstance()`)
- `global $db;` usage everywhere
- All public method signatures: `fetchOne()`, `fetchAll()`, `insert()`, `execute()`, `query()`, `beginTransaction()`, `commit()`, `rollback()`, `close()`
- The `$types` parameter (e.g., `"ssi"`) is **kept in all signatures but ignored** — PDO infers types. This means **zero changes** to the ~40 call sites that pass type strings.

### What changes internally
- `mysqli` replaced with `PDO`
- `$this->connection->insert_id` → `$this->connection->lastInsertId()`
- `$stmt->affected_rows` → `$stmt->rowCount()`
- `$stmt->get_result()->fetch_assoc()` → `$stmt->fetch(PDO::FETCH_ASSOC)`
- `$stmt->get_result()->fetch_all(MYSQLI_ASSOC)` → `$stmt->fetchAll(PDO::FETCH_ASSOC)`

### New methods added
- `getDriver()` — returns `'mysql'` or `'sqlite'`
- `tableExists($tableName)` — cross-platform table introspection (replaces `SHOW TABLES LIKE`)

---

## Phased Migration (3 phases, each independently deployable)

### Phase 1: Replace mysqli with PDO (MySQL only)

Rewrite `Database.php` internals from mysqli to PDO. No SQLite yet, no caller changes.

**Key notes:**
- `insert()` and `execute()` had `$types` and `$params` as **required** parameters. Now they have defaults (`""` and `[]`). All existing callers still pass them, so no breakage.
- `escape()` uses `PDO::quote()` and strips surrounding quotes for drop-in compat. This method is **never called** in the codebase.
- `getConnection()` now returns a PDO object. This method is **never called** in the codebase.

### Phase 2: Replace MySQL-specific SQL in caller code

**5 `NOW()` replacements** — use the existing `getCurrentTimestamp()` from `helpers.php:184` as a bound parameter instead:

| File | Before | After |
|------|--------|-------|
| `api.php` | `VALUES (?, ?, ?, ?, 1, NOW())` | `VALUES (?, ?, ?, ?, 1, ?)` + `getCurrentTimestamp()` |
| `api.php` | `updated_at = NOW()` | `updated_at = ?` + `getCurrentTimestamp()` |
| `api-versions.php` | `VALUES (?, ?, ?, ?, 0, NOW())` | `VALUES (?, ?, ?, ?, 0, ?)` + `getCurrentTimestamp()` |
| `api-versions.php` | `$updates[] = "updated_at = NOW()"` | `$updates[] = "updated_at = ?"` + timestamp param |
| `migrations/002-...` | `VALUES (?, ?, ?, ?, ?, NOW())` | `VALUES (?, ?, ?, ?, ?, ?)` + `getCurrentTimestamp()` |

**1 `SHOW TABLES` replacement:** → `$db->tableExists('qr_code_versions')`

**1 `ALTER TABLE` guard:** Wrapped in `if ($db->getDriver() !== 'sqlite')`

### Phase 3: Add SQLite support

1. **`config.example.php`** — Added `DB_DRIVER` and `DB_SQLITE_PATH` constants
2. **`database-sqlite.sql`** — SQLite-compatible schema (separate file)
3. **`.htaccess`** — Block access to `data/` directory and `database-sqlite.sql`
4. **`diagnostic.php`** — Check `pdo_mysql` or `pdo_sqlite` instead of `mysqli`
5. **`.gitignore`** — Added `data/*.db`, `data/*.db-wal`, `data/*.db-shm`

---

## Files That Need ZERO Changes

These files use standard SQL that works on both MySQL and SQLite:

- `index.php`, `edit.php`, `r.php`, `create.php`, `save-image.php`
- `includes/helpers.php`, `includes/version-helpers.php`
- `assets/app.js`, `assets/style.css`

---

## SQL Compatibility Notes

| Feature | MySQL | SQLite | Impact |
|---------|-------|--------|--------|
| `NOW()` | Native | Not supported | Replaced with PHP `getCurrentTimestamp()` |
| `AUTO_INCREMENT` | `INT AUTO_INCREMENT` | `INTEGER PRIMARY KEY AUTOINCREMENT` | Schema only |
| `JSON` column | Native type | Stored as TEXT | App uses json_encode/decode |
| `BOOLEAN` | Alias for TINYINT | Stores as INTEGER | App uses `= 1` / `= 0` |
| `ON UPDATE CURRENT_TIMESTAMP` | Native | Not supported | App sets `updated_at` explicitly |
| `SHOW TABLES LIKE` | Native | `sqlite_master` | New `tableExists()` method |
| `ALTER TABLE ADD FK` | Works | Not supported post-creation | Guarded in migration |
| Foreign keys | Default ON | Need PRAGMA | Handled in constructor |

---

## Risks and Mitigations

| Risk | Likelihood | Mitigation |
|------|-----------|------------|
| PDO not available | Very low (bundled since PHP 5.1) | Check in diagnostic.php |
| `lastInsertId()` returns string | N/A | Cast with `(int)` |
| SQLite write contention | Low (small-scale app) | WAL mode enabled |
| SQLite DB file exposed | Medium | `.htaccess` + `data/` dir |

---

## Summary of All File Changes

| File | Phase | Change |
|------|-------|--------|
| `includes/Database.php` | 1 | Rewrite: mysqli → PDO. Add `tableExists()`, `getDriver()`. Same public API. |
| `api.php` | 2 | Replace `NOW()` with `getCurrentTimestamp()` bound param |
| `api-versions.php` | 2 | Replace `NOW()` with `getCurrentTimestamp()` bound param |
| `migrations/002-...` | 2 | Replace `SHOW TABLES LIKE` → `tableExists()`. Replace `NOW()`. Guard `ALTER TABLE`. |
| `config.example.php` | 3 | Add `DB_DRIVER` and `DB_SQLITE_PATH` |
| `database-sqlite.sql` | 3 | New file — SQLite schema |
| `diagnostic.php` | 3 | Check `pdo_mysql`/`pdo_sqlite` instead of `mysqli` |
| `.htaccess` | 3 | Block `data/` directory + `database-sqlite.sql` |
| `.gitignore` | 3 | Add `data/*.db` |
