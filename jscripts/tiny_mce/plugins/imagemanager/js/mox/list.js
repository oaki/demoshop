/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

new function() {
	function isset(o) {
		return typeof(o) != 'undefined';
	};

	/**#@+
	 * @class This class contains common array and list management methods.
	 * @member mox.List
	 * @static
	 */
	mox.create('static mox.List', {
		/**#@+
		 * @method
		 */

		/**
		 * Filters the specified array by the specified filter function.
		 *
		 * @param {Array} a Array to filter out elements from.
		 * @param {function} f Function to call for each array item. If this function returns true the item will be placed in output.
		 * @param {Number} si Optional start index.
		 * @param {Number} ei Optional end index.
		 * @return {Array} Filtered array.
		 */
		filter : function(a, f, si, ei) {
			var i, o = [];

			si = isset(si) ? si : 0;
			ei = isset(ei) ? ei : a.length;

			for (i=si; i<ei; i++) {
				if (f(i, a[i]))
					o.push(a[i]);
			}

			return o;
		},

		map : function(a, f, si, ei) {
			var i;

			si = isset(si) ? si : 0;
			ei = isset(ei) ? ei : a.length;

			for (i=si; i<ei; i++)
				f(i, a[i]);
		},

		indexOf : function(a, v) {
			var i;

			for (i=0; i<a.length; i++) {
				if (a[i] == v)
					return i;
			}

			return -1;
		},

		each : function(a, f) {
			var n, v;

			for (n in a) {
				if ((v = f(n, a[n])))
					return v;
			}

			return false;
		},

		getKeys : function(a) {
			var o = [];

			this.each(a, function(n, v) {
				o.push(n);
			});

			return o;
		},

		hasValue : function(a, rv) {
			var s = 0;

			this.each(a, function(n, v) {
				return s = (v == rv);
			});

			return s;
		},

		remove : function(a, rv) {
			return this.filter(a, function(i, v) {
				return v != rv;
			});
		}

		/**#@-*/
	});
};