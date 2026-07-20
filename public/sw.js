// Deliberately minimal — exists only to satisfy PWA installability criteria (Chrome requires a
// registered service worker with a fetch handler before it will fire beforeinstallprompt). No
// caching, no offline support: every request just passes straight through to the network. Adding
// a caching strategy is a separate, deliberate future feature, not bundled in here — a wrong
// caching strategy is a real way to strand users on stale assets after a deploy.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', (event) => event.respondWith(fetch(event.request)));
