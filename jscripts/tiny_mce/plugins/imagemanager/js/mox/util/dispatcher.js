/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

mox.require([
	'mox.List'
], function() {
	mox.create('mox.util.Dispatcher', {
		_listeners : null,
		_scope : null,

		Dispatcher : function(s) {
			this._listeners = [];
			this._scope = s;
		},

		add : function(f, s) {
			this._listeners.push({
				func : f,
				scope : !s ? this._scope : s
			});
		},

		dispatch : function() {
			var a = arguments, s = true;

			mox.List.each(this._listeners, function(i, v) {
				return s = v.func.apply(!v.scope ? window : v.scope, a);
			});

			return s;
		}
	});
});
