/**#@+
 * @member mox.manager.BaseManager
 */
mox.require([
	'mox.util.Dispatcher',
	'mox.net.JSON',
	'mox.tpl.Template'
], function() {
	var Dispatcher = mox.util.Dispatcher;

	mox.create('moxiecode.manager.BaseManager', {
		settings : null,
		json : mox.net.JSON,
		rpcCounter : 0,
		args : null,
		callSettings : null,

		BaseManager : function(s) {
			var baseDir;

			// Get document base directory path
			baseDir = document.location.href;
			if (baseDir.indexOf('?') != -1)
				baseDir = baseDir.substring(0, baseDir.indexOf('?'));

			baseDir = baseDir.substring(0, baseDir.lastIndexOf('/') + 1);

			this.settings = {
				document_base_url : unescape(baseDir),
				relative_urls : false,
				remove_script_host : false,
				path : null,
				rootpath : null,
				remember_last_path : true,
				custom_data : null,
				target_elements : '',
				target_form : ''
			};

			this.onResult = new Dispatcher(this);
			this.onError = new Dispatcher(this);

			this.init(s);
		},

		init : function(s) {
			this.merge(s, this.settings);
		},

		merge : function(s, a) {
			var n, o = {};

			for (n in a)
				o[n] = a[n];

			if (s) {
				for (n in s)
					o[n] = s[n];
			}

			return o;
		},

		openInIframe : function(iframe_id, form_name, element_names, file_url, js, settings) {
			mox.log('openInIframe: ', iframe_id, form_name, element_names, file_url, js, settings);
		},

		open : function(form_name, element_names, file_url, js, settings) {
			mox.log('open: ', form_name, element_names, file_url, js, settings);
		},

		filebrowserCallBack : function(field_name, url, type, win) {
			var s;

			// Is file manager included, use that one on files
			if (typeof(mcFileManager) != "undefined" && type == "file") {
				mcFileManager.filebrowserCallBack(field_name, url, type, win);
				return;
			}

			// Save away
			this.field = field_name;
			this.callerWindow = win;
			this.inTinyMCE = true;

			// Setup instance specific settings
			s = {
				path : tinyMCE.getParam("imagemanager_path"),
				rootpath : tinyMCE.getParam("imagemanager_rootpath"),
				remember_last_path : tinyMCE.getParam("imagemanager_remember_last_path"),
				custom_data : tinyMCE.getParam("imagemanager_custom_data"),
				is_callback : true
			};

			// Open browser
			this.open(0, field_name, url, mox.bind(this, this.insertFileToTinyMCE), s);
		},

		insertFileToTinyMCE : function(url) {
			var url;

			// Handle old and new style
			if (typeof(TinyMCE_convertURL) != "undefined")
				url = TinyMCE_convertURL(url, null, true);
			else
				url = tinyMCE.convertURL(url, null, true);

			// Set URL
			this.callerWindow.document.forms[0].elements[this.field].value = url;

			// Try to fire the onchange event
			try {
				this.callerWindow.document.forms[0].elements[this.field].onchange();
			} catch (e) {
				// Skip it
			}
		},

		selectFile : function(d) {
			this.insertFileToForm(d.url, d);
		},

		insertFileToForm : function(url, d) {
			var s = this.callSettings, elements = s.target_elements.split(','), i, elm;

			if (s.insert_callback)
				s.insert_callback(url, d);

			// Convert to relative
			if (s.relative_urls)
				url = new mox.utils.URL(url).getRelativePath(s.document_base_url);

			// Remove proto and host
			if (s.remove_script_host)
				url = this.removeHost(url);

			// Set URL to all form fields
			for (i=0; i<elements.length; i++) {
				elm = document.forms[s.target_form].elements[elements[i]];

				if (elm && typeof elm != "undefined")
					elm.value = url;

				// Try to fire the onchange event
				try {
					elm.onchange();
				} catch (e) {
					// Skip it
				}
			}
		},

		removeHost : function(url) {
			return url.replace(/^(\w+:\/\/)([^\/]+)/, '');
		},

		getArgs : function() {
			return this.args;
		},

		/**
		 * ...
		 *
		 * @param {string} m Method name to execute.
		 * @param {Object} o Name/Value collection object of arguments.
		 * @param {function/Object} Function or object callback to execute when the RPC result is returned.
		 */
		execRPC : function(m, o, c, ec) {
			var ma = this, json = ma.json, s = ma.settings, co = ma.rpcCounter++;

			json.send(
				mox.baseURL + '/../rpc/' + (mox.defaultDoc ? mox.defaultDoc : ''),
				{ method : m, params : [o], id : 'c' + co },
				function(status, response, text) {
					var t, li;

					if (status != 'OK') {
						ma.onError.dispatch(m, 'Critical JSON failure: ' + status, null, co, text);
						return;
					}

					if (!response)
						return;

					if (response.result)
						ma.onResult.dispatch(m, response.result, co, text);

					if (response.error) {
						if (!ec)
							ma.onError.dispatch(m, response.error, response.result, co, text);
						else
							ec(response);

						return;
					}

					switch (typeof(c)) {
						case 'function':
							c(response, o);
							break;

						case 'object':
							c.processTable(response);
							break;

						case 'string':
							t = new mox.template.GridFill(c);
							t.process(response);
							break;
					}
				}
			);
		},

		setTitle : function(s) {
			var w = opener || parent, wm;

			try {
				if (w.tinymce) {
					wm = w.tinyMCE.activeEditor.windowManager;

					if (wm.setTitle) {
						wm.setTitle(s || document.title, wm.params['mce_window_id']);
						wm.setTitle(wm.params['mce_window_id'], s || document.title);
					}
				}
			} catch (ex) {
				 // Ignore firefox error

				 // At least log it
				 if (console && console.debug)
					 console.debug(ex);
			}
		},

		restoreSelection : function() {
			var w = opener || parent, ed;

			if (mox.isIE && w.tinymce) {
				ed = w.tinyMCE.activeEditor;
				ed.selection.moveToBookmark(ed.windowManager.bookmark);
			}
		},

		close : function() {
			var w = opener || parent, wm;

			// Close TinyMCE window
			if (w.tinymce) {
				wm = w.tinyMCE.activeEditor.windowManager;
				wm.close(window, wm.params['mce_window_id']);
			} else
				window.close();
		}

		/**#@-*/
	});
});
