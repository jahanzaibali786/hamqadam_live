<?php

namespace App\Http\Controllers;

use App\Services\LegalContentService;
use Illuminate\Http\Response;

final class LegalSitemapController extends Controller
{
    public function index(LegalContentService $cms): Response
    {
        $urls = ['/', '/about-us', '/our-team', '/privacy-policy',
            '/terms-conditions', '/refund-policy', '/help-center',
            '/blog', '/contact-us/page'];
        foreach ($cms->availableHelp() as $entry) {
            $urls[] = '/help-center/' . $entry['slug'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach (array_unique($urls) as $path) {
            $url = htmlspecialchars(url($path), ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $xml .= "  <url><loc>{$url}</loc></url>\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=900',
        ]);
    }
}
