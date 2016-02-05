/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

mox.require([
	'mox.geom.Point'
], function() {
	// Shorten class names
	var Point = mox.geom.Point;

	/**#@+
	 * @class ....
	 * @member mox.geom.Rect
	 * @base mox.geom.Point
	 */
	mox.create('mox.geom.Rect:mox.geom.Point', {
		/**#@+
		 * @field
		 */

		/**
		 * Width of the rectange.
		 *
		 * @type Number
		 */
		w : 0,

		/**
		 * Height of the rectange.
		 *
		 * @type Number
		 */
		h : 0,

		/**
		 * Constructs a new Rectange instance.
		 *
		 * @param {Number} x X cordinate/Horizontal position.
		 * @param {Number} y Y cordinate/Vertical position.
		 * @param {Number} w Width of the rectange.
		 * @param {Number} h Height of the rectange.
		 * @constructor
		 */
		Rect : function(x, y, w, h) {
			this.parent(x, y);

			this.w = w;
			this.h = h;
		},

		/**#@+
		 * @method
		 */

		/**
		 * Returns true/false if the specified rectange intersects with the current one.
		 *
		 * @param {mox.geom.Rect} r Rectange to check agains.
		 * @return {bool} true/false state if the specified rectange intersects.
		 */
		intersects : function(r) {
			var i, p = new Point(0, 0), c = ['tl', 'tr', 'bl', 'br'];

			for (i=0; i<c.length; i++) {
				p = this.getLocation(c[i], p);

				if (r.containsXY(p.x, p.y))
					return true;
			}

			return false;
		},

		/**
		 * Returns true/false if the specified x, y cordinate is located inside the current rectange.
		 *
		 * @param {Number} x X cordinate/Horizontal position to check.
		 * @param {Number} y Y cordinate/Vertical position to check.
		 * @return {bool} true/false state if the specified x, y is located inside this rect.
		 */
		containsXY : function(x, y) {
			return x >= this.x && x <= this.x + this.w && y >= this.y && y <= this.y + this.h;
		},

		/**
		 * Returns true/false if the specified rectange is located inside the current one.
		 *
		 * @param {mox.geom.Rect} r Rectange to check agains.
		 * @return {bool} true/false state if the specified rectange is located inside the current one.
		 */
		contains : function(r) {
			return this.x <= r.x && this.y <= r.y && this.x + this.w >= r.x + r.w && this.y + this.h >= r.y + r.h;
		},

		/**
		 * Returns a location of the specified rectange. To retrive a point for the bottom right corner you specify
		 * br as a input for this method. If you want the center location inside the rectange you specify cc. Available
		 * vertical locations are t, b, c and horizontal are l, r, c both the vertical and horizontal chars is needed.
		 *
		 * @param {string} l Location string for example cc for center of rectangle or br for bottom right.
		 * @param {mox.geom.Point} Optional point to fill.
		 * @return {mox.geom.Point} Location X, Y of specified location.
		 */
		getLocation : function(l, p) {
			var v = l.charAt(0), h = l.charAt(1);

			p = !p ? new Point(0, 0) : p;

			switch (v) {
				case 't':
					p.y = this.y;
					break;

				case 'b':
					p.y = this.y + this.h;
					break;

				case 'c':
					p.y = this.y + (this.h / 2);
					break;
			}

			switch (h) {
				case 'l':
					p.x = this.x;
					break;

				case 'r':
					p.x = this.x + this.w;
					break;

				case 'c':
					p.x = this.x + (this.w / 2);
					break;
			}

			return p;
		},

		/**
		 * Resizes the rectange to the specified width and height.
		 *
		 * @param {Number} w New width of the rectange.
		 * @param {Number} h New height of the rectange.
		 */
		resizeTo : function(w, h) {
			this.w = w;
			this.h = h;
		},

		/**
		 * Resizes the rectange to the by the specified relative width and height.
		 *
		 * @param {Number} w Relative width to resize the rectange by.
		 * @param {Number} h Relative height to resize the rectange by.
		 */
		resizeBy : function(w, h) {
			this.w += w;
			this.h += h;
		},

		/**
		 * Sets the rectangles data.
		 *
		 * @param {Number} x X cordinate/Horizontal position.
		 * @param {Number} y Y cordinate/Vertical position.
		 * @param {Number} w New width of the rectange.
		 * @param {Number} h New height of the rectange.
		 */
		set : function(x, y, w, h) {
			this.x = x;
			this.y = y;
			this.w = w;
			this.h = h;
		},

		/**
		 * Clones the current instance into a new object.
		 *
		 * @return {mox.geom.Rect} New Rect containing the current objects data.
		 */
		clone : function() {
			return new mox.geom.Rect(this.x, this.y, this.w, this.h);
		}

		/**#@-*/
	});
});
