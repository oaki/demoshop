/**
 * $Id: validate.js 18 2006-06-29 14:11:23Z spocke $
 *
 * Various form validation methods.
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

mox.require([
	'mox.ui.Validator'
], function() {
	// Shorten class names
	var Validator = mox.ui.Validator;

	/**#@+
	 * @member mox.utils.AutoValidator
	 * @abstract
	 */
	mox.create('static mox.ui.AutoValidator', {
		customTypes : new Array(),
		settings : {
			id_cls : 'id',
			int_cls : 'int',
			url_cls : 'url',
			number_cls : 'number',
			email_cls : 'email',
			size_cls : 'size',
			required_cls : 'required',
			invalid_cls : 'invalid',
			min_cls : 'min',
			max_cls : 'max',
			confirm_cls : 'confirm',
			confirmmsg_cls : 'confirmmsg',
			notconfirmed_cls : 'notconfirmed',
			date_cls : 'date',
			time_cls : 'time',
			datetime_cls : 'datetime',
			date_format : 'YYYY-mm-dd',
			time_format : 'HH:ii',
			datetime_format : 'YYYY-mm-dd HH:ii'
		},

		init : function(s) {
			var n;

			for (n in s)
				this.settings[n] = s[n];
		},

		addCustomType : function(c, f) {
			this.customTypes.push({cls : c, func : f});
		},

		validate : function(f) {
			var i, nl, s = this.settings, c = 0;

			nl = this.tags(f, 'label');
			for (i=0; i<nl.length; i++)
				this.removeClass(nl[i], s.invalid_cls);

			c += this.markGroups(f);
			c += this.validateElms(f, 'input');
			c += this.validateElms(f, 'select');
			c += this.validateElms(f, 'textarea');

			return c == 3;
		},

		invalidate : function(n) {
			this.mark(n.form, n);
		},

		reset : function(e) {
			var t = new Array('label', 'input', 'select', 'textarea');
			var i, j, nl, s = this.settings;

			if (e == null)
				return;

			for (i=0; i<t.length; i++) {
				nl = this.tags(e.form ? e.form : e, t[i]);
				for (j=0; j<nl.length; j++) {
					this.removeClass(nl[j], s.invalid_cls);
					this.removeClass(nl[j], s.notconfirmed_cls);
				}
			}
		},

		markGroups : function(f) {
			var nl, i, x, nx, s = this.settings, st, os = 0;

			nl = this.tags(f, 'div');
			for (i=0; i<nl.length; i++) {
				st = 0;

				if (this.hasClass(nl[i], s.required_cls)) {
					if (this.hasClass(nl[i], s.required_cls)) {
						nx = this.tags(nl[i], 'input');

						for (x=0; x<nx.length; x++) {
							if (nx[x].checked || !Validator.isEmpty(nx[x])) {
								st = 1;
								break;
							}
						}
					}

					if (!st)
						os += !this.mark(f, nl[i]);
				}
			}

			return os;
		},

		validateElms : function(f, e) {
			var nl, i, n, s = this.settings, ct = this.customTypes, st = true, va = Validator, v, x;

			nl = this.tags(f, e);
			for (i=0; i<nl.length; i++) {
				n = nl[i];

				this.removeClass(n, s.invalid_cls);

				if (this.hasClass(n, s.required_cls) && va.isEmpty(n))
					st = this.mark(f, n);

				if (this.hasClass(n, s.notconfirmed_cls)) {
					this.removeClass(n, s.confirm_cls);
					this.removeClass(n, s.notconfirmed_cls);
					this.markLabels(f, n, '', s.notconfirmed_cls);
				}

				if (this.hasClass(n, s.confirm_cls) && va.isEmpty(n)) {
					this.addClass(n, s.notconfirmed_cls);
					st = this.markLabels(f, n, s.notconfirmed_cls, 0, s.confirmmsg_cls);
				}

				if (this.hasClass(n, s.number_cls) && !va.isNumber(n))
					st = this.mark(f, n);

				if (this.hasClass(n, s.int_cls) && !va.isNumber(n, true))
					st = this.mark(f, n);

				if (this.hasClass(n, s.url_cls) && !va.isAbsUrl(n))
					st = this.mark(f, n);

				if (this.hasClass(n, s.email_cls) && !va.isEmail(n))
					st = this.mark(f, n);

				if (this.hasClass(n, s.size_cls) && !va.isSize(n))
					st = this.mark(f, n);

				if (this.hasClass(n, s.id_cls) && !va.isId(n))
					st = this.mark(f, n);

				if (this.hasClass(n, s.min_cls, true)) {
					v = this.getNum(n, s.min_cls);

					if (isNaN(v) || parseInt(n.value) < parseInt(v))
						st = this.mark(f, n);
				}

				if (this.hasClass(n, s.max_cls, true)) {
					v = this.getNum(n, s.max_cls);

					if (isNaN(v) || parseInt(n.value) > parseInt(v))
						st = this.mark(f, n);
				}

				if (this.hasClass(n, s.date_cls) && !va.isDateTime(n, s.date_format))
					st = this.mark(f, n);

				if (this.hasClass(n, s.time_cls) && !va.isDateTime(n, s.time_format))
					st = this.mark(f, n);

				if (this.hasClass(n, s.datetime_cls) && !va.isDateTime(n, s.datetime_format))
					st = this.mark(f, n);

				for (x=0; x<ct.length; x++) {
					if (this.hasClass(n, ct[x].cls) && !ct[x].func(n))
						st = this.mark(f, n);
				}
			}

			return st;
		},

		hasClass : function(n, c, d) {
			return new RegExp('\\b' + c + (d ? '[\\-?0-9]+' : '') + '\\b', 'g').test(n.className);
		},

		getNum : function(n, c) {
			c = n.className.match(new RegExp('\\b' + c + '\\-?([0-9]+)\\b', 'g'))[0];
			c = c.replace(/[^0-9\-]/g, '');

			return c;
		},

		addClass : function(n, c, b) {
			var o = this.removeClass(n, c);
			n.className = b ? c + (o != '' ? (' ' + o) : '') : (o != '' ? (o + ' ') : '') + c;
		},

		removeClass : function(n, c) {
			c = n.className.replace(new RegExp("(^|\\s+)" + c + "(\\s+|$)"), ' ');
			return n.className = c != ' ' ? c : '';
		},

		tags : function(f, s) {
			return f.getElementsByTagName(s);
		},

		mark : function(f, n) {
			var s = this.settings;

			this.addClass(n, s.invalid_cls);
			this.markLabels(f, n, s.invalid_cls);

			return false;
		},

		markLabels : function(f, n, ic, rc, hc) {
			var nl, i;

			nl = this.tags(f, "label");

			for (i=0; i<nl.length; i++) {
				if ((nl[i].getAttribute("for") == n.id || nl[i].htmlFor == n.id) && (!hc || this.hasClass(nl[i], hc))) {
					!rc || this.removeClass(nl[i], rc);
					!ic || this.addClass(nl[i], ic);
				}
			}

			return false;
		}

		/**#@-*/
	});
});
