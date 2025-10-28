/**
 * SmartSite Optimizer Analytics Tracking Script
 */

(function() {
	'use strict';

	if (typeof ssoAnalytics === 'undefined') {
		return;
	}

	var config = ssoAnalytics;
	var vitalsData = {};

	// Track Core Web Vitals
	if (config.trackVitals && 'PerformanceObserver' in window) {
		trackWebVitals();
	}

	// Track user behavior
	if (config.trackBehavior) {
		trackBehavior();
	}

	function trackWebVitals() {
		// Track LCP (Largest Contentful Paint)
		try {
			var lcpObserver = new PerformanceObserver(function(list) {
				var entries = list.getEntries();
				var lastEntry = entries[entries.length - 1];
				vitalsData.lcp = lastEntry.renderTime || lastEntry.loadTime;
			});
			lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });
		} catch (e) {}

		// Track FID (First Input Delay)
		try {
			var fidObserver = new PerformanceObserver(function(list) {
				var entries = list.getEntries();
				entries.forEach(function(entry) {
					vitalsData.fid = entry.processingStart - entry.startTime;
				});
			});
			fidObserver.observe({ entryTypes: ['first-input'] });
		} catch (e) {}

		// Track CLS (Cumulative Layout Shift)
		try {
			var clsValue = 0;
			var clsObserver = new PerformanceObserver(function(list) {
				list.getEntries().forEach(function(entry) {
					if (!entry.hadRecentInput) {
						clsValue += entry.value;
						vitalsData.cls = clsValue;
					}
				});
			});
			clsObserver.observe({ entryTypes: ['layout-shift'] });
		} catch (e) {}

		// Track TTFB and FCP
		window.addEventListener('load', function() {
			setTimeout(function() {
				var perfData = performance.getEntriesByType('navigation')[0];
				if (perfData) {
					vitalsData.ttfb = perfData.responseStart - perfData.requestStart;
				}

				var paintEntries = performance.getEntriesByType('paint');
				paintEntries.forEach(function(entry) {
					if (entry.name === 'first-contentful-paint') {
						vitalsData.fcp = entry.startTime;
					}
				});

				// Send data after a delay to capture all metrics
				setTimeout(sendVitalsData, 3000);
			}, 0);
		});
	}

	function sendVitalsData() {
		var xhr = new XMLHttpRequest();
		xhr.open('POST', config.ajaxUrl, true);
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		
		var data = 'action=sso_track_web_vitals';
		data += '&nonce=' + encodeURIComponent(config.nonce);
		data += '&page_url=' + encodeURIComponent(window.location.href);
		data += '&lcp=' + (vitalsData.lcp || 0);
		data += '&fid=' + (vitalsData.fid || 0);
		data += '&cls=' + (vitalsData.cls || 0);
		data += '&ttfb=' + (vitalsData.ttfb || 0);
		data += '&fcp=' + (vitalsData.fcp || 0);

		xhr.send(data);
	}

	function trackBehavior() {
		var startTime = Date.now();
		var pageUrl = window.location.href;

		// Track time on page
		window.addEventListener('beforeunload', function() {
			var timeOnPage = (Date.now() - startTime) / 1000;
			
			var xhr = new XMLHttpRequest();
			xhr.open('POST', config.ajaxUrl, true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			
			var data = 'action=sso_track_metric';
			data += '&nonce=' + encodeURIComponent(config.nonce);
			data += '&page_url=' + encodeURIComponent(pageUrl);
			data += '&metric_type=time_on_page';
			data += '&metric_value=' + timeOnPage;

			xhr.send(data);
		});
	}

	// Expose API for custom event tracking
	window.ssoTrackEvent = function(eventName, eventData) {
		var xhr = new XMLHttpRequest();
		xhr.open('POST', config.ajaxUrl, true);
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		
		var data = 'action=sso_track_metric';
		data += '&nonce=' + encodeURIComponent(config.nonce);
		data += '&page_url=' + encodeURIComponent(eventData.url || window.location.href);
		data += '&metric_type=custom_event_' + encodeURIComponent(eventName);
		data += '&metric_value=' + (eventData.value || 1);

		xhr.send(data);
	};

})();
