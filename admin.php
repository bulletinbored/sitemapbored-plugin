<?php
/**
 * admin.php — sitemapbored settings.
 *
 * Reachable at: /plugins/sitemapbored/admin.php  (admin only)
 */

require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/setup.php';
require_once __DIR__ . '/lib/sitemapbored_core.php';

if (!function_exists('is_admin') || !is_admin()) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

function sitemapbored_admin_save(array $post): array {
    global $config;

    $config['sitemapbored_include_home']       = !empty($post['include_home']) ? 1 : 0;
    $config['sitemapbored_include_categories'] = !empty($post['include_categories']) ? 1 : 0;
    $config['sitemapbored_include_threads']    = !empty($post['include_threads']) ? 1 : 0;

    $path = __DIR__ . '/../../config.json';
    if (file_exists($path)) {
        file_put_contents($path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    return ['ok' => true, 'msg' => 'Settings saved'];
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$root = dirname(__DIR__, 2); // forum root (parent of plugins/)
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\');
$forumBase = '';
if ($docRoot !== '' && strncasecmp($root, $docRoot, strlen($docRoot)) === 0) {
    $forumBase = str_replace('\\', '/', substr($root, strlen($docRoot)));
}
$base = $scheme . '://' . $host . $forumBase;
$sitemapUrl = $base . '/sitemap.xml';

$msg = '';
$msgOk = false;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!function_exists('validate_csrf_token') || !validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $msg = 'Invalid CSRF token';
        $msgOk = false;
    } elseif (isset($_POST['save'])) {
        $r = sitemapbored_admin_save($_POST);
        $msg = $r['msg'];
        $msgOk = $r['ok'];
    } elseif (isset($_POST['generate'])) {
        if (sitemapbored_generate_file()) {
            $msg = 'sitemap.xml generated at the site root';
            $msgOk = true;
        } else {
            $msg = 'Could not write sitemap.xml (check write permissions on the site root)';
            $msgOk = false;
        }
    }
}

$csrf = function_exists('generate_csrf_token') ? generate_csrf_token() : '';
$nonce = $GLOBALS['CSP_NONCE'] ?? '';
$pluginUrl = $base . '/plugins/sitemapbored';

function sb_checked($v) { return !empty($v) ? 'checked' : ''; }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>sitemapbored — Settings</title>
    <link href="<?php echo htmlspecialchars($pluginUrl, ENT_QUOTES, 'UTF-8'); ?>/assets/css/sitemapbored.css" rel="stylesheet">
</head>
<body class="sb-admin">
<div class="sb-wrap">
    <h1>sitemapbored</h1>
    <p class="sb-sub">XML sitemap generation</p>

    <?php if ($msg !== ''): ?>
        <div class="sb-alert <?php echo $msgOk ? 'sb-ok' : 'sb-err'; ?>"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="sb-card">
        <h2>Sitemap</h2>
        <p>Your sitemap is served at:</p>
        <p><a href="<?php echo htmlspecialchars($sitemapUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($sitemapUrl, ENT_QUOTES, 'UTF-8'); ?></a></p>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" name="generate" class="sb-btn">Generate sitemap.xml</button>
        </form>
        <p class="sb-hint">Add this URL to your <code>robots.txt</code> as <code>Sitemap: <?php echo htmlspecialchars($sitemapUrl, ENT_QUOTES, 'UTF-8'); ?></code></p>
    </div>

    <form method="post" class="sb-card">
        <h2>Content</h2>
        <label class="sb-check"><input type="checkbox" name="include_home" value="1" <?php echo sb_checked($config['sitemapbored_include_home'] ?? 1); ?>> Include homepage</label>
        <label class="sb-check"><input type="checkbox" name="include_categories" value="1" <?php echo sb_checked($config['sitemapbored_include_categories'] ?? 1); ?>> Include categories</label>
        <label class="sb-check"><input type="checkbox" name="include_threads" value="1" <?php echo sb_checked($config['sitemapbored_include_threads'] ?? 1); ?>> Include threads</label>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit" name="save" class="sb-btn">Save settings</button>
    </form>

    <p class="sb-back"><a href="<?php echo htmlspecialchars($base, ENT_QUOTES, 'UTF-8'); ?>/admin/plugins">&larr; Back to plugins</a></p>
</div>
</body>
</html>
