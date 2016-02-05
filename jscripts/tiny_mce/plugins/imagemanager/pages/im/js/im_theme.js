mox.require([
	'mox.Event',
	'mox.List',
	'mox.DOM',
	'mox.geom.Point',
	'mox.geom.Rect',
	'mox.String',
	'mox.dom.Drag',
	'mox.tpl.Template',
	'mox.tpl.Paging',
	'mox.data.ResultSet',
	'mox.net.Cookie',
	'mox.ui.WindowManager',
	'mox.ui.Menu',
	'mox.ui.MenuItem',
	'mox.ui.DropMenu',
	'mox.ui.DropMenuItem',
	'mox.dom.Layer',
	'mox.lang.LangPack',
	'mox.dom.Tween',
	'mox.net.JSON',
	'mox.dom.Form',
	'mox.util.Dispatcher',
	'moxiecode.manager.BaseManager',
	'moxiecode.manager.ImageManager',
	'moxiecode.manager.DevKit'
], function() {
	// Shorten class names
	var Event = mox.Event;
	var Template = mox.tpl.Template;
	var Paging = mox.tpl.Paging;
	var ResultSet = mox.data.ResultSet;
	var DOM = mox.DOM;
	var Cookie = mox.net.Cookie;
	var WindowManager = mox.ui.WindowManager;
	var Layer = mox.dom.Layer;
	var LangPack = mox.lang.LangPack;
	var Tween = mox.dom.Tween;
	var JSON = mox.net.JSON;
	var Form = mox.dom.Form;
	var List = mox.List;
	var DevKit = moxiecode.manager.DevKit;
	var DropMenu = mox.ui.DropMenu;

	function isset(o) {
		return typeof(o) != 'undefined';
	}

	// Class that contains all theme specific logic
	mox.create('static moxiecode.manager.ImageManagerDefaultTheme', {
		page : 0,
		thumbsPageSize : 25,
		textPageSize : 25,
		thumbsTemplate : null,
		path : '{default}',
		rootpath : null,
		view : 'thumbs',
		lastView : 'thumbs',
		config : null,
		paging : null,
		rememberPath : 'auto',
		filemanagerURL : null,
		filter : null,
		insertMode : null,
		keepAliveTime : 600000,
		editPath : null,

		init : function() {
			var win, args, dm;

			window.setTimeout('ImageManagerTheme.keepAlive();', this.keepAliveTime);

			// Setup menu
			this.menu = dm = new DropMenu();

			dm.appendItem(dm.createItem(LangPack.get("common", "add_favorite"), "javascript:ImageManagerTheme.addFavorite(ImageManagerTheme.editPath);", {id : 'menu_addfavorite'}));
			dm.appendItem(dm.createItem(LangPack.get("common", "removefavorite"), "javascript:ImageManagerTheme.removeFavorite(ImageManagerTheme.editPath);", {id : 'menu_removefavorite'}));
			dm.appendItem(dm.createItem(LangPack.get("common", "insert"), "javascript:ImageManagerTheme.open(ImageManagerTheme.editPath);", {id : 'menu_insert'}));
			dm.appendItem(dm.createItem(LangPack.get("common", "deleteit"), "javascript:ImageManagerTheme.deleteMedia(ImageManagerTheme.editPath);", {id : 'menu_delete'}));
			dm.appendItem(dm.createItem(LangPack.get("common", "edit"), "javascript:ImageManagerTheme.editMedia(ImageManagerTheme.editPath);", {id : 'menu_edit'}));
			dm.appendItem(dm.createItem(LangPack.get("common", "view"), "javascript:ImageManagerTheme.viewMedia(ImageManagerTheme.editPath);", {id : 'menu_preview'}));

			// Get parent args
			win = this.getParentWin();

			try {
				if (win && win.mcImageManager) {
					args = win.mcImageManager.getArgs();

					if (args.path)
						this.path = args.path;

					if (args.rootpath)
						this.rootpath = args.rootpath;

					if (args.remember_last_path != null)
						this.rememberPath = args.remember_last_path;

					if (args.url && this.path == '{default}') {
						this.listURL = args.url.substring(0, args.url.lastIndexOf('/'));
						this.listFile = args.url.substring(args.url.lastIndexOf('/') + 1);
					}
				}
			} catch (ex) {
				// Ignore
			}

			Event.add(window, 'DOMContentLoaded', this.onDOMContentLoaded, this);
			Event.add(window, 'load', this.onLoaded, this);
			Event.add(document, 'mouseover', this.onMouseOver, this);

			mcImageManager.onResult.add(this.onResult, this);
			mcImageManager.onError.add(this.onError, this);

			this.paging = new Paging({
				prev : '<a href="javascript:ImageManagerTheme.listFiles({$prev});">&laquo; {#common.prev}&nbsp;&nbsp;|&nbsp;&nbsp;</a>',
				prev_inactive : '&laquo; {#common.prev}&nbsp;&nbsp;|&nbsp;&nbsp;',

				next : '<a href="javascript:ImageManagerTheme.listFiles({$next});">|&nbsp;&nbsp;{#common.next} &raquo;</a>',
				next_inactive : '|&nbsp;&nbsp;{#common.next} &raquo;',

				page : '<a href="javascript:ImageManagerTheme.listFiles({$page});">[{$vpage}]</a>&nbsp; ',
				page_inactive : '[{$vpage}]&nbsp; '
			});

			WindowManager.onbeforeopen = function(w) {
				if (w && w.features.fullscreen)
					DOM.hide('listcontainer');
			};

			WindowManager.onclose = function(w) {
				if (w && w.features.fullscreen)
					DOM.show('listcontainer');
			};
		},

		checkDemo : function() {
			if (this.demoMode)
				WindowManager.error(LangPack.get('error', 'demo'));

			return this.demoMode;
		},

		getParentWin : function() {
			var w = opener || parent;

			return w != window ? w : null;
		},

		onDOMContentLoaded : function() {
			var t = this;

			window.focus();

			this.loadPageOptions();

			DevKit.init('im');

			LangPack.translatePage();
			mcImageManager.setTitle();

			this.listDir(this.path);
			this.listRoots();

			this.thumbsTemplate = new Template('thumbs', 'thumbs_template');

			this.thumbsTemplate.addEncoder('thumbloadurl', function(val, row) {
				if (row.type == 'folder')
					return 'img/folder_big.gif';

				if (row.type == 'parent')
					return 'img/parent_big.gif';

				switch (row.type) {
					case 'swf':
					case 'flv':
						return 'img/flash.gif';

					case 'dcr':
						return 'img/dcr.gif';

					case 'mov':
					case 'qt':
						return 'img/qt.gif';

					case 'ram':
					case 'rm':
						return 'img/rm.gif';

					case 'avi':
					case 'wmv':
					case 'mpg':
					case 'mpeg':
					case 'asf':
						return 'img/avi.gif';
				}

				if (!row.custom.thumbnail)
					return 'img/img_generic.png';

				return 'img/loading_bg.gif';
			});

			this.thumbsTemplate.addEncoder('thumburl', function(val, row) {
				if (row.type == 'folder')
					return 'img/folder_big.gif';

				if (row.type == 'parent')
					return 'img/parent_big.gif';

				switch (row.type) {
					case 'swf':
					case 'flv':
						return 'img/flash.gif';

					case 'dcr':
						return 'img/dcr.gif';

					case 'mov':
					case 'qt':
						return 'img/qt.gif';

					case 'ram':
					case 'rm':
						return 'img/rm.gif';

					case 'wmv':
					case 'avi':
					case 'mpg':
					case 'mpeg':
					case 'asf':
						return 'img/avi.gif';
				}

				if (!row.custom.thumbnail)
					return 'img/img_generic.png';

				return '../../stream/' + mox.defaultDoc + '?cmd=im.thumb&path=' + encodeURIComponent(val) + '&u=' + row.size;
			});

			//this.listFiles(this.page);
			this.setPageSize(this.thumbsPageSize);
			this.switchView(this.view);			

//			Event.add('filter', 'blur', this.filterChange, this);
			Event.add('filter', 'keyup', function(e) {
				if (e.keyCode == 13)
					this.filterChange();
			}, this);

			this.menu.render();

			Event.add(document, 'mousedown', function(e) {
				if (!DOM.getParent(e, function(n) { return /act|MenuItem/.test(n.className); }))
					t.menu.hide();
			});

			var fadeIn = new Tween(t.menu._id, {
				opacity : {from : 3, to : 90}
			},{
				time : 300
			});

			var fadeOut = new Tween(t.menu._id, {
				opacity : {from : 90, to : 3}
			},{
				time : 300
			});

			fadeOut.onEnd.add(function() {
				DOM.hide(t.menu._id);
			});

			t.menu.onShow.add(function(e, p) {
				fadeOut.stop();
				fadeIn.start();
			});

			t.menu.onHide.add(function(e, p) {
				if (!DOM.isHidden(t.menu._id)) {
					fadeIn.stop();
					fadeOut.start();
				}

				return false;
			});
		},

		filterChange : function() {
			var f = DOM.get('filter').value;

			if (f == '')
				f = null;
			else if (f.indexOf('*') == -1)
				f = '*' + f + '*';

			this.filter = f;
			this.refresh();
		},

		onLoaded : function() {
			this.updateForm();
		},

		showStatusDialog : function(msg, rs) {
			var h = '', t = this;

			rs.each(function(r, i) {
				var n, root;

				root = t.visualPath;
				root = root.substring(1);
				root = t.visualPath.replace(/^\/([^\/]+)\/.*$/, '$1');

				if (r.fromfile) {
					r.fromfile = r.fromfile.replace(/\{[0-9]+\}/, root);
					r.tofile = r.tofile.replace(/\{[0-9]+\}/, root);

					if (r.status.toLowerCase() != 'ok')
						h += '<div class="statusrow"><div class="statuscol1">' + r.fromfile + " -> " + r.tofile + '</div><div class="statuscol2">' + r.message + '</div></div>';
				}

				if (r.file) {
					r.file = r.file.replace(/\{[0-9]+\}/, root);
					r.file = r.file.replace(/\/+/, '/');

					if (r.status.toLowerCase() != 'ok')
						h += '<div class="statusrow"><div class="statuscol1">' + r.file + '</div><div class="statuscol2">' + r.message + '</div></div>';
				}
			});

			if (h) {
				mox.ui.WindowManager.status(msg, h);
				return true;
			}

			return false;
		},

		updateConfig : function() {
			var im = this;

			// Get the config
			mcImageManager.execRPC('im.getConfig', {path : this.path}, function(r) {
				im.config = r.result;
			});
		},

		keepAlive : function() {
			var im = this;

			// Get the config
			mcImageManager.execRPC('im.keepAlive', null, function(r) {
				var row, rs;
				rs = new ResultSet(r.result);
				if (rs.getRowCount() > 0)
					row = rs.getRow(0);
				
				//NOTE: Do we need the row for anything?
				// row.time has the time code from server.
				window.setTimeout('ImageManagerTheme.keepAlive()', im.keepAliveTime);
			});
		},

		updateForm : function () {
			var frm = document.forms['listOptions'];
			Form.selectOption(frm, 'selectView', this.view);
			Form.selectOption(frm, 'setPages', this.thumbsPageSize);
		},

		storePageOptions : function () {
			var opt, d;

			opt = { pagesize : this.thumbsPageSize, pageview : this.view };

			d = new Date();
			d.setYear(2030);

			Cookie.set("MCManagerOptions", JSON.serialize(opt), d);
		},

		loadPageOptions : function () {
			var cd, sv, p, v, frm;
			
			cd = Cookie.get("MCManagerOptions");

			if (cd) {
				sv = JSON.unserialize(cd);
				this.thumbsPageSize = sv.pagesize;
				this.textPageSize = sv.pagesize;
				this.view = sv.pageview;

				// This might fail onDomContentLoaded?
				try {
					this.updateForm();
				} catch (e) {
					// Ignore, if it fails, run it "onLoad" event instead, as a backup.
				}
			}
		},

		setPageSize : function (s) {
			this.thumbsPageSize = s;
			this.textPageSize = s;
			this.listFiles(0);
		},

		onMouseOver : function(e) {
			var el = DOM.getParent(e.target, function (n) {return n.nodeName == 'DIV' && DOM.hasClass(n, '(edit|details)');});

			if (el)
				return;
		},

		onResult : function(m, r) {
			switch (m) {
				case "im.listFiles":
					if (r.header && (typeof(r.header.pages) != "undefined"))
						this.paging.render(this.page, r.header.pages, 'pages');

					break;
			}
		},

		onError : function(m, e, r, co, text) {
			var doc = document;

			if (typeof(e) == 'object') {
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

		addFavorite : function(path) {
			mcImageManager.execRPC('im.addFavorites', {path0 : path});
		},

		listRoots : function() {
			if (this.rootpath) {
				new Template('category_list', 'folders_template').process({
					path : '/',
					name : '/',
					type : 'folder'
				});
			} else {
				mcImageManager.execRPC('im.listFiles', {path : "root:///", root_path : this.rootpath, only_dirs : true}, function(d) {
					new Template('category_list', 'folders_template').processTable(d);
				});
			}
		},

		listDir : function(path, cb) {
			DOM.get('folder_list').innerHTML = LangPack.translate('<li class="progress">{#common.loading}</li>');

			mcImageManager.execRPC('im.listFiles', {
				path : path,
				root_path : this.rootpath,
				url : this.listURL,
				only_dirs : true,
				filter : this.filter,
				remember_last_path : this.rememberPath
			}, function(d) {
				var rs = new ResultSet(d.result);

				/*rs.each(function(v, i) {
					if (v.type == 'parent') {
						rs.data[i][0] = LangPack.get('common', 'parent');
						return false;
					}
				});*/

				new Template('folder_list', 'folders_template').processTable(d);

				if (cb)
					cb(d.result);
			});
		},

		listFiles : function(page, path, cb, progress) {
			var tpl, t = this, ps;

			if (path)
				this.path = path;

			this.page = page;

			if (this.view == "thumbs") {
				ps = this.thumbsPageSize;
				tpl = this.thumbsTemplate;
			} else {
				ps = this.textPageSize;
				tpl = new Template('textlists', 'textlists_template');
			}

			new Image().src = 'img/loading_bg.gif';

			DOM.hide('pages');
			DOM.show('progress');
			DOM.get('thumbs').innerHTML = '';

			mcImageManager.execRPC('im.listFiles', {
				path : this.path,
				root_path : this.rootpath,
				url : this.listURL,
				page : this.page,
				page_size : ps,
				filter : this.filter,
				remember_last_path : this.rememberPath,
				config : 'general,filesystem,filemanager,thumbnail'
			}, function(d) {
				var conf = d.result.config, rs = new ResultSet(d.result);

				t.demoMode = conf['general.demo'] == "true";
				t.setupToolbars(conf);

				DOM.hide('progress');
				DOM.show('pages');

				rs.each(function(v, i) {
					if (v.type == 'parent') {
						//rs.data[i][0] = LangPack.get('common', 'parent');
						return;
					}
				});

				// Encoders for controling thumbnail width and height
				tpl.addEncoder('size_thumbnail', function(val, row) {
					return parseInt(conf["thumbnail.width"]) + 10;
				});

				tpl.addEncoder('size_pic', function(val, row) {
					return parseInt(conf["thumbnail.height"]) + 10;
				});
				
				tpl.addEncoder('size_name', function(val, row) {
					if (row.type == 'parent')
						return parseInt(conf["thumbnail.width"]);

					return parseInt(conf["thumbnail.width"]) - 16;
				});

				if (d.result.header) {
					t.path = d.result.header.path;
					t.visualPath = d.result.header.visual_path;
					t.config = d.result.config;
					t.filemanagerURL = t.config['filemanager.urlprefix'];
					t.insertMode = t.config['thumbnail.insert'];
					t.imageTools = t.config['thumbnail.image_tools'];

					DOM.get('curpath').innerHTML = LangPack.translate(t.visualPath);
				}

				tpl.processTable(d);
				t.storePageOptions();

				cb && cb(d);

				List.each(DOM.select('img.thumbnailimage', DOM.get('thumbs')), function(i, n) {
					DOM.setAttrib(n, 'src', DOM.getAttrib(n, 'alt'));
					DOM.setAttrib(n, 'alt', '');
				});
			});
		},

		getToolState : function(t) {
			if (List.indexOf(this.disabledTools, t) != -1)
				return 'disabled';

			if (List.indexOf(this.availableTools, t) == -1)
				return 'hidden';

			return '';
		},

		setupToolbars : function(cfg) {
			var tools = ['refresh', 'filemanager'];
			var editTools = ['createdir', 'upload', 'addfavorite', 'removefavorite', 'insert', 'delete', 'edit', 'preview'];
			var cfgTools, t = this;

			if (cfg) {
				cfgTools = cfg['general.tools'].split(',');
				this.availableTools = cfgTools;

				List.each(tools.concat(editTools), function(i, v) {
					if (List.indexOf(cfgTools, v) == -1) {
						DOM.addClass(v, 'hidden');
						DOM.addClass('menu_' + v, 'hidden');
					} else {
						DOM.removeClass('menu_' + v, 'hidden');
						DOM.removeClass(v, 'hidden');
						DOM.removeClass(v, 'disabled');
					}
				});

				this.disabledTools = cfg['general.disabled_tools'].split(',');
				List.each(this.disabledTools, function(i, v) {
					DOM.addClass(v, 'disabled');
				});

				List.each(editTools, function(i, v) {
					if (t.path.indexOf('favorite://') != -1 || t.path.indexOf('history://') != -1)
						DOM.addClass(v, 'disabled');
				});
			}
		},

		openFileManager : function() {
			if (!DOM.hasClass('filemanager', 'disabled'))
				document.location = this.filemanagerURL;
		},

		switchView : function(v) {
			var lv, e;

			this.menu.hide();

			// Whats the point
			if (v == this.view)
				return;

			this.lastView = lv = this.view;
			this.view = v;

			switch (v) {
				case "thumbs":
					this.listFiles(0, this.path, function(d) {
						DOM.hide('textlists');
						DOM.show('thumbs,pages,folders');
					});
					break;

				case "text":
					this.listFiles(0, this.path, function(d) {
						DOM.hide('thumbs');
						DOM.show('textlists,pages,folders');
					});

					break;
			}
		},

		viewMedia : function(path) {
			this.menu.hide();
			WindowManager.open('view.html?rnd=' + new Date().getTime(),'viewMedia','chromeless=yes,fullscreen=yes', {path : path});
		},

		editMedia : function(path) {
			this.menu.hide();
			WindowManager.open('edit.html?rnd=' + new Date().getTime(),'editMedia','fullscreen=yes', {path : path});
		},

		deleteMedia : function(path) {
			var im = this;

			this.menu.hide();

			if (this.checkDemo())
				return;

			WindowManager.confirm(LangPack.get("view", "confirm_delete"), function() {
				mcImageManager.execRPC('im.deleteFiles', {path0 : path},  function (d) {
					if (!im.showStatusDialog(LangPack.get('error', 'delete_failed'), new ResultSet(d.result)))
						im.refresh();
				});
			});
		},

		removeFavorite : function(path) {
			var im = this;

			this.menu.hide();

			if (this.path.indexOf('favorite://') == 0) {
				WindowManager.confirm(LangPack.get("view", "confirm_remove_favorite"), function() {
					mcImageManager.execRPC('im.removeFavorites', {path0 : path},  function (d) {
						im.showStatusDialog(LangPack.get('error', 'remove_favorite_failed'), new ResultSet(d.result));
						im.refresh();
					});
				});
			}
		},

		open : function(path, type, custom) {
			if (type == 'folder' || type == 'parent') {
				this.listDir(path);
				this.listFiles(0, path);
			} else {
				if (this.insertMode)
					this.insert(path, custom);
				else
					this.viewMedia(path);
			}
		},

		insert : function(p, c) {
			var win = this.getParentWin(), man;

			if (win && win != window) {
				man = win.mcImageManager || win.mcFileManager;

				if (man) {
					WindowManager.showProgress(LangPack.get('common', 'image_data'));
					mcImageManager.execRPC('im.insertFiles', {path0 : p},  function (data) {
						var rs, r;

						WindowManager.hideProgress();
						mcImageManager.restoreSelection();

						rs = new ResultSet(data.result);

						if (rs.getRowCount() > 0) {
							r = rs.getRow(0);

							man.selectFile({
								name : r.name,
								path : r.path,
								url : r.url,
								size : r.size,
								type : r.type,
								created : r.created,
								modified : r.modified,
								attribs : r.attribs,
								custom : r.custom
							});
						}

						mcImageManager.close();
					});
				}
			} else
				this.viewMedia(p);
		},

		upload : function() {
			if (!DOM.hasClass('upload', 'disabled'))
				WindowManager.open('upload.html?rnd=' + new Date().getTime(),'UploadWin','top=center,left=center,width=420,height=340');
		},

		createDir : function() {
			if (!DOM.hasClass('createdir', 'disabled'))
				WindowManager.open('createdir.html?rnd=' + new Date().getTime(),'createDir','top=center,left=center,width=320,height=240', {path : this.path});
		},

		refresh : function(progress) {
			if (!DOM.hasClass('refresh', 'disabled')) {
				this.listDir(this.path);
				this.listFiles(this.page, this.path, null, progress);
			}
		},

		showEdit : function(el, path, type, edit, attribs) {
			var p = DOM.getPos(el), r, m = this.menu;

			if (path == this.editPath && (!mox.isSafari && !DOM.isHidden(m.getID()))) {
				m.hide();
				return;
			}

			this.editPath = path;

			// Don't edit these
			if (type == "parent") {
				m.hide();
				return;
			}

			List.each(DOM.select('li', m.getID()), function(i, n) {
				if (!DOM.hasClass(n, 'hidden'))
					DOM.show(n);
			});

			List.each(this.disabledTools, function(i, v) {
				DOM.hide('menu_' + v);
			});

			if (this.path.indexOf('favorite://') != 0)
				DOM.hide('menu_removefavorite');

			if (type == "folder") {
				DOM.hide('menu_insert');
				DOM.hide('menu_preview');
				DOM.hide('menu_edit');
			}

			if (attribs.indexOf('W') == -1)
				DOM.hide('menu_delete');

			if (this.path.indexOf('favorite://') == 0 || this.path.indexOf('history://') == 0) {
				DOM.hide('menu_addfavorite');
				DOM.hide('menu_delete');
			}

			if (edit != 'true')
				DOM.hide('menu_edit');

			r = m.getRect();

			if (mox.isIE) {
				r.w -= 1;
				r.h -= 1;
			}

			m.show(p.x - r.w + 16, p.y - r.h);
		}
	});

	// Setup global image manager
	window.mcImageManager = new moxiecode.manager.ImageManager();

	// Setup short named version of manager
	window.ImageManagerTheme = moxiecode.manager.ImageManagerDefaultTheme;

	// Init theme
	ImageManagerTheme.init();
});
