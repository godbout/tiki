const OFFLINE_URL = 'lib/pwa/offline.html';
importScripts("vendor/npm-asset/dexie/dist/dexie.min.js")
const staticAssets = [
	'.',
	'themes/base_files/favicons/manifest.json',
	'themes/base_files/css/tiki_base.css',
	'vendor_bundled/vendor/bower-asset/fontawesome/css/all.css',
	'themes/default/css/default.css',
	'img/tiki/Tiki_WCG.png',
	'tiki-index.php',
	'lib/jquery_tiki/tiki-bootstrapmodalfix.js',
	'lib/jquery_tiki/iconsets.js',
	'lib/tiki-js.js',
	'lib/jquery_tiki/tiki-jquery.js',
	'tiki-listpages.php',
	'vendor/npm-asset/dexie/dist/dexie.min.js',
	'lib/jquery_tiki/tiki-trackers.js',

	OFFLINE_URL,
];
const cacheName = 'pages-cache-v1';

var db = new Dexie("post_cache");
db.version(1).stores({
	messages: 'name,value',
	post_cache: 'key,request,timestamp',
});

self.addEventListener('install', event => {
	event.waitUntil(
		caches.open(cacheName)
			.then(cache => cache.addAll(staticAssets))
			.then(self.skipWaiting())
	);
});

self.addEventListener('activate', event => {

	const currentCaches = [cacheName];
	event.waitUntil(
		caches.keys().then(cacheNames => {
			return cacheNames.filter(cacheName => !currentCaches.includes(cacheName));
		}).then(cachesToDelete => {
			return Promise.all(cachesToDelete.map(cacheToDelete => {
				return caches.delete(cacheToDelete);
			}));
		}).then(() => self.clients.claim())
	);
});

// The fetch handler serves responses for same-origin resources from a cache.
// If no response is found, it populates the runtime cache with the response
// from the network before returning it to the page.
self.addEventListener('fetch', event => {
	function normalizeRequest(url) {
		if (url.includes("tiki-view_tracker_item.php") || url.includes("tiki-ajax_services.php")) {
			url = url.replace(/&from=.*/, "");
		}
		return url;
	}


	// Skip cross-origin requests, like those for Google Analytics.
	if (event.request.url.startsWith(self.location.origin) && event.request.method === "GET" && event.request.url.indexOf("logout") === -1) {
		let request = event.request;
		let url = normalizeRequest(request.url);

		event.respondWith(
			caches.match(url).then(cachedResponse => {
				return caches.open(cacheName).then(cache => {
					return fetch(event.request).then(response => {
						// Put a copy of the response in the runtime cache.
						return cache.put(event.request, response.clone()).then(() => {
							return response;
						});
					}).catch(error => {
						console.warn(cachedResponse, url)
						if (cachedResponse) {
							return cachedResponse;
						}
						return caches.match(OFFLINE_URL);
					});
				}).catch(error => {
					return caches.match(OFFLINE_URL);
				});
			}).catch(error => {
				return caches.match(OFFLINE_URL);
			})
		);
	} else if ((event.request.method === "POST" || event.request.method === "PUT") && event.request.url.indexOf("logout") === -1) {

		event.respondWith(
			fetch(event.request.clone())
				.then(function (response) {
					return response;
				})
				.catch(function () {
					return cachePut(event, db.post_cache).then(function (resp) { //save request to be done later
						return caches.match(event.request.url).then(cachedResponse => {
							return db.messages.put({"name": "show-warning", "value": true}).then(function () {
								let body;
								var init = {
									"status": 200, "statusText": "You are offline"
								};

								if (cachedResponse) {
									console.warn("fetch", event.request.url)
									body = cachedResponse.body
								} else {
									let url = normalizeRequest(event.request.referrer);
									console.warn("fetch", url)
									return caches.match(url).then(cachedResponse => {
										console.warn("cache", cachedResponse)
										if (cachedResponse)
											body = cachedResponse.body
										return new Response(body, init);
									})
								}

								return new Response(body, init);
							});
						});

					});
				})
		);

	} else {
		console.error(event.request);
	}
});

/**
 * Serializes a Request into a plain JS object.
 *
 * @param request
 * @returns Promise
 */
function serializeRequest(request) {
	var serialized = {
		url: request.url,
		headers: serializeHeaders(request.headers),
		method: request.method,
		mode: request.mode,
		credentials: request.credentials,
		cache: request.cache,
		redirect: request.redirect,
		referrer: request.referrer
	};

	// Only if method is not `GET` or `HEAD` is the request allowed to have body.
	if (request.method !== 'GET' && request.method !== 'HEAD') {
		return request.clone().text().then(function (body) {
			serialized.body = body;
			return Promise.resolve(serialized);
		});
	}
	return Promise.resolve(serialized);
}

/**
 * Saves the response for the given request eventually overriding the previous version
 *
 * @param data
 * @returns Promise
 */
function cachePut(event, store) {
	var request = event.request.clone();
	return serializeRequest(request.clone()).then(function (data) {
		var entry = {
			key: Date.now(),
			request: data,
			timestamp: Date.now()
		};
		return store.put(entry).then(value => {
			return store.get(entry.key).then(function () {
			})
		})
	});
}

/**
 * Serializes headers into a plain JS object
 *
 * @param headers
 * @returns object
 */
function serializeHeaders(headers) {
	var serialized = {};
	// `for(... of ...)` is ES6 notation but current browsers supporting SW, support this
	// notation as well and this is the only way of retrieving all the headers.
	for (var entry of headers.entries()) {
		serialized[entry[0]] = entry[1];
	}
	return serialized;
}


