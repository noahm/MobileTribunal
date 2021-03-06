/*
 * Viewporter v2.0
 * http://github.com/zynga/viewporter
 *
 * Copyright 2011, Zynga Inc.
 * Licensed under the MIT License.
 * https://raw.github.com/zynga/viewporter/master/MIT-LICENSE.txt
 */
var viewporter;
(function() {

	// initialize viewporter object
	viewporter = {

		// constants
		ACTIVE: (('ontouchstart' in window) || (/webos/i).test(navigator.userAgent)),

		// methods
		isLandscape: function() {
			return window.orientation === 90 || window.orientation === -90;
		},

		ready: function(callback) {
			window.addEventListener('viewportready', callback, false);
		}

	};

	// if we are on Desktop, no need to go further
	if (!viewporter.ACTIVE) {
		return;
	}

	// create private constructor with prototype..just looks cooler
	var _Viewporter = function() {

		var that = this;

		this.IS_ANDROID = /Android/.test(navigator.userAgent);

		// listen for document ready, then try to prepare the visual viewport and start firing custom events
		document.addEventListener('DOMContentLoaded', function() {

			// scroll the shit away and fix the viewport!
			that.prepareVisualViewport();

			// listen for orientation change
			var cachedOrientation = window.orientation;
			window.addEventListener('orientationchange', function() {
				if(window.orientation != cachedOrientation) {
					that.prepareVisualViewport();
					cachedOrientation = window.orientation;
				}
			}, false);

		}, false);

	};

	_Viewporter.prototype = {

		getProfile: function() {
			for(var searchTerm in viewporter.profiles) {
				if(new RegExp(searchTerm).test(navigator.userAgent)) {
					return viewporter.profiles[searchTerm];
				}
			}
			return null;
		},

		prepareVisualViewport: function() {
			
			var that = this;

			// maximize the document element's height to be able to scroll away the url bar
			document.documentElement.style.minHeight = '5000px';

			var startHeight = window.innerHeight;
			var deviceProfile = this.getProfile();
			var orientation = viewporter.isLandscape() ? 'landscape' : 'portrait';

			// try scrolling immediately
			window.scrollTo(0, that.IS_ANDROID ? 1 : 0); // Android needs to scroll by at least 1px

			// start the checker loop
			var iterations = this.IS_ANDROID && !deviceProfile ? 20 : 5; // if we're on Android and don't know the device, brute force hard
			var check = window.setInterval(function() {

				// retry scrolling
				window.scrollTo(0, that.IS_ANDROID ? 1 : 0); // Android needs to scroll by at least 1px

				if(
					that.IS_ANDROID
						? (deviceProfile ? window.innerHeight === deviceProfile[orientation] : --iterations < 0) // Android: either match against a device profile, or brute force
						: (window.innerHeight > startHeight || --iterations < 0) // iOS is comparably easy!
				) {
					
					// set minimum height of content to new window height
					document.documentElement.style.minHeight = window.innerHeight + 'px';

					// set the right height for the body wrapper to allow bottom positioned elements
					document.getElementById('viewporter').style.position = 'relative';
					document.getElementById('viewporter').style.height = window.innerHeight + 'px';

					clearInterval(check);

					// let everyone know we're finally ready
					that.triggerWindowEvent(!that._firstUpdateExecuted ? 'viewportready' : 'viewportchange');
					that._firstUpdateExecuted = true;
				}

			}, 10);
				
		},

		triggerWindowEvent: function(name) {
			var event = document.createEvent("Event");
			event.initEvent(name, false, false);
			if (window.dispatchEvent) {
				window.dispatchEvent(event);
			} else {
				document.dispatchEvent(event);
			}
		}

	};

	// initialize
	new _Viewporter();

})();

viewporter.profiles = {

	// Motorola Xoom
	'MZ601': {
		portrait: 696,
		landscape: 1176
	},

	// Samsung Galaxy S, S2 and Nexus S
	'GT-I9000|GT-I9100|Nexus S': {
		portrait: 508,
		landscape: 295
	},

	// Samsung Galaxy Pad
	'GT-P1000': {
		portrait: 657,
		landscape: 400
	},

	// HTC Desire & HTC Desire HD
	'Desire_A8181|DesireHD_A9191': {
		portrait: 533,
		landscape: 320
	}

}
