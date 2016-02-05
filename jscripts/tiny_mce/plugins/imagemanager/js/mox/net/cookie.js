/**
 * $Id: validate.js 18 2006-06-29 14:11:23Z spocke $
 *
 * Various form validation methods.
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

/**
 * @class This class contains common event handling logic.
 * @member mox.utils.Cookie
 * @abstract
 */
mox.require([
	'mox.net.JSON'
], function() {
	var JSON = mox.net.JSON;

	mox.create('static mox.net.Cookie', {
		setJSON : function(name, value, expires, path, domain, secure) {
			this.set(name, JSON.serialize(value), expires, path, domain, secure);
		},

		getJSON : function(name) {
			return JSON.unserialize(this.get(name));
		},

		set : function(name, value, expires, path, domain, secure) {
			var date = new Date();

			date.setTime(date.getTime() - 1000);

			this._set(name, value, date, path, domain, secure);
			this._set(name, value, expires, path, domain, secure);
		},

		get : function(name) {
			var dc = document.cookie;
			var prefix = name + "=";
			var begin = dc.indexOf("; " + prefix);

			if (begin == -1) {
				begin = dc.indexOf(prefix);

				if (begin != 0)
					return null;
			} else
				begin += 2;

			var end = document.cookie.indexOf(";", begin);

			if (end == -1)
				end = dc.length;

			return unescape(dc.substring(begin + prefix.length, end));
		},

		_set : function(name, value, expires, path, domain, secure) {
			document.cookie = name + "=" + escape(value) +
				((expires) ? "; expires=" + expires.toGMTString() : "") +
				((path) ? "; path=" + escape(path) : "") +
				((domain) ? "; domain=" + domain : "") +
				((secure) ? "; secure" : "");
		}
	});
});
