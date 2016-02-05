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

	mox.create('mox.ui.DropMenu:mox.ui.DropMenuItem', {
		DropMenu : function(s) {
			this.parent();
			this._settings = s;
		},

		createItem : function(t, u, e) {
			return new mox.ui.DropMenuItem(t, u, e);
		},

		render : function() {
			var t = this, c, s = this._settings;

			c  = 'Menu';

			if (mox.isIE)
				c += ' isIE';

			if (s && s['class'])
				c += s['class'];

			document.body.appendChild(DOM.createTag('ul', {id : t.setID(DOM.uniqueId()), 'class' : c}));

			List.each(t.getItems(), function(i, n) {
				var e = n.getExtra(), li, id;

				c = 'MenuItem';

				if (n.isFirst())
					c += ' MenuItemFirst';

				if (e && e['class'])
					c += ' ' + e['class'];

				id = n.getID() ? n.getID() : n.setID(DOM.uniqueId());
				li = DOM.createTag('li', {id : id, 'class' : c});
				li.appendChild(DOM.createTag('a', {href : n.getURL()}, n.getText()));
				DOM.get(t._id).appendChild(li);
			});
		}
	});
});