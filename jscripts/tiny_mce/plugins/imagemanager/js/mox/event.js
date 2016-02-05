/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

mox.require([
	'mox.DOM'
], function() {
	/**#@+
	 * @class This class contains common event handling logic.
	 * @member mox.Event
	 * @static
	 */
	mox.create('static mox.Event', {
		_domLoaded : [],
		_listeners : [],
		_cleanups : [],
		_domLoadedState : false,

		/**#@+
		 * @method
		 */

		/**
		 * Adds a event handler to a DOM object.
		 *
		 * @param {Object} o DOM object to attach event handler to.
		 * @param {string} n Name of event to attach like click or load.
		 * @param {function} f Function callback to execute when the event occurs.
		 * @param {Object} s Optional scope object to execute callback on.
		 * @return {function} Function reference to callback, use this with removeHandler.
		 */
		add : function(o, n, f, s) {
			var ev = this, cb;

			if (!o)
				return null;

			o = mox.DOM.get(o);

			// Shorter version of DOMContentLoaded
			if (n == 'init')
				n = 'DOMContentLoaded';

			// Setup event callback
			cb = function(e) {
				e = e || window.event;

				// Patch in target in IE it's W3C valid
				if (e && !e.target)
					e.target = e.srcElement;

				if (!s)
					return f(e);

				return f.call(s, e);
			};

			// Already loaded fire it now!
			if (mox.contentLoaded && n == 'DOMContentLoaded') {
				cb();
				return;
			}

			// Fake DOMContentLoaded event on IE & Safari
			if ((mox.isIE || mox.isSafari) && n == 'DOMContentLoaded') {
				this._domLoaded.push(cb);

				return cb;
			}

			// Store away listener reference
			this._listeners.push({
				obj : o,
				name : n,
				func : f,
				cfunc : cb,
				scope : s
			});

			this._addEvent(o, n, cb);

			return cb;
		},

		/**
		 * Removes a event handler from the specified object.
		 *
		 * @param {Object} o DOM object to remove event handler from.
		 * @param {string} n Name of event to attach like click or load.
		 * @param {function} f Function callback to execute when the event occurs.
		 * @return {function} Function reference to callback.
		 */
		remove : function(o, n, f) {
			var i, li = this._listeners, it;

			if (!o)
				return null;

			o = mox.DOM.get(o);

			this._removeEvent(o, n, f);

			for (i=0; i<li.length; i++) {
				it = li[i];

				// Is match, then remove it
				if (it.obj == o && it.name == n && it.func == f) {
					this._removeEvent(it.obj, it.name, it.cfunc);
					li.splice(i, 1);

					return f;
				}
			}

			return null;
		},

		/**
		 * Removes any events hooked onto the specified object.
		 *
		 * @param {Object} o DOM object to remove events listeners from.
		 */
		removeAll : function(o) {
			var i, li, it;

			for (i=0, li = this._listeners; i<li.length; i++) {
				it = li[i];

				// Is match, then clean it
				if (it.obj == o) {
					this._removeEvent(it.obj, it.name, it.cfunc);
					li.splice(i, 1);
				}
			}
		},

		/**
		 * Cancels the specified event, this will disable the event from be passed to other
		 * listeners in event chain and also cancel the browsers default behavior.
		 *
		 * @param {DOMEvent} e Event to cancel.
		 * @return {bool} Returns false.
		 */
		cancel : function(e) {
			var t = mox.Event;

			t.stopPropagation(e);

			return t.preventDefault(e);
		},

		/**
		 * Stops event propagation. This will cancel the event bubbeling.
		 *
		 * @param {DOMEvent} e Event to cancel.
		 * @return {bool} Returns false.
		 */
		stopPropagation : function(e) {
			if (!e)
				return false;

			if (e.stopPropagation)
				e.stopPropagation();
			else
				e.cancelBubble = true;

			return false;
		},

		/**
		 * Stops the browsers default behavior.
		 *
		 * @param {DOMEvent} e Event to stop.
		 * @return {bool} Returns false.
		 */
		preventDefault : function(e) {
			if (!e)
				return false;

			if (e.preventDefault)
				e.preventDefault();
			else
				e.returnValue = false;

			return false;
		},

		fire : function(o, n, e) {
			var i, li, it;

			if (!e)
				return false;

			for (i=0, li=this._listeners; i<li.length; i++) {
				it = li[i];

				if (it.obj == o && it.name == n)
					it.cfunc(e);
			}

			return true;
		},

		/**
		 * Returns the page X, Y cordinate from a event. This method will returns a lightweight object with just two properties x, y.
		 *
		 * @param {DOMEvent} e Event to get page X, Y from.
		 * @return {Object} Lightweight object with a x, y property.
		 */
		getPageXY : function(e) {
			var d = document, de = d.documentElement || d.body, x = e.pageX || e.clientX || 0, y = e.pageY || e.clientY || 0;

			if (mox.isIE) {
				x += de.scrollLeft;
				y += de.scrollTop;
			}

			return {x : x, y : y};
		},

		/**
		 * Adds a memory cleanup function on page unload. This method can be used to add
		 * custom logic to be executed on page unload since some browsers have
		 * memory leaks.
		 *
		 * @param {function} f Function to execute on page unload.
		 */
		addCleanup : function(f) {
			this._cleanups.push(f);
		},

		// * * Private methods

		_unLoad : function() {
			var i, ev = mox.Event, li, it, ex;

			for (i=0, li=ev._listeners; i<li.length; i++) {
				it = li[i];

				try {
					ev._removeEvent(it.obj, it.name, it.cfunc);
					it.obj = it.name = it.cfunc = null;
				} catch (ex) {
					// Ignore
				}
			}

			for (i=0, li=ev._cleanups; i<li.length; i++) {
				try {
					li[i]();
				} catch (ex) {
					// Ignore
				}
			}

			ev._removeEvent(window, 'unload', ev._unLoad);
			ev._listeners = [];
			ev._cleanups = [];
		},

		_addEvent : function(o, n, f) {
			if (o.attachEvent)
				o.attachEvent('on' + n, f);
			else if (o.addEventListener)
				o.addEventListener(n, f, false);
			else
				o['on' + n] = f;
		},

		_removeEvent : function(o, n, f) {
			if (o.detachEvent)
				o.detachEvent('on' + n, f);
			else if (o.removeEventListener)
				o.removeEventListener(n, f, false);
			else
				o['on' + n] = null;
		},

		_DOMContentLoadedIE : function() {
			var e = document.getElementById('_ie_lo');

			if (e.readyState == "complete") {
				this._dispatchDOMContentLoaded();
				e.onreadystatechange = null;
			}
		},

		_dispatchDOMContentLoaded : function() {
			var i, ar = mox.Event._domLoaded;

			for (i=0; i<ar.length; i++)
				ar[i](window.event);

			mox.Event._domLoaded = [];
			mox.contentLoaded = true;
		},

		_waitForLoaded : function() {
			if (/loaded|complete/.test(document.readyState))
				this._dispatchDOMContentLoaded();
			else
				window.setTimeout('mox.Event._waitForLoaded();', 10);
		},

		_handleDOMContentLoaded : function(d) {
			d = !d ? document : d;

			// Force CSS background caching
			if (mox.isIE) {
				try {
					document.execCommand('BackgroundImageCache', false, true);
				} catch (e) {
					// Ignore
				}
			}

			// Add unload handler
			this._addEvent(window, 'unload', this._unLoad);

			// Fake DOM content loaded in IE and Safari
			if (mox.isIE || mox.isSafari)
				this._waitForLoaded();
		}

		/**#@-*/
	});

	// Dispatch DOM content loaded event for IE and Safari
	mox.Event._handleDOMContentLoaded();
});
