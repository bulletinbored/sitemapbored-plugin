<?php
/**
 * sitemapbored_core.php — shared sitemap generation logic.
 *
 * Loaded by both sitemap.php (public endpoint) and admin.php (settings/submit)
 * so the physical file and the dynamic output stay in sync.
 */

function sitemapbored_site_base(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $root = dirname(__DIR__, 3); // forum root: plugins/<plugin>/lib -> up 3
    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
    $sub = '';
    if ($docRoot !== '' && strncasecmp($root, $docRoot, strlen($docRoot)) === 0) {
        $sub = str_replace('\\', '/', substr($root, strlen($docRoot)));
    }
    return $scheme . '://' . $host . $sub;
}

function sitemapbored_build_urls(): array {
    global $pdo, $config;

    $urls = [];
    $opts = sitemapbored_options();
    $siteBase = sitemapbored_site_base();

    if (!empty($opts['include_home'])) {
        $urls[] = [
            'loc'        => $siteBase . '/',
            'lastmod'    => gmdate('Y-m-d\TH:i:s\Z'),
            'changefreq' => 'daily',
            'priority'   => '1.0',
        ];
    }

    if (!isset($pdo)) {
        return $urls;
    }

    if (!empty($opts['include_categories'])) {
        try {
            $stmt = $pdo->query("SELECT id, name, updated_at FROM categories ORDER BY id ASC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $slug = function_exists('slugify') ? slugify($row['name'] ?? '') : '';
                $urls[] = [
                    'loc'        => $siteBase . '/category/' . (int)$row['id'] . ($slug ? '-' . $slug : ''),
                    'lastmod'    => sitemapbored_lastmod($row['updated_at'] ?? null),
                    'changefreq' => 'weekly',
                    'priority'   => '0.6',
                ];
            }
        } catch (Throwable $e) {}
    }

    if (!empty($opts['include_threads'])) {
        try {
            $stmt = $pdo->query("
                SELECT t.id, t.title, t.updated_at, t.created_at
                FROM threads t
                WHERE t.status = 'visible'
                ORDER BY t.id ASC
            ");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $slug = function_exists('slugify') ? slugify($row['title'] ?? '') : '';
                $urls[] = [
                    'loc'        => $siteBase . '/thread/' . (int)$row['id'] . ($slug ? '-' . $slug : ''),
                    'lastmod'    => sitemapbored_lastmod($row['updated_at'] ?? $row['created_at'] ?? null),
                    'changefreq' => 'monthly',
                    'priority'   => '0.8',
                ];
            }
        } catch (Throwable $e) {}
    }

    return $urls;
}

function sitemapbored_lastmod($value): string {
    if (empty($value)) {
        return gmdate('Y-m-d\TH:i:s\Z');
    }
    $ts = is_numeric($value) ? (int)$value : @strtotime($value);
    if ($ts === false || $ts <= 0) {
        return gmdate('Y-m-d\TH:i:s\Z');
    }
    return gmdate('Y-m-d\TH:i:s\Z', $ts);
}

function sitemapbored_options(): array {
    global $config;
    return [
        'include_home'       => $config['sitemapbored_include_home'] ?? 1,
        'include_categories' => $config['sitemapbored_include_categories'] ?? 1,
        'include_threads'    => $config['sitemapbored_include_threads'] ?? 1,
    ];
}

function sitemapbored_render(): string {
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

    foreach (sitemapbored_build_urls() as $u) {
        $xml .= "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
        $xml .= "    <lastmod>" . htmlspecialchars($u['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</lastmod>\n";
        $xml .= "    <changefreq>" . htmlspecialchars($u['changefreq'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</changefreq>\n";
        $xml .= "    <priority>" . htmlspecialchars($u['priority'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</priority>\n";
        $xml .= "  </url>\n";
    }

    $xml .= "</urlset>\n";
    return $xml;
}

function sitemapbored_file_path(): string {
    return dirname(__DIR__, 3) . '/sitemap.xml'; // plugins/<plugin>/lib -> up 3 = forum root
}

function sitemapbored_generate_file(): bool {
    $path = sitemapbored_file_path();
    $dir = dirname($path);
    if (!is_dir($dir) || !is_writable($dir)) {
        return false;
    }
    return file_put_contents($path, sitemapbored_render()) !== false;
}
