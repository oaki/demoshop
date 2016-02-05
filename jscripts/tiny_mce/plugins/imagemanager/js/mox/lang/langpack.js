/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

new function() {
	/**#@+
	 * @member mox.lang.LangPack
	 */
	mox.create('static mox.lang.LangPack', {
		_data : {},
		_code : 'en',
		_translated : false,

		/**#@+
		 * @method
		 */

		add : function(l, g, v) {
			var n;

			for (n in v)
				this._data[l + '.' + g + '.' + n] = v[n];
		},

		get : function(g, n, r) {
			var v = this._data[n], rn;

			v = this._data[this._code + '.' + g + '.' + n];
			v = v ? v : '{#' + g + '.' + n + '}';

			if (r) {
				for (rn in r)
					v = v.replace(new RegExp('/{' + rn + '}/', g), r);
			}

			return v;
		},

		translatePage : function(d) {
			var b, h;

			if (this._translated)
				return;

			this._translated = true;

			d = !d ? document : d;
			b = d.body;

			h = b.innerHTML;
			h = h.replace(new RegExp('=({#[a-z0-9_]+})', 'gi'), '="$1"'); // IE!!!

			b.innerHTML = this.translate(h, 1);
			d.title = this.translate(d.title.replace(/^(http|https):\/\/[a-z0-9._\-\s]+-/gi, ''));
		},

		translate : function(s, e) {
			var t = this;

			return s.replace(/\{#([^\}]+)\}/g, function(a, b) {
				a = t._data[t._code + '.' + b];
				a = a ? a : '{#' + b + '}';

				return e ? t.xmlEncode(a) : a;
			});
		},

		xmlEncode : function(s) {
			return s ? ('' + s).replace(new RegExp('[<>&"\']', 'g'), function (c, b) {
				switch (c) {
					case '&':
						return '&amp;';

					case '"':
						return '&quot;';

					case '\'':
						return '&#39;'; // &apos; is not working in MSIE

					case '<':
						return '&lt;';

					case '>':
						return '&gt;';
				}

				return c;
			}) : s;
		}
	});

	/**#@-*/
};
