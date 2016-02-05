/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

mox.require([
	'mox.DOM',
	'mox.lang.LangPack',
	'mox.tpl.Template'
], function() {
	// Shorten class names
	var DOM = mox.DOM;
	var LangPack = mox.lang.LangPack;

	/**#@+
	 * @member mox.ui.WindowManager
	 * @abstract
	 */
	mox.create('mox.tpl.Paging:mox.tpl.Template', {
		settings : null,

		Paging : function(s) {
			this.settings = s;
		},

		render : function(p, np, t) {
			var h = '', s = this.settings, i, d;

			d = {
				page : p,
				vpage : p + 1,
				pages : np,
				vpages : np + 1,
				next : p + 1,
				vnext : p + 2,
				prev : p - 1,
				vprev : p - 2
			};

			if (p == 0)
				h += s.prev_inactive;
			else
				h += s.prev;

			for (i=0; i<np; i++) {
				d.page = i;
				d.vpage = i + 1;

				if (i == p) {
					h += this.replace(s.page_inactive, d);
				} else
					h += this.replace(s.page, d);
			}

			if (p == np - 1)
				h += s.next_inactive;
			else
				h += s.next;

			h = this.replace(h, d);
			h = LangPack.translate(h);

			DOM.get(t).innerHTML = h;
		}

		/**#@-*/
	});
});
