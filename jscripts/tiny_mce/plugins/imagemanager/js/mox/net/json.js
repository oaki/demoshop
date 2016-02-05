/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

/**#@+
 * @class JSON RPC class. This class contains logic for parsing and serializing of JSON data and
 * JSON AJAX execution.
 * @member mox.ajax.JSON
 */
mox.create('static mox.net.JSON', {
	synchronous : false,
	debug : false,
	encoded : false,

	/**#@+
	 * @method
	 */

	/**
	 * Serializes the specified object as a JSON string.
	 *
	 * @param {Object} o Object to serialize as a JSON string.
	 * @return {string} JSON string serialized from input.
	 */
	serialize : function(o) {
		var i, v, s = mox.net.JSON.serialize, t;

		if (o == null)
			return 'null';

		t = typeof o;

		if (t == 'string') {
			v = '\bb\tt\nn\ff\rr\""\'\'\\\\';

			return '"' + o.replace(new RegExp('([\u0080-\uFFFF\\x00-\\x1f\\\\"])', 'g'), function(a, b) {
				i = v.indexOf(b);

				if (i + 1)
					return '\\' + v.charAt(i + 1);

				a = b.charCodeAt().toString(16);

				return '\\u' + '0000'.substring(a.length) + a;
			}) + '"';
		}

		if (t == 'object') {
			if (o instanceof Array) {
					for (i=0, v = '['; i<o.length; i++)
						v += (i > 0 ? ',' : '') + s(o[i]);

					return v + ']';
				}

				v = '{';

				for (i in o)
					v += typeof o[i] != 'function' ? (v.length > 1 ? ',"' : '"') + i + '":' + s(o[i]) : '';

				return v + '}';
		}

		return '' + o;
	},

	/**
	 * Unserializes/parses the specified JSON string into a object.
	 *
	 * @param {string} s JSON String to parse into a JavaScript object.
	 * @return {Object} Object from input JSON string or null if it failed.
	 */
	unserialize : function(s) {
		// Since Safari craches it is now more insecure
		if (!mox.isSafari) {
			if (!/^("(\\.|[^"\\\n\r])*?"|[,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t])+?$/.test(s))
				return 'INVALID_JSON_STRING';
		}

		try {
			return eval('(' + s + ')');
		} catch (ex) {
			return 'INVALID_JSON_STRING';
		}
	},

	/**
	 * Peforms AJAX or AJAT since JSON is plain text.
	 *
	 * @param {string} u URL to backend page.
	 * @param {Object} o Object to serialize as a JSON string.
	 * @param {function} r Response callback function.
	 * @param {function} i Optional initialization callback.
	 */
	send : function(u, o, r, e, i) {
		if (mox.net.JSON.synchronous)
			this.sendSync(u, o, r, e, i);
		else
			this.sendAsync(u, o, r, e, i);
	},

	/**
	 * Sends synchronous JSON call. Used for unit testing.
	 *
	 * @param {string} u URL to backend page.
	 * @param {Object} o Object to serialize as a JSON string.
	 * @param {function} r Response callback function.
	 * @param {function} i Optional initialization callback.
	 */
	sendSync : function(u, o, r, e, i) {
		var j = this, x;

		function g(s) {
			x = 0;

			try {
				x = new ActiveXObject(s);
			} catch (s) {
			}

			return x;
		};

		x = window.ActiveXObject ? g('Msxml2.XMLHTTP') || g('Microsoft.XMLHTTP') : new XMLHttpRequest();

		if (!x)
			return r('ERR_NO_XMLHTTP', null, x.responseText, u, o, x);

		!i || i(x);

		x.overrideMimeType && x.overrideMimeType('text/plain');
		x.open('POST', u, false);
		x.setRequestHeader('Content-Type', 'application/json');

		x.async = false;
		x.send(j.serialize(o));

		if (j.debug) {
			mox.log("RPC Request [JSON]: " + j.serialize(o));
			mox.log("RPC Response [OBJ]: ", j.unserialize(x.responseText));
			mox.log("RPC Response [JSON]: " + x.responseText);
		}

		// Response is not a valid JSON string
		c = j.unserialize(x.responseText);
		if (c == 'INVALID_JSON_STRING')
			return r('ERR_NOT_JSON', null, x.responseText, u, o, x);

		// Valid response exec callback
		r('OK', c, x.responseText, u, o, x);
	},

	sendAsync : function(u, o, r, e, i) {
		var j = this, x, p;

		function g(s) {
			x = 0;

			try {
				x = new ActiveXObject(s);
			} catch (s) {
			}

			return x;
		};

		x = window.ActiveXObject ? g('Msxml2.XMLHTTP') || g('Microsoft.XMLHTTP') : new XMLHttpRequest();

		if (!x)
			return cb('ERR_NO_XMLHTTP', null, x.responseText, u, o, x);

		!i || i(x);

		x.open('POST', u, true);

		if (!e && !mox.net.JSON.encoded) {
			x.overrideMimeType && x.overrideMimeType('text/plain');
			x.setRequestHeader('Content-Type', 'application/json');
			p = j.serialize(o);
		} else {
			p = "json_data=" + escape(j.serialize(o));
			x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			x.setRequestHeader("Content-length", p.length);
		}

		this.waitForResponse(u, o, x, r, e, i);
		x.send(p);
	},

	waitForResponse : function(u, o, x, cb, e, i) {
		var j = this, t, c = 0;

		t = window.setInterval(function() {
			if (x.readyState == 4 || c++ > 10000) {
				window.clearInterval(t);

				// Apache mod_security try again
				if (x.status == 403 || x.status == 404 || x.status == 405 || x.status == 500) {
					mox.net.JSON.encoded = 1;

					if (!e)
						j.sendAsync(u, o, cb, 1, i);

					return false;
				}

				if (x.readyState == 4) {
					if (j.debug) {
						mox.log("RPC Request [JSON]: " + j.serialize(o));
						mox.log("RPC Response [OBJ]: ", j.unserialize(x.responseText));
						mox.log("RPC Response [RAW]: " + x.responseText);
					}
				}

				// Request took to long time
				if (c > 10000)
					return cb('ERR_TIMEOUT', null, x.responseText, u, o, x);

				// Response is not a valid JSON string
				c = j.unserialize(x.responseText);
				if (c == 'INVALID_JSON_STRING')
					return cb('ERR_NOT_JSON', null, x.responseText, u, o, x);

				// Valid response exec callback
				cb('OK', c, x.responseText, u, o, x);
			}
		}, 10);
	}

	/**#@-*/
});
