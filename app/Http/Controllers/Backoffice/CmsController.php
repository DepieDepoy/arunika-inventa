<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\SiteContent;
use App\Services\Landing\LandingContent;
use App\Services\Backoffice\BackofficeAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * CMS landing page — khusus pegawai Vasetra (backoffice).
 *
 * Form dibangun dari LandingContent::schema(), jadi menambah field baru cukup
 * di satu tempat (schema + defaults) tanpa mengubah controller ini.
 */
class CmsController extends Controller
{
    public function __construct(protected LandingContent $content)
    {
    }

    public function index(): View
    {
        $rows = SiteContent::query()->with('editor:id,name')->get()->groupBy('locale')->map(fn ($g) => $g->keyBy('key'));

        $sections = collect($this->content->schema())->map(function ($s, $key) use ($rows) {
            $status = [];
            foreach (array_keys(LandingContent::LOCALES) as $locale) {
                $row = $rows->get($locale)?->get($key);
                $status[$locale] = [
                    'customized' => (bool) $row,
                    'updated_at' => $row?->updated_at,
                    'editor' => $row?->editor?->name,
                ];
            }

            $latest = collect($status)->filter(fn ($x) => $x['updated_at'])->sortByDesc('updated_at')->first();

            return $s + [
                'key' => $key,
                'status' => $status,
                // Versi Inggris tertinggal: Indonesia sudah diubah lebih baru dari Inggris.
                'en_outdated' => $status['id']['customized']
                    && (!$status['en']['customized'] || $status['en']['updated_at']->lt($status['id']['updated_at'])),
                'updated_at' => $latest['updated_at'] ?? null,
                'editor' => $latest['editor'] ?? null,
            ];
        });

        $trialDays = (int) (Plan::query()->where('plan_code', 'FREE')->value('trial_days') ?: 7);

        return view('backoffice.cms.index', compact('sections', 'trialDays'));
    }

    public function edit(Request $request, string $key): View
    {
        $schema = $this->schemaFor($key);
        $locale = LandingContent::normalizeLocale($request->query('lang'));

        return view('backoffice.cms.edit', [
            'key' => $key,
            'locale' => $locale,
            'locales' => LandingContent::LOCALES,
            'section' => $schema,
            'value' => $this->content->section($key, $locale),
            'customized' => $this->content->isCustomized($key, $locale),
            'customizedOther' => collect(LandingContent::LOCALES)->keys()->reject(fn ($l) => $l === $locale)
                ->mapWithKeys(fn ($l) => [$l => $this->content->isCustomized($key, $l)])->all(),
            'icons' => LandingContent::ICON_SUGGESTIONS,
        ]);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $schema = $this->schemaFor($key);
        $locale = LandingContent::normalizeLocale($request->input('locale'));
        $isBase = $locale === LandingContent::DEFAULT_LOCALE;
        $current = $this->content->section($key, $locale);
        $input = (array) $request->input('content', []);

        $value = $current;
        $errors = [];

        foreach ($schema['fields'] as $name => $field) {
            if ($field['type'] === 'image') {
                // Gambar dipakai bersama semua bahasa → hanya disimpan di bahasa utama.
                if ($isBase) {
                    $value[$name] = $this->handleImage($request, $key, $name, $current[$name] ?? null, $errors);
                } else {
                    unset($value[$name]);
                }
                continue;
            }

            $value[$name] = $this->clean($field, $input[$name] ?? null);
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $before = SiteContent::query()->where('key', $key)->where('locale', $locale)->value('value');

        SiteContent::query()->updateOrCreate(
            ['key' => $key, 'locale' => $locale],
            ['value' => $value, 'updated_by' => auth('backoffice')->id()]
        );

        $this->content->forget();

        BackofficeAudit::log(
            'cms_updated',
            'Mengubah konten landing (' . strtoupper($locale) . '): ' . $schema['label'],
            is_array($before) ? $before : null,
            $value,
        );

        return redirect()
            ->route('backoffice.cms.edit', ['key' => $key, 'lang' => $locale])
            ->with('success', 'Konten "' . $schema['label'] . '" (' . LandingContent::LOCALES[$locale] . ') berhasil disimpan dan langsung tampil di landing page.');
    }

    /**
     * Kembalikan bagian ke teks bawaan untuk satu bahasa.
     * Gambar hanya ikut dihapus saat bahasa utama yang di-reset.
     */
    public function reset(Request $request, string $key): RedirectResponse
    {
        $schema = $this->schemaFor($key);
        $locale = LandingContent::normalizeLocale($request->input('locale'));
        $row = SiteContent::query()->where('key', $key)->where('locale', $locale)->first();

        if ($row) {
            if ($locale === LandingContent::DEFAULT_LOCALE) {
                foreach ($schema['fields'] as $name => $field) {
                    if ($field['type'] === 'image') {
                        $this->deleteImage($row->value[$name] ?? null);
                    }
                }
            }

            $row->delete();
            $this->content->forget();

            BackofficeAudit::log('cms_reset', 'Mengembalikan konten landing (' . strtoupper($locale) . ') ke bawaan: ' . $schema['label'], $row->value);
        }

        return redirect()
            ->route('backoffice.cms.edit', ['key' => $key, 'lang' => $locale])
            ->with('success', 'Konten "' . $schema['label'] . '" (' . LandingContent::LOCALES[$locale] . ') dikembalikan ke teks bawaan.');
    }

    protected function schemaFor(string $key): array
    {
        $schema = $this->content->schema()[$key] ?? null;
        abort_if(!$schema, 404);

        return $schema;
    }

    /**
     * Normalisasi satu nilai sesuai tipe field.
     */
    protected function clean(array $field, mixed $raw): mixed
    {
        return match ($field['type']) {
            'text' => $this->str($raw, 300),
            'textarea' => $this->str($raw, 3000),
            'icon' => is_string($raw) && preg_match('/^bi-[a-z0-9-]{1,60}$/', trim($raw)) ? trim($raw) : 'bi-check2-circle',
            'lines' => collect(preg_split('/\r\n|\r|\n/', (string) $raw))
                ->map(fn ($l) => $this->str($l, 300))
                ->filter()
                ->take($field['max'] ?? 20)
                ->values()
                ->all(),
            'list' => collect(is_array($raw) ? $raw : [])
                ->map(function ($row) use ($field) {
                    $row = is_array($row) ? $row : [];
                    $item = [];
                    foreach ($field['fields'] as $sub => $subField) {
                        $item[$sub] = $this->clean($subField, $row[$sub] ?? null);
                    }

                    return $item;
                })
                ->filter(fn ($item) => collect($item)->except('icon')->filter(fn ($v) => $v !== '' && $v !== [])->isNotEmpty())
                ->take($field['max'] ?? 20)
                ->values()
                ->all(),
            default => null,
        };
    }

    protected function str(mixed $raw, int $max): string
    {
        return mb_substr(trim(strip_tags((string) $raw)), 0, $max);
    }

    protected function handleImage(Request $request, string $section, string $name, ?string $currentPath, array &$errors): ?string
    {
        if ($request->boolean("remove_images.$name")) {
            $this->deleteImage($currentPath);
            $currentPath = null;
        }

        /** @var UploadedFile|null $file */
        $file = $request->file("images.$name");

        if (!$file) {
            return $currentPath;
        }

        $validator = validator(
            ['file' => $file],
            ['file' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048']],
            ['file.max' => 'Ukuran gambar maksimal 2 MB.', 'file.mimes' => 'Format gambar harus JPG, PNG, WEBP, atau GIF.', 'file.image' => 'File harus berupa gambar.']
        );

        if ($validator->fails()) {
            $errors["images.$name"] = $validator->errors()->get('file');

            return $currentPath;
        }

        $path = $file->store("site/$section", 'public');
        $this->deleteImage($currentPath);

        return $path;
    }

    protected function deleteImage(?string $path): void
    {
        // Hanya file yang memang diunggah lewat CMS (folder site/).
        if ($path && str_starts_with($path, 'site/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
