// Cache resources
const cacheName = 'cache-v1';
const precacheResources = [
  '/',
  'index.php',
  'css/buoydata.css'
];

// Register Service Worker listeners
self.addEventListener('install', event => {
  console.log('Service worker installing...');
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  console.log('Service worker activating...');
});

self.addEventListener('fetch', event => {
  console.log('Fetch intercepted for:', event.request.url);
});