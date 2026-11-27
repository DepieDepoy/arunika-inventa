<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * sitemap.xml untuk Google/Bing: hanya halaman publik yang layak muncul di pencarian.
 * Setiap halaman punya versi ID & EN, ditandai hreflang agar mesin pencari
 * menampilkan bahasa yang sesuai.
 */
class SitemapController extends Controller
{
    /** Pasangan [rute Indonesia, rute Inggris, prioritas]. */
    private const PAGES = [
        ['landing', 'landing.en', '1.0'],
        ['legal.privacy', 'legal.privacy.en', '0.3'],
        ['legal.terms', 'legal.terms.en', '0.3'],
    ];

    public function __invoke(): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n";

        foreach (self::PAGES as [$id, $en, $priority]) {
            $idUrl = route($id);
            $enUrl = route($en);

            foreach ([$idUrl, $enUrl] as $loc) {
                $xml .= "  <url>\n"
                    .'    <loc>'.e($loc)."</loc>\n"
                    .'    <xhtml:link rel="alternate" hreflang="id" href="'.e($idUrl).'"/>'."\n"
                    .'    <xhtml:link rel="alternate" hreflang="en" href="'.e($enUrl).'"/>'."\n"
                    .'    <xhtml:link rel="alternate" hreflang="x-default" href="'.e($idUrl).'"/>'."\n"
                    ."    <priority>{$priority}</priority>\n"
                    ."  </url>\n";
            }
        }

        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
