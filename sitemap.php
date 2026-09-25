<?php
/**
 * sitemap.php — public XML sitemap endpoint + keeps the physical
 * /sitemap.xml file at the forum root in sync.
 *
 * Reachable at: /plugins/sitemapbored/sitemap.php
 */

require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/setup.php';
require_once __DIR__ . '/lib/sitemapbored_core.php';

// Regenerate the on-disk sitemap.xml at most once per hour. Generating on every
// anonymous request would let anyone force unbounded disk writes.
$path = sitemapbored_file_path();
if (!is_file($path) || (time() - (int)@filemtime($path)) > 3600) {
    sitemapbored_generate_file();
}

header('Content-Type: application/xml; charset=utf-8');
if (is_file($path) && is_readable($path)) {
    readfile($path);
} else {
    echo sitemapbored_render();
}
