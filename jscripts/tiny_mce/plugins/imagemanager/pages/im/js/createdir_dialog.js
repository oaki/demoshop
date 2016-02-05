/**
 * $Id: dialog.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

mox.require([
	'mox.Event',
	'mox.DOM',
	'mox.geom.Point',
	'mox.geom.Rect',
	'mox.List',
	'mox.lang.LangPack',
	'mox.tpl.Template',
	'mox.data.ResultSet',
	'mox.dom.Form',
	'mox.net.JSON',
	'mox.util.Dispatcher',
	'moxiecode.manager.BaseManager',
	'moxiecode.manager.ImageManager'
], function() {
	// Shorten class names
	var Event = mox.Event;
	var DOM = mox.DOM;
	var LangPack = mox.lang.LangPack;
	var Template = mox.tpl.Template;
	var ResultSet = mox.data.ResultSet;
	var Rect = mox.geom.Rect;
	var Form = mox.dom.Form;

	// Class that contains all theme specific logic
	mox.create('static CreateDirDialog', {
		lastWin : null,

		init : function() {
			this.lastWin = this.getWindowManager().getLastWindow();
			this.args = this.lastWin.getArgs();

			Event.add(window, 'DOMContentLoaded', this.onDOMContentLoaded, this);
			Event.add(window, 'focus', this.lastWin.focus, this.lastWin);

			mcImageManager.onError.add(this.onError, this);
		},

		onError : function(m, e, r, co, text) {
			var doc, WindowManager = this.getWindowManager();

			if (typeof(e) == 'object') {
				doc = this.getParent().document;

				if (e.level == 'AUTH') {
					if (r.login_url.indexOf('return_url') != -1)
						doc.location = r.login_url;
					else if (r.login_url.indexOf('://') == -1)
						doc.location = mox.baseURL + "/../" + r.login_url + "?return_url=" + escape(doc.location);
					else
						doc.location = r.login_url + "?return_url=" + escape(doc.location);

					return;
				}

				WindowManager.error('Fatal error: ' + e.errstr + "\n" + e.errfile + (e.errline ? "\nLine: " + e.errline : ''));
			} else {
				if (e.indexOf('JSON failure') != -1)
					WindowManager.status(e, mox.String.xmlEncode(mox.String.stripTags('' + text)));
				else
					WindowManager.error(e);
			}
		},

		getParent : function() {
			return parent || opener;
		},

		refreshList : function() {
			this.getParent().setTimeout('ImageManagerTheme.refresh()', 100);
		},

		getWindowManager : function() {
			return this.getParent().mox.ui.WindowManager;
		},

		onDOMContentLoaded : function() {
			var win = this.getParent(), t = this;

			LangPack.translatePage();

			this.lastWin.setTitle(document.title);

			DOM.get('template').disabled = true;
			DOM.get('createin').innerHTML = win.ImageManagerTheme.visualPath;

			mcImageManager.execRPC('im.getConfig', {path : win.ImageManagerTheme.path}, function(r) {
				var config = r.result, templates, n, force;

				templates = config['filesystem.directory_templates'];
				force = config['filesystem.force_directory_template'] == "true";

				if (templates.replace(/\s+/g, '') != '')
					DOM.get('template').disabled = false;
				else {
					DOM.hide('templaterow');
					DOM.show('content');
					return;
				}

				templates = t.splitNameValue(templates, function(v) {
					return v.substring(v.lastIndexOf('/') + 1);
				});

				if (force) {
					new Template('template', 'template_template', {}).processNameValue(templates);
					DOM.hide('templaterow');
				} else {
					new Template('template', 'template_template', {
						prepend_str : '<option value="">' + LangPack.get('createdir', 'select_template') + '</option>'
					}).processNameValue(templates);
				}

				DOM.show('content');
			});

			Event.add('cancel', 'click', function() {
				this.close();
			}, this);

			// Fix for odd focus bug in IE
			if (mox.isIE)
				window.focus();
		},

		close : function() {
			// Fix for odd focus bug in IE6
			try {document.body.appendChild(document.createElement('input')).focus();} catch (e) {}
			this.getWindowManager().closeAll();
		},

		createDirAction : function(f) {
			var t = this;

			if (t.getParent().ImageManagerTheme.checkDemo())
				return false;

			if (!f.dirname.value)
				return false;

			t.getWindowManager().showProgress(LangPack.get('createdir', 'progress'));
			mcImageManager.execRPC('im.createDirs', {path : this.args.path, name0 : f.dirname.value, template0 : f.template.options[f.template.selectedIndex].value}, function (d) {
				t.getWindowManager().hideProgress();

				if (!t.getParent().ImageManagerTheme.showStatusDialog(LangPack.get('error', 'createdir_failed'), new ResultSet(d.result))) {
					t.refreshList();
					t.close();
				}
			});

			return false;
		},

		splitNameValue : function(s, f) {
			var o = {}, i, c;

			s = s.split(',');

			for (i=0; i<s.length; i++) {
				c = s[i].split('=');

				// If no key, then call function to create one
				if (c.length < 2)
					f && (o[f(c[0])] = c[0]);
				else
					o[c[0]] = c[1];
			}

			return o;
		}
	});

	// Setup global image manager
	window.mcImageManager = new moxiecode.manager.ImageManager();

	CreateDirDialog.init();
});
