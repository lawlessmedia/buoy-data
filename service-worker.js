// Cache resources
const cacheName = 'buoycache-v1';
const precacheResources = [
  '/surf/',
  'index.php',
  'css/buoydata.css'
];

// Register Service Worker listeners
self.addEventListener('install', event => {
  console.log('Service worker installing...');
  event.waitUntil(
	  caches.open(cacheName)
	  	.then(cache => {
		  	return cache.addAll(precacheResources);
	  	})
  );
  //self.skipWaiting(); // Comment out this line once testing is done
});

self.addEventListener('activate', event => {
  console.log('Service worker activating...');
});

self.addEventListener('fetch', event => {
	console.log('Fetch intercepted for:', event.request.url);
	event.respondWith(
  		caches.match(event.request)
  			.then(cachedResponse => {
  				return cachedResponse || fetch(event.request);
  			})
  	);
});