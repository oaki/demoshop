/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

/**#@+
 * @class Point geometry class.
 * @member mox.geom.Point
 */
mox.create('mox.geom.Point', {
	/**#@+
	 * @field
	 */

	/**
	 * X cordinate/Horizontal position.
	 *
	 * @type Number
	 */
	x : 0,

	/**
	 * Y cordinate/Vertical position.
	 *
	 * @type Number
	 */
	y : 0,

	/**
	 * Constructs a point.
	 *
	 * @param {Number} x X cordinate/Horizontal position.
	 * @param {Number} y Y cordinate/Vertical position.
	 * @constructor
	 */
	Point : function(x, y) {
		this.x = x;
		this.y = y;
	},

	/**#@+
	 * @method
	 */

	/**
	 * Moves the specified point to a specific location.
	 *
	 * @param {Number} x X cordinate/Horizontal position.
	 * @param {Number} y Y cordinate/Vertical position.
	 */
	moveTo : function(x, y) {
		this.x = x;
		this.y = y;
	},

	/**
	 * Moves the specified point by a relative distance.
	 *
	 * @param {Number} x Relative X cordinate/Horizontal position.
	 * @param {Number} y Relative Y cordinate/Vertical position.
	 */
	moveBy : function(x, y) {
		this.x += x;
		this.y += y;
	},

	/**
	 * Clones the current instance into a new object.
	 *
	 * @return {mox.geom.Point} New point containing the current objects data.
	 */
	clone : function() {
		return new mox.geom.Point(this.x, this.y);
	}

	/**#@-*/
});
