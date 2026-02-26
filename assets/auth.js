/**
 * auth.js — Supabase Auth client wrapper
 *
 * Manages session tokens, syncs to cookie for PHP page loads,
 * and provides Auth.fetch() for authenticated API calls.
 *
 * Loaded AFTER supabase-js CDN script.
 * Config injected by PHP: window.__SUPABASE_URL__, window.__SUPABASE_ANON_KEY__
 */

(function(window) {
    'use strict';

    var SUPABASE_URL      = window.__SUPABASE_URL__      || '';
    var SUPABASE_ANON_KEY = window.__SUPABASE_ANON_KEY__ || '';

    if (!SUPABASE_URL || !SUPABASE_ANON_KEY) return;

    var createClient = supabase.createClient;
    var sb = createClient(SUPABASE_URL, SUPABASE_ANON_KEY, {
        auth: {
            flowType: 'pkce',
            autoRefreshToken: true,
            persistSession: true,
            detectSessionInUrl: true
        }
    });

    var currentToken = null;

    /**
     * Sync access token to a cookie so PHP can read it on page loads
     */
    function syncTokenCookie(token) {
        if (token) {
            document.cookie = 'sb-access-token=' + token + '; path=/; SameSite=Lax; Secure';
        } else {
            document.cookie = 'sb-access-token=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
        }
    }

    // Listen for auth state changes (sign in, sign out, token refresh)
    sb.auth.onAuthStateChange(function(event, session) {
        currentToken = session && session.access_token ? session.access_token : null;
        syncTokenCookie(currentToken);

        if (event === 'SIGNED_OUT') {
            window.location.href = '/auth/login.php';
        }
    });

    // Initialize token from existing session
    sb.auth.getSession().then(function(result) {
        var session = result.data && result.data.session ? result.data.session : null;
        currentToken = session ? session.access_token : null;
        syncTokenCookie(currentToken);
    });

    /**
     * Public Auth API — available as window.Auth
     */
    window.Auth = {
        getToken: function() {
            return currentToken;
        },

        getClient: function() {
            return sb;
        },

        signInWithEmail: function(email, password) {
            return sb.auth.signInWithPassword({ email: email, password: password });
        },

        signInWithGoogle: function(redirectTo) {
            return sb.auth.signInWithOAuth({
                provider: 'google',
                options: {
                    redirectTo: redirectTo || window.location.origin + '/auth/callback.php'
                }
            });
        },

        signInWithMagicLink: function(email, redirectTo) {
            return sb.auth.signInWithOtp({
                email: email,
                options: {
                    emailRedirectTo: redirectTo || window.location.origin + '/auth/callback.php'
                }
            });
        },

        signOut: function() {
            return sb.auth.signOut();
        },

        /**
         * Authenticated fetch wrapper. Attaches Bearer token.
         * Drop-in replacement for fetch() in API calls.
         */
        fetch: function(url, options) {
            options = options || {};
            var token = currentToken;
            if (!token) {
                window.location.href = '/auth/login.php';
                return Promise.reject(new Error('Not authenticated'));
            }
            var headers = options.headers || {};
            headers['Authorization'] = 'Bearer ' + token;
            options.headers = headers;
            return fetch(url, options);
        }
    };

    // Convenience alias
    window.authFetch = window.Auth.fetch;

})(window);
