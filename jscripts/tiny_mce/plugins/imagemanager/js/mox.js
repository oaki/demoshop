/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

/**#@+
 * @class This is the core class for the Moxiecode API. It handles core problems like
 * creating new classes and instances and also console logging to the devkit.
 * @member moxiecode
 * @static
 */
var mox = {
	/**#@+
	 * @field
	 */

	/**
	 * State true/false if DOMContentLoaded has been fired or not.
	 */
	contentLoaded : false,

	/**
	 * Class load que, this gets filled when pending scripts/classes are loaded.
	 */
	que : [],

	/**
	 * Class lookup array with states 1 is loading, 2 is finished loading.
	 */
	classes : {},

	/**
	 * Constant if the current browser is Opera or not.
	 *
	 * @type bool
	 */
	isOpera : (/Opera/gi).test(navigator.userAgent),

	/**
	 * Constant if the current browser is Safari or not.
	 *
	 * @type bool
	 */
	isSafari : (/Safari|KHTML|Konqueror/gi).test(navigator.userAgent),

	/**
	 * Constant if the current browser is Gecko/Firefox/Mozilla or not.
	 *
	 * @type bool
	 */
	isGecko : !(/Safari|KHTML|Konqueror/gi).test(navigator.userAgent) && (/Gecko/gi).test(navigator.userAgent),

	/**
	 * Constant if the current browser is Microsoft Internet Explorer or not.
	 *
	 * @type bool
	 */
	isIE : (/MSIE/gi).test(navigator.userAgent) && (/Explorer/gi).test(navigator.appName),

	/**
	 * Base URL where the API is located.
	 */
	baseURL : '',

	/**#@+
	 * @method
	 */

	/**
	 * Creates a new class by the specified name, this method will create the namespace
	 * for the class and assign the methods specified to the new class object. It also
	 * supports inheritage and the creation of purly static classes.
	 *
	 * @param {string} s Class name to create and possible classes to inherit and if it should be static.
	 * @param {object} p Proprotype name/value object containing methods that the new class should include.
	 */
	create : function(s, p) {
		var st, cp, bp, co, cn, bcn, m, pm, sn, n, t = this;

		// Parse static and split class and base class
		st = /static\s+/.test(s);
		s = s.replace(/(static)\s+/, '');
		s = s.replace(/\s+/, '').split(':');

		// Setup base prototype and constructor
		bcn = this.getClassName(s[1]);
		cn = this.getClassName(s[0]);
		co = p[cn];

		// Require base class if needed
		this.require(s[1], function() {
			if (bcn)
				bp = t.getClass(s[1]).prototype;

			// Add pass through constructor
			if (!co) {
				co = p[cn] = function() {
					if (bp)
						bp[bcn].apply(this, arguments);
				};
			}

			// Inherit constructor
			if (bp) {
				t._inherit(bp, p, bcn, cn);
				co = p[cn];
			}

			// Get class prototype
			cp = co.prototype;

			// Add base methods
			if (bp) {
				for (n in bp)
					cp[n] = bp[n];
			}

			// Add class methods
			for (n in p) {
				// Handle static members
				if (n == 'static') {
					for (sn in p[n])
						co[sn] = p[n][sn];
				} else {
					if (bp && bp[n])
						t._inherit(bp, p, n, n);

					cp[n] = p[n];
				}
			}

			// Add class to namespace
			t.getClass(s[0], st ? new co() : co);
			t.provide(s[0]);
		});
	},

	/**
	 * Returs a class by name, this will resolve the namespaces in a string and look for the class this
	 * method can also be used to add new classes to the API. It has this dual functionality to
	 * reduce script size.
	 *
	 * @param {string} s Class name to look for, for example mox.somepackage.someclass.
	 * @param {function} cl Optional class to add to the API.
	 * @return {function} Function reference to the specified class or null if it wasn't found.
	 */
	getClass : function(s, cl) {
		var i, l, n, c;

		for (i=0, s = s.split('.'), l=s.length, c = window; i<l; i++) {
			n = s[i];

			if (i == l - 1) {
				if (cl)
					c[n] = cl;

				return c[n];
			}

			c = !c[n] ? (c[n] = {}) : c[n];
		}

		return null;
	},

	/**
	 * Returns the class name/last portion of a namespaced class string.
	 *
	 * @param {string} s Namespaced input string for example: mox.somepackage.SomeClass.
	 * @return {string} Last portion of namespaced string for example: SomeClass.
	 */
	getClassName : function(s) {
		return !s ? '' : s.match(/(^|\.)([a-z0-9_]+)$/i)[2];
	},

	/**
	 * Logs a message to devkit if it's enabled.
	 *
	 * @param {mixed} 1..n Unlimited number of arguments to log as a message to devkit console window.
	 */
	log : function() {
		var d = mox.devkit, c = window.console, a = arguments;

		// Log to devkit
		if (d)
			d.DevKit.log.apply(d.DevKit, a);

		// Log to firebug
		if (c && c.debug && !this.isSafari)
			c.debug(a.length == 1 ? a[0] : a);
	},

	/**
	 * Binds a function to a specific scope, this is useful for event methods so that they are bound
	 * to the correct class scope.
	 *
	 * @param {object} s Scope reference to bind function to.
	 * @param {function} f Function to execute in the specified scope.
	 * @return {function} Scope bound function object.
	 */
	bind : function(s, f) {
		return typeof(f) == 'function' ? function() {
			return f.apply(s, arguments);
		} : null;
	},

	/**
	 * Sets up the baseURL of the moxiecode API by searching for the specified key.
	 *
	 * @param {string} k key to search for.
	 * @return {string} Base URL for the API or null if it wasn't found.
	 */
	findBaseURL : function(k) {
		var i, nl = document.getElementsByTagName('script'), n;

		for (i=0; i<nl.length; i++) {
			n = nl[i];

			if (n.src && k.test(n.src))
				return this.baseURL = n.src.substring(0, n.src.lastIndexOf('/'));
		}

		return null;
	},

	/**
	 * Imports/loads the specified classes(s). This method will not load the same class twice.
	 *
	 * @param {string/Array} f Array or string containting full class name to load.
	 * @param {function} cb Optional callback to execute ones the specified classes are loaded.
	 */
	require : function(f, cb) {
		var i, o = [];

		if (!f) {
			if (cb)
				cb();

			return;
		}

		if (typeof(f) == 'string')
			f = [f];

		for (i=0; i<f.length; i++) {
			if (!this.getClass(f[i]))
				o.push(f[i]);
		}

		if (cb) {
			if (o.length == 0)
				cb();
			else
				this.que.push({classes : o, count : 0, callback : cb});
		}

		this.loadClasses(o);
	},

	/**
	 * Tells the loading que that a class has been loaded. This will trigger callback functions ones
	 * specific classes get loaded.
	 *
	 * @param {string} c Class that got loaded for example mox.DOM.
	 */
	provide : function(c) {
		var i, y, q, cl, cb = [];

		if (this.classes[c] > 1)
			return;

		this.classes[c] = 2;

		for (i=0, q = this.que; i<q.length; i++) {
			if (q[i]) {
				for (y=0, cl = q[i].classes; y<cl.length; y++) {
					if (cl[y] == c)
						q[i].count++;
				}

				if (q[i].count >= q[i].classes.length) {
					cb.push(q[i].callback);
					q[i] = null;
				}
			}
		}

		for (i=0; i<cb.length; i++)
			cb[i]();
	},

	/**
	 * Loads a specific class asynchrous by adding it to the head element.
	 *
	 * @param {string} c Class name to load.
	 */
	loadClasses : function(cl) {
		var d = document, s, i, c;

		for (i=0; i<cl.length; i++) {
			c = cl[i];

			if (this.classes[c] > 0)
				continue;

			this.classes[c] = 1;

			s = d.createElement('script');
			s.setAttribute('type', 'text/javascript');
			s.setAttribute('src', this.baseURL + '/' + c.toLowerCase().replace(/\./g, '/') + '.js');
			d.getElementsByTagName('head')[0].appendChild(s);
		}
	},

	// Private method
	
	_inherit : function(bp, p, bn, n) {
		var m = p[n], pm = bp[bn];

		p[n] = function() {
			// NOTE: Maybe restore parent after call
			this.parent = pm;
			return m.apply(this, arguments);
		};
	},

	_pageInit : function() {
		if (this.isIE && !/https/gi.test(document.location.protocol)) {
			document.write('<script id=__ie_onload defer src=javascript:void(0)><\/script>');
			document.getElementById("__ie_onload").onreadystatechange = mox._pageDone;
		}
	},

	_pageDone : function() {
		if (this.readyState == "complete")
			mox.contentLoaded = true;
	}

	/**#@-*/
};

// Set contentLoaded state
if (mox.isGecko) {
	window.addEventListener('DOMContentLoaded', function() {
		mox.contentLoaded = true;
	}, false);
};

mox._pageInit();

// Look for the API
mox.findBaseURL(/mox\.js/);
