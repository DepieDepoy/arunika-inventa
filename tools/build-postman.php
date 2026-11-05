<?php
/*
 * Membuat koleksi Postman dari route api/v1.
 * Jalankan: php tools/build-postman.php
 */
$root = $argv[1] ?? dirname(__DIR__);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$bodies = [
    'POST v1/login' => ['login' => 'admin@perusahaan.com', 'password' => 'password', 'device_name' => 'Postman', 'platform' => 'android'],
    'POST v1/forgot-password' => ['email' => 'admin@perusahaan.com'],
    'PUT v1/profile' => ['name' => 'Nama Baru', 'nik' => '1234567890', 'phone' => '081234567890'],
    'PUT v1/profile/password' => ['current_password' => 'password', 'password' => 'PasswordBaru#1', 'password_confirmation' => 'PasswordBaru#1', 'logout_other_devices' => false],
    'POST v1/devices' => ['fcm_token' => 'FCM_TOKEN', 'platform' => 'android', 'device_name' => 'Pixel 8', 'app_version' => '1.0.0'],
    'DELETE v1/devices' => ['fcm_token' => 'FCM_TOKEN'],
    'POST v1/logout' => ['fcm_token' => 'FCM_TOKEN'],
    'POST v1/calendar/events' => ['title' => 'Rapat Mingguan', 'event_type' => 'meeting', 'start_at' => '2026-11-10 09:00', 'end_at' => '2026-11-10 10:00', 'location' => 'Ruang Rapat', 'attendees' => [2, 3]],
    'PUT v1/calendar/events/{id}' => ['title' => 'Rapat Mingguan (ubah)', 'event_type' => 'meeting', 'start_at' => '2026-11-10 09:00', 'end_at' => '2026-11-10 11:00', 'attendees' => [2]],
    'POST v1/assets/{id}/assign' => ['user_id' => 2, 'reason' => 'Serah terima'],
    'POST v1/assets/{id}/return' => ['reason' => 'Resign'],
    'POST v1/assets/{assetId}/maintenance-request' => ['description' => 'AC kurang dingin sejak kemarin'],
    'POST v1/assets/{assetId}/repair-request' => ['description' => 'Layar laptop berkedip'],
    'POST v1/assets/{assetId}/requests' => ['request_type' => 'problem', 'description' => 'Laptop tidak bisa menyala'],
    'PUT v1/maintenance-requests/{id}' => ['request_type' => 'repair', 'description' => 'Laptop tidak bisa menyala, sudah dicoba charger lain'],
    'POST v1/users' => ['name' => 'Budi Teknisi', 'nik' => '3201000000000001', 'email' => 'budi@perusahaan.com', 'phone' => '081234500001', 'role_id' => 2, 'status' => 1, 'send_verification_email' => true],
    'PUT v1/users/{id}' => ['name' => 'Budi Teknisi', 'nik' => '3201000000000001', 'email' => 'budi@perusahaan.com', 'phone' => '081234500001', 'role_id' => 2, 'status' => 1],
    'POST v1/roles' => ['role_name' => 'Teknisi', 'status' => 1],
    'PUT v1/roles/{id}' => ['role_name' => 'Teknisi Lapangan', 'status' => 1],
    'PUT v1/roles/{id}/permissions' => ['permission_ids' => [1, 2, 3]],
    'POST v1/categories' => ['category_name' => 'Kendaraan', 'status' => 1, 'description' => 'Mobil & motor operasional'],
    'PUT v1/categories/{id}' => ['category_name' => 'Kendaraan', 'status' => 1, 'description' => 'Mobil & motor operasional'],
    'POST v1/categories/{id}/sub-categories' => ['sub_category_name' => 'Motor', 'status' => 1],
    'PUT v1/sub-categories/{id}' => ['sub_category_name' => 'Motor Listrik', 'status' => 1],
    'POST v1/vendors' => ['vendor_name' => 'CV Servis Jaya', 'address' => 'Jl. Merdeka 10, Bandung', 'email' => 'servis@jaya.co.id', 'phone' => '0221234567', 'pic_name' => 'Andi', 'status' => 1],
    'PUT v1/vendors/{id}' => ['vendor_name' => 'CV Servis Jaya', 'address' => 'Jl. Merdeka 10, Bandung', 'email' => 'servis@jaya.co.id', 'phone' => '0221234567', 'pic_name' => 'Andi', 'status' => 1],
    'POST v1/ai/ask' => ['question' => 'Aset apa saja yang maintenance-nya terlambat?'],
    'POST v1/ai/confirm' => ['token' => 'TOKEN_64_KARAKTER_DARI_RESPONS_ASK'],
];

$multipart = [
    'POST v1/assets' => [
        ['key' => 'asset_name', 'value' => 'Laptop Lenovo ThinkPad'],
        ['key' => 'category_id', 'value' => '1'],
        ['key' => 'vendor_id', 'value' => '1'],
        ['key' => 'purchase_price', 'value' => '15000000'],
        ['key' => 'asset_condition', 'value' => 'new'],
        ['key' => 'location', 'value' => 'Lantai 2'],
        ['key' => 'status', 'value' => '1'],
        ['key' => 'maintenance_required', 'value' => '0'],
        ['key' => 'asset_photos[]', 'type' => 'file', 'src' => []],
    ],
    'PUT v1/assets/{id}' => [
        ['key' => '_method', 'value' => 'PUT'],
        ['key' => 'asset_name', 'value' => 'Laptop Lenovo ThinkPad'],
        ['key' => 'category_id', 'value' => '1'],
        ['key' => 'vendor_id', 'value' => '1'],
        ['key' => 'status', 'value' => '1'],
        ['key' => 'delete_images[]', 'value' => ''],
        ['key' => 'asset_photos[]', 'type' => 'file', 'src' => []],
    ],
    'POST v1/maintenance/team/requests/{id}/progress' => [
        ['key' => 'note', 'value' => 'Sedang dicek teknisi'],
        ['key' => 'photos[]', 'type' => 'file', 'src' => []],
    ],
    'POST v1/maintenance/team/requests/{id}/complete' => [
        ['key' => 'action_taken', 'value' => 'Ganti freon dan bersihkan filter'],
        ['key' => 'maintenance_date', 'value' => '2026-11-05'],
        ['key' => 'cost', 'value' => '350000'],
        ['key' => 'after_photos[]', 'type' => 'file', 'src' => []],
    ],
];

$folders = [
    'Auth' => ['login', 'logout', 'me', 'forgot-password', 'app-info', 'test'],
    'Profil & Perangkat' => ['profile', 'devices'],
    'Notifikasi' => ['notifications'],
    'Beranda' => ['dashboard'],
    'Aset' => ['assets', 'my-assets'],
    'Maintenance' => ['maintenances', 'maintenance-requests', 'maintenance'],
    'Kalender' => ['calendar'],
    'Lookups' => ['lookups'],
    'Master Data' => ['categories', 'sub-categories', 'vendors'],
    'User, Role & Admin' => ['users', 'roles', 'admin'],
    'Vasetra AI' => ['ai'],
];

$items = [];
foreach (app('router')->getRoutes() as $route) {
    $uri = $route->uri();
    if (!str_starts_with($uri, 'api/v1/')) continue;
    $path = substr($uri, 4); // v1/...
    $first = explode('/', substr($path, 3))[0];

    foreach ($route->methods() as $method) {
        if ($method === 'HEAD') continue;
        $key = "$method $path";
        // Request multipart PUT dikirim sebagai POST + _method.
        $sendMethod = isset($multipart[$key]) && $method === 'PUT' ? 'POST' : $method;

        $pmPath = preg_replace('/\{(\w+)\}/', ':$1', substr($path, 3));
        $vars = [];
        preg_match_all('/\{(\w+)\}/', $path, $m);
        foreach ($m[1] as $v) $vars[] = ['key' => $v, 'value' => '1'];

        $public = !collect($route->gatherMiddleware())->contains(fn ($mw) => str_starts_with((string) $mw, 'auth:'));

        $request = [
            'method' => $sendMethod,
            'header' => [['key' => 'Accept', 'value' => 'application/json']],
            'url' => [
                'raw' => '{{base_url}}/' . $pmPath,
                'host' => ['{{base_url}}'],
                'path' => explode('/', $pmPath),
                'variable' => $vars,
            ],
        ];
        if ($public) {
            $request['auth'] = ['type' => 'noauth'];
        }
        if (isset($bodies[$key])) {
            $request['header'][] = ['key' => 'Content-Type', 'value' => 'application/json'];
            $request['body'] = ['mode' => 'raw', 'raw' => json_encode($bodies[$key], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)];
        } elseif (isset($multipart[$key])) {
            $request['body'] = ['mode' => 'formdata', 'formdata' => array_map(fn ($f) => $f + ['type' => 'text'], $multipart[$key])];
        }

        $item = ['name' => "$method /" . substr($path, 3), 'request' => $request];

        if ($key === 'POST v1/login') {
            $item['event'] = [[
                'listen' => 'test',
                'script' => ['type' => 'text/javascript', 'exec' => [
                    'const j = pm.response.json();',
                    'if (j.success && j.data && j.data.token) { pm.collectionVariables.set("token", j.data.token); }',
                ]],
            ]];
        }

        $folder = 'Lainnya';
        foreach ($folders as $name => $prefixes) {
            if (in_array($first, $prefixes, true)) { $folder = $name; break; }
        }
        // Request maintenance yang berawal /assets/... tetap dikelompokkan ke Maintenance.
        if (str_contains($path, "request")) { $folder = "Maintenance"; }
        $items[$folder][] = $item;
    }
}

$ordered = [];
foreach (array_merge(array_keys($folders), ['Lainnya']) as $name) {
    if (!empty($items[$name])) {
        usort($items[$name], fn ($a, $b) => strcmp($a['name'], $b['name']));
        $ordered[] = ['name' => $name, 'item' => $items[$name]];
    }
}

$collection = [
    'info' => [
        'name' => 'Vasetra Mobile API v1',
        'description' => "Lihat docs/api/MOBILE_API.md.\n\n1. Atur variabel base_url.\n2. Jalankan Auth › POST /login — token tersimpan otomatis.",
        'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
    ],
    'auth' => ['type' => 'bearer', 'bearer' => [['key' => 'token', 'value' => '{{token}}', 'type' => 'string']]],
    'variable' => [
        ['key' => 'base_url', 'value' => 'http://localhost/arunika-inventa/public/api/v1'],
        ['key' => 'token', 'value' => ''],
    ],
    'item' => $ordered,
];

$out = $root . '/docs/api/Vasetra-Mobile-API.postman_collection.json';
file_put_contents($out, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
$count = array_sum(array_map(fn ($f) => count($f['item']), $ordered));
echo "Ditulis: $out ($count request)\n";
foreach ($ordered as $f) echo "  {$f['name']}: " . count($f['item']) . "\n";
