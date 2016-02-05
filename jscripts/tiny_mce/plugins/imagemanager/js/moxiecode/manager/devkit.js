/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

mox.require([
	'mox.DOM',
	'mox.Event'
], function() {
	// Shorten class names
	var DOM = mox.DOM;
	var Event = mox.Event;

	/**
	 * @class This class contains common event handling logic.
	 * @member mox.devkit.DevKit
	 * @static
	 */
	mox.create('static moxiecode.manager.DevKit', {
		_log : [],

		init : function(t) {
			this._type = t;

			Event.add(mox.isIE ? document : window, 'keyup', function(e) {
				// Ctrl+Shift+F8
				if (e.keyCode == 119 && e.ctrlKey && e.shiftKey) {
					this.show();
					return Event.cancel(e);
				}
			}, this);
		},

		show : function() {
			var ifr;

			if (this._opened)
				return;

			ifr = document.body.appendChild(DOM.createTag('iframe', {id : 'devkit', frameBorder : '0', 'class' : 'devkitdown', style : 'display: none;', src : mox.baseURL + '/moxiecode/manager/devkit/devkit.htm'}));
			DOM.importCSS(mox.baseURL + '/moxiecode/manager/devkit/css/page.css');

			// Enable JSON debugging
			if (mox.net && mox.net.JSON)
				mox.net.JSON.debug = true;

			this._opened = true;
		},

		log : function() {
			var d = moxiecode.manager.DevKit, a = arguments;

			if (!d._iframe) {
				if (d._log.length > 100)
					d._log = [];

				d._log.push(a);
			} else
				d._iframe.log(a);
		}
	});

	if (window.console && window.console.debug)
		mox.log = window.console.debug;
	else
		mox.log = moxiecode.manager.DevKit.log;

	// Patch in console debugger fake Firebug
	if (!window.console)
		window.console = {};

	if (!window.console.debug)
		window.console.debug = moxiecode.manager.DevKit.log;
});