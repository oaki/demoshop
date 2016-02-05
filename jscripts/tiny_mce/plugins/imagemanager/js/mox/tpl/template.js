/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

mox.require([
	'mox.DOM',
	'mox.List'
], function() {
	var DOM = mox.DOM;
	var List = mox.List;

	function isset(o) {
		return typeof(o) != 'undefined';
	};

	/**#@+
	 * @member mox.mte.Template
	 */
	mox.create('mox.tpl.Template', {
		targetId : null,
		templateId : null,
		templates : null,
		encoders : null,
		settings : null,

		Template : function(t, tpl, s) {
			var e, n;

			// Setup target id and template id
			this.targetId = t;
			this.templateId = tpl;
			this.templates = {};

			// Defualt settings
			this.settings = {
				prepend_str : '',
				append_str : ''
			};

			for (n in s)
				this.settings[n] = s[n];

			// Setup encoders
			e = this.encoders = {};
			e.urlencode = e.escape = escape;
			e.encodeURIComponent = encodeURIComponent;

			this.addEncoder('translate', function(v, r) {
				return mox.lang.LangPack.translate(v);
			});
		},

		addEncoder : function(n, f) {
			return this.encoders[n] = f;
		},

		process : function(d) {
			var h = '', tl = this.templates;

			this.setup();

			h += this.replace(tl.header, d);
			h += this.replace(tl.item, d);
			h += this.replace(tl.footer, d);

			this._setHTML(DOM.get(this.targetId), h);
		},

		processArray : function(d) {
			var h = '', t = this, tl = t.templates;

			t.setup();

			List.map(d, function(i, v) {
				h += t.replace(tl.header, v);
			});

			List.map(d, function(i, v) {
				h += t.replace(tl.item, v);
			});

			List.map(d, function(i, v) {
				h += t.replace(tl.footer, v);
			});

			t._setHTML(DOM.get(t.targetId), h);
		},

		processNameValue : function(d) {
			var h = '', tl = this.templates;

			this.setup();

			h += this.replace(tl.header, d);
			h += this.replaceNameValue(tl.item, d);
			h += this.replace(tl.footer, d);

			this._setHTML(DOM.get(this.targetId), h);
		},

		processTable : function(d) {
			var h = '', tl = this.templates;

			this.setup();

			h += this.replace(tl.header, d.result.header);
			h += this.replaceTable(tl.item, d.result.columns, d.result.data);
			h += this.replace(tl.footer, d, d.result.header);

			this._setHTML(DOM.get(this.targetId), h);
		},

		processResultSet : function(d) {
			var h = '', tl = this.templates;

			this.setup();

			h += this.replace(tl.header, d.header);
			h += this.replaceTable(tl.item, d.columns, d.data);
			h += this.replace(tl.footer, d, d.header);

			this._setHTML(DOM.get(this.targetId), h);
		},

		replaceNameValue : function(t, d) {
			var n, h = '';

			if (!t)
				return '';

			for (n in d) {
				// Prototype check
				if (typeof(d[n]) == 'string')
					h += this.replace(t, {name : n, value : d[n]});
			}

			return h;
		},

		replaceTable : function(t, c, d) {
			var i, te, h = '', o;

			if (!t)
				return '';

			for (i=0; i<d.length; i++)
				h += this.replace(t, this.makeRow(c, d[i], i, d.length));

			return h;
		},

		makeRow : function(c, d, idx, len) {
			var o = {}, i;

			o.$index = idx;
			o.$length = len;

			for (i=0; i<c.length; i++)
				o[c[i]] = d[i];

			return o;
		},

		replace : function(t, d) {
			var te = this, e = te.encoders;

			if (!t)
				return '';

			// Replace variables
			t = t.replace(/\{\$([^\}]+)\}/g, function(a, b) {
				var l = b.split('|'), i, v = l[0].indexOf('.') != -1 ? eval('d.'+ l[0]) : d[l[0]];

				// Default encoding
				if (l.length == 1)
					v = te.xmlEncode(v);

				// Execute encoders
				for (i=1; i<l.length; i++)
					v = e[l[i]](v, d);

				return v;
			});

			// Execute functions
			t = t.replace(/\{\=([^\}]+)\}/g, function(a, b) {
				return eval(b);
			});

			return t;
		},

		/**
		 * Encodes the string to raw XML entities. This will only convert the most common ones.
		 * For real entity encoding use the xmlEncode method of the Cleanup class.
		 *
		 * @param {string} s String to encode.
		 * @return XML Encoded string.
		 * @type string
		 */
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
		},

		getTemplateStr : function(id) {
			var tpe, h;

			tpe = DOM.get(id);

			// Clean up template
			h = tpe.innerHTML;
			h = h.replace(/(<!\[CDATA\[|\]\]>)/gi, '');

			return h;
		},

		setup : function() {
			var s = this.settings, tl = this.templates;

			if (!tl.header && s.header_tpl)
				tl.header = this.getTemplateStr(s.header_tpl);

			if (!tl.item)
				tl.item = this.getTemplateStr(this.templateId);

			if (!tl.footer && s.footer_tpl)
				tl.footer = this.getTemplateStr(s.footer_tpl);
		},

		_setHTML : function(e, h) {
			var n = e.nodeName, s = this.settings, m, te, nl, i;

			// Add header/footer
			h = s.prepend_str + h + s.append_str;

			// Remove some empty attributes
			h = h.replace(/(width|height)=\"\s*\"/gi, '');

			// Check if it's a tr then wrap it in a temporary table and clone the tr nodes
			// IE will fail if you set innerHTML on tbody or table and Opera will render it wrong.
			if (!mox.isGecko && h.match(/^\s*<tr/i)) {
				for (nl = e.childNodes, i=nl.length - 1; i>=0; i--)
					nl[i].parentNode.removeChild(nl[i]);

				te = document.createElement('div');
				te.innerHTML = '<table>' + h + '</table>';

				DOM.selectElements(te, 'tr', function(n) {
					e.appendChild(n.cloneNode(true));
					return false;
				});

				// Cleanup
				te = null;
				return;
			}

			// IE and Opera has issues with setting inner html on some elements
			if (!mox.isGecko && /(TABLE|SELECT)/.test(n))
				e.outerHTML = e.outerHTML.replace(new RegExp('<' + n + '([^<]+)>.*', 'i'), '<' + n + '$1>' + h + '</' + n + '>');
			else {
				// IE produces runtime error on some elements so we need to remove it using DOM
				if (mox.isIE && !h && /(TBODY)/.test(n)) {
					n = e.firstChild;

					if (n) {
						do {
							e.removeChild(n);
						} while ((n = n.nextSibling) != null);
					}
				} else
					e.innerHTML = h;
			}
		}
	});
});
