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

// Keep the on-disk sitemap.xml in sync when this endpoint is hit.
sitemapbored_generate_file();

header('Content-Type: application/xml; charset=utf-8');
echo sitemapbored_render();
