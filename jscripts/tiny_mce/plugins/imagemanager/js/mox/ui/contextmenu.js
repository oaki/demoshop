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
	'mox.ui.DropMenu',
	'mox.DOM',
	'mox.Event',
	'mox.util.Dispatcher'
], function() {
	var Event = mox.Event;
	var DOM = mox.DOM;

	mox.create('mox.ui.ContextMenu:mox.ui.DropMenu', {
		ContextMenu : function(s) {
			this.parent(s);
			this.onContextMenu = new mox.util.Dispatcher();
		},

		render : function() {
			var t = this;

			t.parent();
			t.hide();

			Event.add(document, 'contextmenu', t._showAt, this);
			Event.add(document, 'click', function(e) {
				// Ignore context menu
				if (e.button == 2)
					return;

				// For Opera
				if (mox.isOpera && e.altKey)
					return;

				t.hide();
			});
		},

		show : function(x, y) {
			var r1, r2, t = this, s = t._settings;

			t.parent(x, y);

			if (typeof(x) != 'undefined') {
				if (!s || !s.container)
					r1 = DOM.getViewPort();
				else
					r1 = DOM.getRect(s.container);

				r2 = t.getRect();
				r1.w -= 2;
				r1.h -= 2;

				if (!r1.contains(r2))
					t.moveTo(x, y - ((r2.y + r2.h) - (r1.y + r1.h)));
			}
		},

		_showAt : function(e) {
			var p = Event.getPageXY(e), t = this;

			if (this.onContextMenu.dispatch(e, p) !== false) {
				t.show(p.x + 5, p.y + 5);

				return Event.cancel(e);
			}
		}
	});
});