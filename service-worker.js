// Cache resources
const cacheName = 'buoycache-v1';

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

  // Code below from:
  // https://carmalou.com/lets-take-this-offline/2019/04/16/cache-requests-with-service-worker.html
	event.respondWith(
    (async function() {
          var cache = await caches.open(cacheName);
          var cachedFiles = await cache.match(event.request);
          if(cachedFiles) {
            return cachedFiles;
          } else {
              try {
                var response = await fetch(event.request);
                await cache.put(event.request, response.clone());
                return response;
              } catch(e) { 
                console.log('Error Fetching Data in fetch addEventListener');
              }
          }
      }())
  )

});