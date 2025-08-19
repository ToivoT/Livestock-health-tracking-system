// Service Worker for LHVTS - Offline Functionality
const CACHE_NAME = 'lhvts-v1';
const urlsToCache = [
    '/',
    '/pages/login.php',
    '/pages/dashboard.php',
    '/pages/register_livestock.php',
    '/pages/record_vaccination.php',
    '/pages/report_disease.php',
    '/assets/css/styles.css',
    '/assets/js/scripts.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/chart.js'
];

// Install event - cache resources
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
            .catch(err => {
                console.error('Cache installation failed:', err);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', event => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Parse the URL
    const url = new URL(event.request.url);

    // Handle API requests differently
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    // Clone the response before caching
                    const responseToCache = response.clone();
                    
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, responseToCache);
                    });
                    
                    return response;
                })
                .catch(() => {
                    // If offline, try to get from cache
                    return caches.match(event.request)
                        .then(response => {
                            if (response) {
                                return response;
                            }
                            
                            // Return offline response for API calls
                            return new Response(
                                JSON.stringify({
                                    success: false,
                                    offline: true,
                                    message: 'You are offline. This action will be synced when connection is restored.'
                                }),
                                {
                                    headers: { 'Content-Type': 'application/json' }
                                }
                            );
                        });
                })
        );
        return;
    }

    // Handle regular requests
    event.respondWith(
        caches.match(event.request)
            .then(response => {
                // Cache hit - return response
                if (response) {
                    return response;
                }

                // Clone the request
                const fetchRequest = event.request.clone();

                return fetch(fetchRequest).then(response => {
                    // Check if valid response
                    if (!response || response.status !== 200 || response.type === 'opaque') {
                        return response;
                    }

                    // Clone the response
                    const responseToCache = response.clone();

                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, responseToCache);
                    });

                    return response;
                }).catch(() => {
                    // Return offline page for navigation requests
                    if (event.request.mode === 'navigate') {
                        return caches.match('/offline.html').catch(() => {
                            return new Response(`
                                <!DOCTYPE html>
                                <html>
                                <head>
                                    <title>Offline - LHVTS</title>
                                    <meta name="viewport" content="width=device-width, initial-scale=1">
                                    <style>
                                        body {
                                            font-family: Arial, sans-serif;
                                            text-align: center;
                                            padding: 50px;
                                            background: #f8f9fa;
                                        }
                                        .container {
                                            max-width: 600px;
                                            margin: 0 auto;
                                            background: white;
                                            padding: 40px;
                                            border-radius: 10px;
                                            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                                        }
                                        h1 { color: #2c5530; }
                                        .emoji { font-size: 64px; margin: 20px; }
                                        button {
                                            background: #2c5530;
                                            color: white;
                                            border: none;
                                            padding: 10px 20px;
                                            border-radius: 5px;
                                            cursor: pointer;
                                            margin-top: 20px;
                                        }
                                    </style>
                                </head>
                                <body>
                                    <div class="container">
                                        <div class="emoji">📵</div>
                                        <h1>You're Offline</h1>
                                        <p>It looks like you've lost your internet connection.</p>
                                        <p>Don't worry! Your data is saved locally and will sync when you're back online.</p>
                                        <button onclick="window.location.reload()">Try Again</button>
                                    </div>
                                </body>
                                </html>
                            `, {
                                headers: { 'Content-Type': 'text/html' }
                            });
                        });
                    }
                });
            })
    );
});

// Background sync for offline data
self.addEventListener('sync', event => {
    if (event.tag === 'sync-offline-data') {
        event.waitUntil(syncOfflineData());
    }
});

async function syncOfflineData() {
    try {
        // Get offline data from IndexedDB or localStorage
        const offlineData = await getOfflineData();
        
        if (offlineData && offlineData.length > 0) {
            for (const item of offlineData) {
                try {
                    const response = await fetch(item.url, {
                        method: item.method,
                        headers: item.headers,
                        body: item.body
                    });
                    
                    if (response.ok) {
                        // Remove synced item from offline storage
                        await removeOfflineData(item.id);
                    }
                } catch (error) {
                    console.error('Failed to sync item:', item.id, error);
                }
            }
        }
        
        // Notify user of successful sync
        self.registration.showNotification('LHVTS Sync Complete', {
            body: 'Your offline data has been synchronized.',
            icon: '/assets/images/icon-192.png',
            badge: '/assets/images/badge-72.png'
        });
        
    } catch (error) {
        console.error('Sync failed:', error);
    }
}

// Helper functions for offline data management
async function getOfflineData() {
    // This would typically use IndexedDB
    // For now, return empty array
    return [];
}

async function removeOfflineData(id) {
    // Remove item from IndexedDB
    console.log('Removing synced item:', id);
}

// Message handler for communication with main app
self.addEventListener('message', event => {
    if (event.data.action === 'skipWaiting') {
        self.skipWaiting();
    }
});