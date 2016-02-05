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
 * @member mox.utils.Validator
 * @abstract
 */
mox.create('mox.net.URL', {
	protocol : null,
	username : null,
	password : null,
	host : null,
	port : null,
	domain : null,
	path : null,
	query : null,
	hash : null,

	URL : function(u) {
		var l = document.location;

		// Force absolute
		if (u.indexOf('://') == -1) {
			if (u.indexOf('../') != -1)
				u = this.relativeToAbsolutePath(l.pathname, u);

			u = l.protocol + '//' + l.host + (l.port ? ':' + l.port : '') + u;
		}

		u = /^((\w+):\/\/)?((\w+):?(\w+)?@)?([^\/\?:]+):?(\d+)?(\/?[^\?#]+)?\??([^#]+)?#?(\w*)/.exec(u);

		this.protocol = u[2];
		this.username = u[3];
		this.password = u[4];
		this.host = u[5];
		this.port = u[6];
		this.domain = u[7];
		this.path = u[8];
		this.query = u[9];
		this.hash = u[10];
	},

	getRelativePath : function(base_url) {
		var strTok1, strTok2, breakPoint = 0, outputString = "";

		base_url = new mox.utils.URL(base_url).path;

		// Crop away last path part
		base_url = base_url.substring(0, base_url.lastIndexOf('/'));
		strTok1 = base_url.split('/');
		strTok2 = this.path.split('/');

		if (strTok1.length >= strTok2.length) {
			for (var i=0; i<strTok1.length; i++) {
				if (i >= strTok2.length || strTok1[i] != strTok2[i]) {
					breakPoint = i + 1;
					break;
				}
			}
		}

		if (strTok1.length < strTok2.length) {
			for (var i=0; i<strTok2.length; i++) {
				if (i >= strTok1.length || strTok1[i] != strTok2[i]) {
					breakPoint = i + 1;
					break;
				}
			}
		}

		if (breakPoint == 1)
			return this.path;

		for (var i=0; i<(strTok1.length-(breakPoint-1)); i++)
			outputString += "../";

		for (var i=breakPoint-1; i<strTok2.length; i++) {
			if (i != (breakPoint-1))
				outputString += "/" + strTok2[i];
			else
				outputString += strTok2[i];
		}

		return outputString;
	},

	relativeToAbsolutePath : function(base_path, rel_path) {
		var end = '';

		rel_path = rel_path.replace(/[?#].*$/, function(a) {
			end = a;

			return '';
		});

		// Split parts
		baseURLParts = base_path.split('/');
		relURLParts = rel_path.split('/');

		// Remove empty chunks
		var newBaseURLParts = new Array();
		for (var i=baseURLParts.length-1; i>=0; i--) {
			if (baseURLParts[i].length == 0)
				continue;

			newBaseURLParts[newBaseURLParts.length] = baseURLParts[i];
		}
		baseURLParts = newBaseURLParts.reverse();

		// Merge relURLParts chunks
		var newRelURLParts = new Array();
		var numBack = 0;
		for (var i=relURLParts.length-1; i>=0; i--) {
			if (relURLParts[i].length == 0 || relURLParts[i] == ".")
				continue;

			if (relURLParts[i] == '..') {
				numBack++;
				continue;
			}

			if (numBack > 0) {
				numBack--;
				continue;
			}

			newRelURLParts[newRelURLParts.length] = relURLParts[i];
		}

		relURLParts = newRelURLParts.reverse();

		// Remove end from absolute path
		var len = baseURLParts.length-numBack;
		var absPath = (len <= 0 ? "" : "/") + baseURLParts.slice(0, len).join('/') + "/" + relURLParts.join('/');

		return absPath + end;
	}
});
