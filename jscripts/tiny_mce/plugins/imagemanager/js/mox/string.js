/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

/**#@+
 * @class This class contains common event handling logic.
 * @member mox.String
 * @static
 */
mox.create('static mox.String', {
	_entities : {'&' : '&amp;', '"' : '', '\'' : '&#39;', '<' : '&lt;', '>' : '&gt;', '"' : '&quot;'},
	_xmlRe : /[<>&]/g,
	_xmlReQuot : /[<>&\"\']/g,

	/**#@+
	 * @method
	 */

	/**
	 * Encodes the string to raw XML entities. This will only convert the most common ones.
	 * For real entity encoding use the xmlEncode method of the Cleanup class.
	 *
	 * @param {string} s String to encode.
	 * @param {bool} sq Optional skip encode quotes.
	 * @return {string} XML Encoded string.
	 */
	xmlEncode : function(s, sq) {
		var l = this._entities;

		return s ? ('' + s).replace(!sq ? this._xmlReQuot : this._xmlRe, function(c, b) {
			return l[c];
		}) : s;
	},

	/**
	 * Decodes the string from XML entities into plain text.
	 *
	 * @param {string} s String to decode.
	 * @return {string} XML Decoded string.
	 */
	xmlDecode : function(s) {
		var e = document.createElement("div");

		if (!s)
			return s;

		e.innerHTML = s;

		return e.firstChild.nodeValue;
	},

	stripTags : function(s) {
		return !s ? s : s.replace(/<[^>]+>/g, '');
	}

	/**#@-*/
});