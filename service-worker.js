// Cache resources
const cacheName = 'buoycache-v2';

let todaysDate = new Date();
let apiDate = todaysDate.toLocaleString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit' });
	
const precacheResources = [
  '/surf/',
  'index.php',
  'css/buoydata.css',
  'https://tidesandcurrents.noaa.gov/api/datagetter?product=predictions&application=NOS.COOPS.TAC.WL&begin_date=' + apiDate + '&end_date=' + apiDate + '&datum=MLLW&station=8658559&time_zone=lst_ldt&units=english&interval=hilo&format=json',
  'favicon-16x16.png',
  'favicon-32x32.png',
  'android-chrome-192x192.png',
  'manifest.json',
];

// Register Service Worker listeners
self.addEventListener('install', event => {
  console.log('Service worker installing...');
  event.waitUntil(
	caches.open(cacheName).then(cache => {
		return cache.addAll(precacheResources);
		console.log('Caches added.');
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
    // Try the network
    fetch(event.request)
      .then(function(response) {
        return caches.open(cacheName)
          .then(function(cache) {
            // Put in cache if succeeds
            cache.put(event.request, response.clone());
            return response;
          })
      })
      .catch(function(err) {
          // Fallback to cache if network fails
          return caches.match(event.request);
    })
  )
  
});