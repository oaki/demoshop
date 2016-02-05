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
	'mox.List'
], function() {
	mox.create('mox.ui.MenuItem', {
		MenuItem : function(t, u, e) {
			this._text = t;
			this._url = u;
			this._extra = e;
			this._items = [];
			this._collapsed = false;
		},

		getText : function() {
			return this._text;
		},

		setText : function(v) {
			this._text = v;
		},

		getURL : function() {
			return this._url;
		},

		setURL : function(v) {
			this._url = v;
		},

		getExtra : function() {
			return this._extra;
		},

		setExtra : function(v) {
			this._extra = v;
		},

		expand : function(d) {
		},

		collapse : function(d) {
		},

		isCollapsed : function() {
			return this._collapsed;
		},

		getItems : function() {
			return this._items;
		},

		appendItem : function(n) {
			this._items.push(n);

			return n;
		},

		insertBefore : function(n, r) {
		},

		removeItem : function(n) {
			var i = List.indexOf(this._items, n);

			if (i != -1)
				this._items.splice(i, 1);
		}
	});
});
