<?php
/**
 * Plugin Name: sitemapbored
 * Author: mlzog
 * Description: Generates an XML sitemap for the forum.
 * License: BSD Zero Clause License
 */

require_once __DIR__ . '/lib/sitemapbored_core.php';

// How often (seconds) the on-disk sitemap.xml may be stale before it is
// rebuilt automatically on a normal page request. Keeps the file fresh
// without depending on anyone visiting the endpoint.
if (!defined('SITEMAPBORED_REFRESH')) {
    define('SITEMAPBORED_REFRESH', 3600);
}

function sitemapbored_init() {
    global $pluginManager, $config;

    if (!isset($pluginManager)) {
        return;
    }

    $baseUrl = rtrim(base_url(), '/');
    $pluginUrl = $baseUrl . '/plugins/sitemapbored';
    $adminUrl = $pluginUrl . '/admin.php';

    // Expose a small admin entry point in the frontend head (admins only).
    $pluginManager->addHook('admin_before_render', function () use ($adminUrl) {
        if (!function_exists('is_admin') || !is_admin()) {
            return;
        }
        $nonce = $GLOBALS['CSP_NONCE'] ?? '';
        echo '<link href="' . htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8') . '/../assets/css/sitemapbored.css" rel="stylesheet">' . "\n";
    });

    // Autonomous, guaranteed refresh: build the file on first request after
    // install and keep it fresh over time. This runs on every normal forum
    // request (index.php calls loadEnabled()), so no core changes or custom
    // hooks are required.
    if (function_exists('sitemapbored_generate_file')) {
        $path = sitemapbored_file_path();
        $stale = !file_exists($path) || (time() - (int)@filemtime($path)) > SITEMAPBORED_REFRESH;
        if ($stale) {
            sitemapbored_generate_file();
        }
    }

    // Register a custom admin route so the settings page can be opened from
    // the standard /admin area via a known link.
    if (function_exists('is_admin') && is_admin()) {
        if (($GLOBALS['_GET']['action'] ?? '') === 'sitemapbored') {
            require_once __DIR__ . '/admin.php';
            exit;
        }
    }
}
