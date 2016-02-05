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
mox.create('static mox.ui.Validator', {
	isEmail : function(s) {
		return this.test(s, '^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+@[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$');
	},

	isAbsUrl : function(s) {
		return this.test(s, '^(news|telnet|nttp|file|http|ftp|https)://[-A-Za-z0-9\\.]+\\/?.*$');
	},

	isSize : function(s) {
		return this.test(s, '^[0-9]+(px|%)?$');
	},

	isId : function(s) {
		return this.test(s, '^[A-Za-z_]([A-Za-z0-9_])*$');
	},

	isEmpty : function(s) {
		var nl, i;

		if (s.nodeName == 'SELECT' && s.options[s.selectedIndex].value == '')
			return true;

		if (s.type == 'checkbox' && !s.checked)
			return true;

		if (s.type == 'radio') {
			for (i=0, nl = s.form.elements; i<nl.length; i++) {
				if (nl[i].type == "radio" && nl[i].name == s.name && nl[i].checked)
					return false;
			}

			return true;
		}

		return new RegExp('^\\s*$').test(s.nodeType == 1 ? s.value : s);
	},

	isNumber : function(s, d) {
		return !isNaN(s.nodeType == 1 ? s.value : s) && (!d || !this.test(s, '^-?[0-9]*\\.[0-9]*$'));
	},

	isDateTime : function(s, f) {
		var l = new Array(), m, i, v, c;

		if (this.isEmpty(s = s.nodeType == 1 ? s.value : s))
			return true;

		f = f.replace(/[\.\*\?\-\[\]\{\}\/\\\x22\x27]/g, function (a, b) {return '\\' + a;});
		f = f.replace(/(YYYY|mm|dd|hh|ii|ss|HH|g|AA|aa)/g, function (a, b) {
			l.push(a);

			if (a == 'AA' || a == 'aa')
				return a == 'AA' ? '(AM|PM)' : '(am|pm)';

			return '([0-9]{' + (a == 'YYYY' ? 4 : (a == 'g' ? '1,2' : 2)) + '})'
		});

		for (i=1, c=0, m = new RegExp('^' + f + '$').exec(s); m && i<m.length; i++) {
			r = l[i-1];
			v = parseInt(m[i]);

			c += r == 'mm' && (v < 1 || v > 12);
			c += r == 'dd' && (v < 1 || v > 31);
			c += r == 'HH' && v > 23;
			c += (r == 'hh' || r == 'g') && (v < 1 || v > 12);
			c += (r == 'ii' || r == 'ss') && (v < 0 || v > 59);
		}

		return m && c < 1;
	},

	test : function(s, p) {
		s = s.nodeType == 1 ? s.value : s;

		return s == '' || new RegExp(p).test(s);
	}

	/**#@-*/
});
