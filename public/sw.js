importScripts('config.js');

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(cacheName + cacheVersion).then((cache) => {
      return cache.addAll(contentToCache);
    })
  );
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(thisCacheName => {
          if (thisCacheName !== cacheName + cacheVersion) {
            return caches.delete(thisCacheName);
          }
        })
      );
    })
  )
});


self.addEventListener('fetch', (e) => {
  e.respondWith(
    caches.match(e.request).then((r) => {
      return r || fetch(e.request).then((response) => {
        return caches.open(cacheName+cacheVersion).then((cache) => {
          if ((e.request.method == 'GET') && (e.request.mode === 'navigate')) {
            if(response.headers.get('Content-Type') && (!response.headers.get('Content-Type').startsWith('text/html'))){
              cache.put(e.request, response.clone());
            }
          }
          return response;
        });
      });
    })
  );
});
