/**
 * $Id: validate.js 18 2006-06-29 14:11:23Z spocke $
 *
 * Various form validation methods.
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

/**#@+
 * @member mox.utils.Validator
 * @abstract
 */
mox.require([
	'mox.List',
	'mox.DOM',
	'mox.util.Dispatcher'
], function() {
	var DOM = mox.DOM;
	var List = mox.List;
	var Dispatcher = mox.util.Dispatcher;

	mox.create('mox.ui.DropMenuItem:mox.ui.MenuItem', {
		DropMenuItem : function(t, u, e) {
			this.parent(t, u, e);

			if (e) {
				this._id = e.id ? e.id : null;
			}

			this.onShow = new Dispatcher();
			this.onHide = new Dispatcher();
		},

		getID : function() {
			return this._id;
		},

		setID : function(v) {
			return this._id = v;
		},

		appendItem : function(n) {
			n._parent = this;
			this.parent(n);

			return n;
		},

		addClass : function(c) {
			DOM.addClass(this._id, c);
		},

		removeClass : function(c) {
			DOM.removeClass(this._id, c);
		},

		hasClass : function(c) {
			return !DOM.hasClass(this._id, c);
		},

		show : function(x, y) {
			if (this.onShow.dispatch() !== false) {
				if (typeof(x) != 'undefined')
					this.moveTo(x, y);

				DOM.show(this._id);
			}
		},

		moveTo : function(x, y) {
			DOM.setStyles(this._id, { left : x + 'px', top : y + 'px' });
		},

		hide : function() {
			if (this.onHide.dispatch() !== false)
				DOM.hide(this._id);
		},

		isFirst : function() {
			return this._parent.getItems()[0] == this;
		},

		expand : function(d) {
			this.removeClass('MenuHidden');
		},

		collapse : function(d) {
			this.addClass('MenuHidden');
		},

		getRect : function() {
			var o, r, b;

			o = DOM.getStyle(this._id, 'opacity');
			b = DOM.getStyle(this._id, 'display');

			DOM.setStyle(this._id, 'opacity', 0);
			DOM.show(this._id);

			r = DOM.getRect(this._id, true);

			DOM.setStyle(this._id, 'opacity', o);

			if (b)
				DOM.setStyle(this._id, 'display', b);

			return r;
		}
	});
});