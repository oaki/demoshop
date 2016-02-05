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
	var List = mox.List;

	mox.create('mox.ui.Menu:mox.ui.MenuItem', {
		Menu : function(id, s) {
			this.parent();

			this._id = id;
			this._settings = {
			};
		},

		addList : function(id) {
		},

		addArray : function(a, te) {
			var i, n;

			if (!te)
				te = this;

			//console.debug(te);

			if (te.length) {
				te = te.appendItem(this.createItem(a[0], a[1], a[2]));

				for (i=2; i<a.length; i++)
					this.addArray(te, a[i]);
			}
		},

		createItem : function(t, u, i) {
			return new mox.ui.MenuItem(t, u, i);
		},

		render : function() {
		}
	});
});
