<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Services\Landing\LandingContent;
use App\Services\Landing\LegalContent;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

/**
 * Halaman Kebijakan Privasi & Syarat Layanan (dua bahasa, isi dari CMS backoffice).
 */
class LegalController extends Controller
{
    public function __invoke(LandingContent $landing, string $page, string $locale = 'id'): View
    {
        abort_unless(in_array($page, ['privacy', 'terms'], true), 404);

        $locale = LandingContent::normalizeLocale($locale);
        App::setLocale($locale);

        $content = $landing->section($page, $locale);

        return view('landing.legal', [
            'page' => $page,
            'locale' => $locale,
            'doc' => $content,
            'html' => LegalContent::render((string) ($content['body'] ?? '')),
            'footer' => $landing->section('footer', $locale),
        ]);
    }
}
