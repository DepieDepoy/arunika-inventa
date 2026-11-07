/*
 * Service worker notifikasi browser Vasetra (Web Push).
 * Menerima push dari server lalu menampilkannya walau tab Vasetra tertutup.
 */
self.addEventListener('install', function () { self.skipWaiting(); });
self.addEventListener('activate', function (event) { event.waitUntil(self.clients.claim()); });

self.addEventListener('push', function (event) {
    var data = {};
    try { data = event.data ? event.data.json() : {}; } catch (e) { data = { title: 'Vasetra', body: event.data ? event.data.text() : '' }; }

    var title = data.title || 'Vasetra';
    var options = {
        body: data.body || '',
        icon: data.icon || '/assets/images/brand/vasetra-mark.png',
        badge: '/assets/images/brand/vasetra-mark.png',
        tag: data.id || data.type || undefined,
        renotify: !!data.id,
        data: { url: data.open_url || data.url || '/dashboard/home' },
    };

    // Beri tahu tab Vasetra yang terbuka agar badge lonceng langsung diperbarui.
    var informTabs = self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (list) {
        list.forEach(function (c) { c.postMessage({ type: 'vasetra-push' }); });
    });

    event.waitUntil(Promise.all([self.registration.showNotification(title, options), informTabs]));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();
    var target = (event.notification.data && event.notification.data.url) || '/dashboard/home';

    // Hanya buka alamat di domain Vasetra sendiri.
    try {
        var u = new URL(target, self.location.origin);
        if (u.origin !== self.location.origin) target = '/dashboard/home';
        else target = u.href;
    } catch (e) { target = '/dashboard/home'; }

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (list) {
            for (var i = 0; i < list.length; i++) {
                if (list[i].url.indexOf(self.location.origin) === 0 && 'focus' in list[i]) {
                    list[i].navigate(target);
                    return list[i].focus();
                }
            }
            return self.clients.openWindow(target);
        })
    );
});
