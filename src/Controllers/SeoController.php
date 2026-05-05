<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;

final class SeoController extends Controller
{
    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\n";
        echo "Disallow: /me\n";
        echo "Disallow: /me/\n";
        echo "Disallow: /reservations/\n";
        echo "Disallow: /operator/\n";
        echo "Disallow: /admin/\n";
        echo "Disallow: /api/\n";
        echo "Disallow: /auth/\n";
        echo "Disallow: /login\n";
        echo "Disallow: /register\n";
        echo "Allow: /\n\n";
        echo "Sitemap: https://bad.mvc.kr/sitemap.xml\n";
        exit;
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        $venues = Db::fetchAll('SELECT id, updated_at FROM venues WHERE status = "active" ORDER BY id');
        $now = date('Y-m-d');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $urls = [
            ['/',        '1.0',  'daily'],
            ['/venues',  '0.9',  'daily'],
            ['/search',  '0.7',  'weekly'],
            ['/login',   '0.4',  'monthly'],
            ['/register','0.4',  'monthly'],
        ];
        foreach ($urls as [$loc, $pri, $freq]) {
            echo "  <url>\n";
            echo "    <loc>https://bad.mvc.kr$loc</loc>\n";
            echo "    <changefreq>$freq</changefreq>\n";
            echo "    <priority>$pri</priority>\n";
            echo "    <lastmod>$now</lastmod>\n";
            echo "  </url>\n";
        }
        foreach ($venues as $v) {
            $lastmod = substr((string) $v['updated_at'], 0, 10);
            echo "  <url>\n";
            echo "    <loc>https://bad.mvc.kr/venues/{$v['id']}</loc>\n";
            echo "    <changefreq>daily</changefreq>\n";
            echo "    <priority>0.8</priority>\n";
            echo "    <lastmod>$lastmod</lastmod>\n";
            echo "  </url>\n";
        }
        echo '</urlset>' . "\n";
        exit;
    }
}
