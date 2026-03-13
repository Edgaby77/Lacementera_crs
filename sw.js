// sw.js — Service Worker La Antigua Cementera
const CACHE = 'cementera-v1';

// Archivos estáticos que se cachean para carga rápida
const STATIC = [
  '/index.html',
  '/fondo.png',
  '/crs.png',
  '/manifest.json',
  'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;500;600&family=Barlow+Condensed:wght@400;600;700&display=swap'
];

// Instalar: guarda archivos estáticos en caché
self.addEventListener('install', e => {
  e.waitUntil(
    caches.open(CACHE).then(cache => cache.addAll(STATIC))
  );
  self.skipWaiting();
});

// Activar: limpia cachés viejos
self.addEventListener('activate', e => {
  e.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

// Fetch: network-first para PHP, cache-first para estáticos
self.addEventListener('fetch', e => {
  const url = new URL(e.request.url);

  // POST (guardar.php, hora de salida) → siempre red, nunca caché
  if (e.request.method === 'POST') {
    e.respondWith(fetch(e.request));
    return;
  }

  // PHP → network first (datos siempre frescos)
  if (url.pathname.endsWith('.php')) {
    e.respondWith(
      fetch(e.request).catch(() => caches.match(e.request))
    );
    return;
  }

  // Estáticos (imágenes, fuentes, HTML) → cache first
  e.respondWith(
    caches.match(e.request).then(cached => {
      if (cached) return cached;
      return fetch(e.request).then(response => {
        const clone = response.clone();
        caches.open(CACHE).then(cache => cache.put(e.request, clone));
        return response;
      });
    })
  );
});