<?php if (AuthMiddleware::isEnabled()): ?>
<script>
    window.__SUPABASE_URL__      = '<?php echo htmlspecialchars(SUPABASE_URL, ENT_QUOTES, "UTF-8"); ?>';
    window.__SUPABASE_ANON_KEY__ = '<?php echo htmlspecialchars(SUPABASE_ANON_KEY, ENT_QUOTES, "UTF-8"); ?>';
</script>
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script src="assets/auth.js"></script>
<?php endif; ?>
