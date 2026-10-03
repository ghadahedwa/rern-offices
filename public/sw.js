/*
 * Service Worker تطبيق المنظومة.
 *
 * ⚠️ لا يخزّن أي صفحة ولا أي أصل: كل طلب يذهب للسيرفر كما هو، فلا يرى المستخدم نسخةً قديمة أبداً.
 * الشيء الوحيد المخزَّن صفحة «لا يوجد اتصال»، تُعرض حين يفشل فتح صفحة لانقطاع الشبكة.
 *
 * ⚠️ لا يمسّ إلا تنقّل GET (فتح صفحة). طلبات Livewire (fetch/POST) والتنزيلات والنماذج تمرّ بلا تدخّل.
 *
 * مفتاح الإيقاف: لو لزم تعطيله من كل الأجهزة، استبدل هذا الملف بـ:
 *   self.addEventListener('install', () => self.skipWaiting());
 *   self.addEventListener('activate', () => self.registration.unregister());
 */
const CACHE = 'rern-offline-v1';
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE)
            .then((cache) => cache.addAll([OFFLINE_URL, '/icons/icon-192.png']))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.mode !== 'navigate' || request.method !== 'GET') {
        return;
    }

    event.respondWith(
        fetch(request).catch(() => caches.match(OFFLINE_URL))
    );
});
