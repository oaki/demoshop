/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

mox.require([
	'mox.DOM',
	'mox.geom.Rect'
], function() {
	// Shorten class names
	var DOM = mox.DOM;
	var Rect = mox.geom.Rect;

	/**#@+
	 * @class Layer class.
	 * @member mox.dom.Layer
	 * @base mox.geom.Rect
	 */
	mox.create('mox.dom.Layer:mox.geom.Rect', {
		/**
		 * Constructor for the Layer. This class enables you to construct
		 * floating layers that is visible on top of select input fields, flashes and iframes.
		 *
		 * @param {string} id Unique ID name for the layer.
		 * @param {boolean} bm Block mode, defaults to true.
		 * @constructor
		 */
		Layer : function(id, bm) {
			this.parent(null, null, null, null);

			this.id = id;
			this.events = false;
			this.element = null;
			this.blockMode = typeof(bm) != 'undefined' ? bm : false;
			this.lastRect = new Rect(null, null, null, null);
		},

		/**#@+
		 * @method
		 */

		/**
		 * Moves the layer relative to the specified HTML element.
		 *
		 * @param {HTMLElement} re Element to move the layer relative to.
		 * @param {string} p Position of the layer tl = top left, tr = top right, bl = bottom left, br = bottom right.
		 * @param {string} rf Reference point inside element default to tl. Values: tl = top left, tr = top right, bl = bottom left, br = bottom right.
		 */
		moveRelativeTo : function(re, p, rp) {
			var x, y, r = DOM.getRect(re);

			this.updateRect();
			this.moveTo(0, 0);

			p = this.getLocation(p);
			rp = r.getLocation(rp);

			x = rp.x - p.x;
			y = rp.y - p.y;

			this.moveTo(x, y);
		},

		/**
		 * Moves the layer relative in pixels.
		 *
		 * @param {int} x Horizontal relative position in pixels.
		 * @param {int} y Vertical relative position in pixels.
		 */
		moveBy : function(x, y) {
			this.updateRect();
			this.parent(x, y);
			this.update();
		},

		/**
		 * Moves the layer absolute in pixels.
		 *
		 * @param {int} x Horizontal absolute position in pixels.
		 * @param {int} y Vertical absolute position in pixels.
		 */
		moveTo : function(x, y) {
			this.parent(x, y);
			this.update();
		},

		/**
		 * Resizes the layer by the specified relative width and height.
		 *
		 * @param {int} w Relative width value.
		 * @param {int} h Relative height value.
		 */
		resizeBy : function(w, h) {
			this.updateRect();
			this.parent(w, h);
			this.update();
		},

		/**
		 * Resizes the layer to the specified width and height.
		 *
		 * @param {int} w Width value.
		 * @param {int} h Height value.
		 */
		resizeTo : function(w, h) {
			this.parent(w, h);
			this.update();
		},

		/**
		 * Set style.
		 */
		setStyle : function(n, v) {
			DOM.setStyle(this.getElement(), n, v);
		},

		getStyle : function(n) {
			return DOM.getStyle(this.getElement(), n);
		},

		/**
		 * Shows the layer.
		 */
		show : function() {
			DOM.show(this.getElement());
		},

		/**
		 * Hides the layer.
		 */
		hide : function() {
			DOM.hide(this.getElement());
		},

		/**
		 * Returns true/false if the layer is hidden or not.
		 *
		 * @return {bool} true/false if it's hidden or not.
		 */
		isHidden : function() {
			return this.getStyle('display') == 'none';
		},

		/**
		 * Sets the visbility of the current layer. This will set the visibility style attribute of the element.
		 *
		 * @param {bool} s Visibility state true/false if it should be visible.
		 * @return {bool} Input state.
		 */
		setVisible : function(s) {
			this.setStyle('visibility', s ? 'visible' : 'hidden');
			return s;
		},

		/**
		 * Returns true/false if the layer is visible or not.
		 *
		 * @return true/false if it's visible or not.
		 * @type boolean
		 */
		isVisible : function() {
			return this.getStyle('visibility') == 'visible';
		},

		/**
		 * Returns the DOM element that the layer is binded to.
		 *
		 * @return DOM HTML element.
		 * @type HTMLElement
		 */
		getElement : function() {
			//if (!this.element)
			//	this.element = document.getElementById(this.id);

			return document.getElementById(this.id);
		},

		getEl : function() {
			return this.getElement();
		},

		/**
		 * Sets the block mode. If you set this property to true a control box blocker iframe
		 * will be added to the document since MSIE has a issue where select boxes are visible
		 * through layers.
		 *
		 * @param {boolean} s Block mode state, true is the default value.
		 */
		setBlockMode : function(s, u) {
			b = this.getBlocker();

			this.blockMode = s;

			if (u) {
				b.style.left = this.x + 'px';
				b.style.top = this.y + 'px';
				b.style.width = this.w + 'px';
				b.style.height = this.h + 'px';
			}
		},

		/**
		 * Updates the internal rect with the values from the DOM element.
		 */
		updateRect : function(f) {
			if (this.x == null || f)
				DOM.getRect(this.getElement(), true, this);
		},

		/**
		 * Updates the layers DOM element to the internal rectangle cordinates.
		 * This method will also update the blocker iframe element in IE if that feature is enabled.
		 */
		update : function() {
			var e = this.getElement(), b;

			if (this.x != this.lastRect.x)
				e.style.left = this.x + 'px';

			if (this.y != this.lastRect.y)
				e.style.top = this.y + 'px';

			if (this.w >= 0 && this.w != this.lastRect.w && this.w != null)
				e.style.width = this.w + 'px';

			if (this.h >= 0 && this.h != this.lastRect.h && this.h != null)
				e.style.height = this.h + 'px';

			if (this.blockMode) {
				b = this.getBlocker();

				if (this.x != this.lastRect.x)
					b.style.left = this.x + 'px';

				if (this.y != this.lastRect.y)
					b.style.top = this.y + 'px';

				if (this.w != this.lastRect.w && this.w != null)
					b.style.width = this.w + 'px';

				if (this.h != this.lastRect.h && this.h != null)
					b.style.height = this.h + 'px';

				b.style.zIndex = (parseInt(this.getEl().style.zIndex) - 1);
				b.style.display = e.style.display;
			}

			this.lastRect.set(this.x, this.y, this.w, this.h);
		},

		/**
		 * Returns the blocker DOM element, this is a invisible iframe.
		 *
		 * @return DOM HTML element.
		 * @type HTMLElement
		 */
		getBlocker : function() {
			var d, b;

			if (this.blockMode) {
				d = document;
				b = d.getElementById(this.id + "_blocker");

				if (!b) {
					b = d.createElement("iframe");

					b.setAttribute('id', this.id + "_blocker");
					b.style.cssText = 'display: none; position: absolute; left: 0; top: 0;';
					b.src = 'about:blank';
					b.frameBorder = '0';
					b.scrolling = 'no';

					d.body.appendChild(b);
				}

				return b;
			}

			return null;
		},

		/**
		 * Returns true/false if a element exists for the layer.
		 * 
		 * @return true/false if a element exists for the layer.
		 * @type boolean
		 */	 	
		exists : function() {
			return document.getElementById(this.id) != null;
		},

		/**
		 * Parses a int value this method will return 0 if the string is empty.
		 *
		 * @param {string} s String to parse value of.
		 * @return Parsed number.
		 * @type int
		 */
		parseInt : function(s) {
			if (s == null || s == '')
				return 0;

			return parseInt(s);
		},

		/**
		 * Removes the element for the layer from DOM and also the blocker iframe.
		 */
		remove : function() {
			var e = this.getElement(), b = this.getBlocker();

			if (e)
				e.parentNode.removeChild(e);

			if (b)
				b.parentNode.removeChild(b);
		}
	});

	/**#@-*/
});
