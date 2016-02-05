/**
 * $Id: editor_plugin_src.js 42 2006-08-08 14:32:24Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright � 2004-2007, Moxiecode Systems AB, All rights reserved.
 */

(function() {
	function getParam(n, d) {
		if (tinyMCE.getParam)
			return tinyMCE.getParam(n, d);
		else
			return tinyMCE.activeEditor.getParam(n, d);
	};

	var TinyMCE_ImageManagerPlugin = {
		getInfo : function() {
			return {
				longname : 'MCImageManager PHP',
				author : '',
				authorurl : '',
				infourl : '',
				version : "3.0.8"
			};
		},

		initInstance : function(inst) {
			inst.settings['file_browser_callback'] = 'mcImageManager.filebrowserCallBack';
			mcImageManager.settings.handle = getParam('imagemanager_handle', mcImageManager.settings.handle);
		},

		getControlHTML : function(cn) {
			switch (cn) {
				case "insertimage":
					return tinyMCE.getButtonHTML(cn, 'lang_imagemanager_insertimage_desc', '{$pluginurl}/pages/im/img/insertimage.gif', 'mceInsertImage', false);
			}

			return "";
		},

		execCommand : function(editor_id, element, command, user_interface, value) {
			var inst = tinyMCE.getInstanceById(editor_id), nl, i, t = this, s, h;

			switch (command) {
				case "mceInsertImage":
					s = {
						path : getParam("imagemanager_path"),
						rootpath : getParam("imagemanager_rootpath"),
						remember_last_path : getParam("imagemanager_remember_last_path"),
						custom_data : getParam("imagemanager_custom_data"),
						insert_filter : getParam("imagemanager_insert_filter")
					};

					mcImageManager.open(0, '', '', function(url, info) {
						var ci = info.custom;

						if (!ci.thumbnail_url) {
							ci.thumbnail_url = url;
							ci.twidth = ci.width;
							ci.theight = ci.height;
						}

						h = t._replace(
							getParam('imagemanager_insert_template', '<a href="{$url}" mce_href="{$url}" rel="lightbox"><img src="{$custom.thumbnail_url}" mce_src="{$custom.thumbnail_url}" width="{$custom.twidth}" height="{$custom.theight}" /></a>'),
							info,
							{
								urlencode : function(v) {
									return escape(v);
								},

								xmlEncode : function(v) {
									if (tinyMCE.xmlEncode)
										return tinyMCE.xmlEncode(v);
									else
										return tinymce.DOM.encode(v);
								}
							}
						);

						if (tinyMCE.storeAwayURLs)
							h = tinyMCE.storeAwayURLs(h);

						inst.execCommand('mceInsertContent', false, h);
					}, s);

					return true;
			}

			return false;
		},

		/* Plugin internal functions */

		_init2x : function() {
			var tm = window['realTinyMCE'] || tinyMCE;

			this._loadScript(tm.baseURL + '/plugins/imagemanager/js/mcimagemanager.js');

			// Load language pack only with new compressor or no compressor at all
			if (!window['realTinyMCE'])
				this._loadScript(tm.baseURL + '/plugins/imagemanager/language/index.php?type=im&format=tinymce&group=tinymce&prefix=imagemanager_');

			tinyMCE.addPlugin("imagemanager", TinyMCE_ImageManagerPlugin);
		},

		_init3x : function() {
			var p = TinyMCE_ImageManagerPlugin, base = (tinymce.PluginManager.urls['imagemanager'] || (tinymce.baseURL + '/plugins/imagemanager'));

			tinymce.ScriptLoader.load(base + '/js/mcimagemanager.js');
			tinymce.ScriptLoader.load(base + '/language/index.php?type=im&format=tinymce_3_x&group=tinymce&prefix=imagemanager_');

			tinymce.create('tinymce.plugins.ImageManagerPlugin', {
				ImageManagerPlugin : function(ed, url) {
					ed.onInit.add(function() {
						p.initInstance(ed);
					});

					ed.addCommand('mceInsertImage', function(u, v) {
						p.execCommand(ed.id, 0, 'mceInsertImage', u, v);
					});

					ed.addButton('insertimage', {
						title : 'imagemanager_insertimage_desc',
						cmd : 'mceInsertImage',
						image : base + '/pages/im/img/insertimage.gif'
					});
				},

				getInfo : function() {
					return TinyMCE_ImageManagerPlugin.getInfo();
				}
			});

			tinymce.PluginManager.add('imagemanager', tinymce.plugins.ImageManagerPlugin);
		},

		_loadScript : function(u) {
			var s, d = document;

			/*s = d.createElement('script');
			s.setAttribute('type', 'text/javascript');
			s.setAttribute('src', u);
			d.getElementsByTagName('head')[0].appendChild(s);*/
			document.write('<script type="text/javascript" src="' + u + '"></script>');
		},

		_replace : function(t, d, e) {
			var i, r;

			function get(d, s) {
				for (i=0, r=d, s=s.split('.'); i<s.length; i++)
					r = r[s[i]];

				return r;
			};

			// Replace variables
			t = '' + t.replace(/\{\$([^\}]+)\}/g, function(a, b) {
				var l = b.split('|'), v = get(d, l[0]);

				// Default encoding
				if (l.length == 1 && e && e.xmlEncode)
					v = e.xmlEncode(v);

				// Execute encoders
				for (i=1; i<l.length; i++)
					v = e[l[i]](v, d, b);

				return v;
			});

			// Execute functions
			t = t.replace(/\{\=([\w]+)([^\}]+)\}/g, function(a, b, c) {
				return get(e, b)(d, b, c);
			});

			return t;
		}
	};

	if (tinyMCE.majorVersion)
		TinyMCE_ImageManagerPlugin._init2x();
	else
		TinyMCE_ImageManagerPlugin._init3x();
})();
