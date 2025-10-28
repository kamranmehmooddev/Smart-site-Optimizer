/**
 * SmartSite Optimizer Lazy Loading Script
 */

(function() {
	'use strict';

	// Check for IntersectionObserver support
	if (!('IntersectionObserver' in window)) {
		// Fallback: load all images immediately
		loadAllImages();
		return;
	}

	// Create observer
	var observer = new IntersectionObserver(function(entries, observer) {
		entries.forEach(function(entry) {
			if (entry.isIntersecting) {
				loadElement(entry.target);
				observer.unobserve(entry.target);
			}
		});
	}, {
		rootMargin: '50px 0px',
		threshold: 0.01
	});

	// Observe all lazy-loadable elements
	function observeElements() {
		var elements = document.querySelectorAll('img[data-src], iframe[data-src]');
		elements.forEach(function(element) {
			observer.observe(element);
		});
	}

	// Load individual element
	function loadElement(element) {
		var src = element.getAttribute('data-src');
		if (!src) return;

		if (element.tagName === 'IMG') {
			var img = new Image();
			img.onload = function() {
				element.src = src;
				element.classList.add('sso-loaded');
			};
			img.src = src;
		} else {
			element.src = src;
			element.classList.add('sso-loaded');
		}

		element.removeAttribute('data-src');
	}

	// Fallback: load all images
	function loadAllImages() {
		var elements = document.querySelectorAll('img[data-src], iframe[data-src]');
		elements.forEach(loadElement);
	}

	// Initialize on DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', observeElements);
	} else {
		observeElements();
	}

})();
