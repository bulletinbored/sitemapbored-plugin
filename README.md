# sitemapbored

Plugin for [bulletinbored](https://github.com/bulletinbored) that generates an
XML sitemap for the forum.

## Features

- Generates a physical `sitemap.xml` at the root of the site, reachable at
  `https://your-site/sitemap.xml`.
- Fully self-contained: **no changes to the forum core required**.
- Includes the homepage, all visible categories and all visible threads.
- Automatically created on the first request after enabling the plugin, and
  kept up to date over time (rebuilt when older than the refresh interval).

## Install

1. Copy the `sitemapbored` folder into `plugins/`.
2. Enable it from **Admin → Plugins**.

## Usage

The `sitemap.xml` file is generated and maintained automatically:

- On the first request after the plugin is enabled, the file is created.
- On every subsequent forum request, it is rebuilt if it is older than the
  refresh interval (default 1 hour; change `SITEMAPBORED_REFRESH` in
  `sitemapbored.php`).

This means the file at the site root always reflects the current content
without needing to visit any endpoint.

You can also (re)build it on demand from the admin page (**Generate
sitemap.xml**) or by visiting the dynamic endpoint:

```
https://your-site/plugins/sitemapbored/sitemap.php
```

Add the sitemap to your `robots.txt`:

```
Sitemap: https://your-site/sitemap.xml
```

## Notes

- Only threads with `status = 'visible'` are included.
- The forum root directory must be writable by the web server so the plugin
  can create `sitemap.xml`.
