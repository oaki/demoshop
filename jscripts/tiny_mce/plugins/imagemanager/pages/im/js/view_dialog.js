/**
 * $Id: dialog.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

mox.require([
	'mox.Event',
	'mox.DOM',
	'mox.List',
	'mox.geom.Point',
	'mox.geom.Rect',
	'mox.lang.LangPack',
	'mox.tpl.Template',
	'mox.data.ResultSet',
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

	// Class that contains all theme specific logic
	mox.create('static ViewDialog', {
		editView : null,
		nextMedia : null,
		prevMedia : null,
		lastWin : null,
		demo : false,

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

		fixSize : function(val, row) {
			val = parseInt(val);

			if (row.type == 'folder' || row.type == 'parent')
				return '';

			// MB
			if (val > 1048576)
				return Math.round(val / 1048576, 1) + " MB";

			// KB
			if (val > 1024)
				return Math.round(val / 1024, 1) + " KB";

			return val + " b";
		},

		getParent : function() {
			return parent || opener;
		},

		getWindowManager : function() {
			return this.getParent().mox.ui.WindowManager;
		},

		onDOMContentLoaded : function() {
			var s;

			LangPack.translatePage();

			this.lastWin.setTitle(document.title);

			window.focus();

			this.getMediaInfo(this.args.path);

			Event.add(document, 'keydown', this.checkKey, this);
			Event.add(window, 'resize', this.resizeView, this);
		},

		resizeView : function() {
			var vp = DOM.getViewPort();

			if (DOM.get('singleimg'))
				DOM.setStyles('singleimg', {'width' : (vp.w - 60) + 'px', 'height' : (vp.h - 130) + 'px'});
		},

		close : function() {
			// Fix for odd focus bug in IE6
			try {document.body.appendChild(document.createElement('input')).focus();} catch (e) {}

			DOM.get('singlecontent').innerHTML = ''; // Remove any embedded object
			this.getWindowManager().closeAll();
		},

		checkKey : function (e) {
			//mox.log("Log: " + e.keyCode);
			switch (e.keyCode) {
				case 32: // space
				case 110: // n
				case 34: // page down
				case 39: // right arrow
				case 40: // down arrow
					this.getMediaInfo(this.nextMedia);
				break;

				case 102: // p
				case 33: // page up
				case 37: // left arrow
				case 38: // up arrow
					this.getMediaInfo(this.prevMedia);
				break;

				case 27: // Esc
					ViewDialog.close();
				break;
			}
		},

		edit : function(path) {
			this.getParent().setTimeout('mox.ui.WindowManager.closeAll();ImageManagerTheme.editMedia("'+ path +'")', 100);
		},

		viewAction : function(cmd, path) {
			switch (cmd) {
				case "edit":
					this.edit(path);
					break;

				case "delete":
					this.close();
					break;

				case "open":
					this.close();
					break;
			}
		},

		deleteMedia : function(path) {
			var t = this;

			if (t.getParent().ImageManagerTheme.checkDemo())
				return;

			t.getWindowManager().confirm(LangPack.get("view", "confirm_delete"), function() {
				mcImageManager.execRPC('im.deleteFiles', {path0 : path},  function (d) {
					if (!t.getParent().ImageManagerTheme.showStatusDialog(LangPack.get('error', 'delete_failed'), new ResultSet(d.result))) {
						t.refreshList();
						ViewDialog.close();
					}
				});
			});
		},

		refreshList : function() {
			this.getParent().setTimeout('ImageManagerTheme.refresh()', 100);
		},

		getMediaInfo : function(path) {
			var im = this;

			if (!path)
				return;

			mcImageManager.execRPC('im.getMediaInfo', {path : path}, function (data) {
				var resultSet = new ResultSet(data.result);
				var row = resultSet.getRow(0), tpl, par, s;

				new Template('singleheader', 'singleheader_template').processTable(data);

				im.nextMedia = row.next;
				im.prevMedia = row.prev;

				if (!row.next)
					DOM.addClass('next', 'disabled');
				else
					DOM.removeClass('next', 'disabled');

				if (!row.prev)
					DOM.addClass('prev', 'disabled');
				else
					DOM.removeClass('prev', 'disabled');

				tpl = new Template('singlefooter', 'single_footer_simple');

				switch (row.type) {
					case "jpg":
					case "jpeg":
					case "gif":
					case "png":
					case "bmp":
						new Template('singlecontent', 'singleview_template').processTable(data);
						tpl = new Template('singlefooter', 'single_footer_full');
						break;

					case "mpg":
					case "mpeg":
					case "wma":
					case "wmv":
					case "asf":
					case "avi":
						new Template('singlecontent', 'mpg_template').processTable(data);
						break;

					case "qt":
					case "mov":
						new Template('singlecontent', 'mov_template').processTable(data);
						break;

					case "rm":
					case "ram":
						new Template('singlecontent', 'rm_template').processTable(data);
						break;

					case "dcr":
						new Template('singlecontent', 'dcr_template').processTable(data);
						break;

					case "swf":
						var so = new SWFObject("../../stream/" + mox.defaultDoc + "?cmd=im.streamFile&path="+ row.path, "swfMovie", row.width, row.height, "7", "#FFFFFF");
						so.addParam("quality", "high");
						so.addParam("scale", "showall");
						so.addParam("wmode", "transparent");
						so.write("singlecontent");
						tpl = new Template('singlefooter', 'single_footer_no_edit');
						break;

					case "flv":
						var so = new SWFObject("flvplayer/flvPlayer.swf", "flvPlayer", row.width, row.height, "8", "#FFFFFF");
						so.addVariable("flvToPlay", "../../../stream/" + mox.defaultDoc + "?cmd=im.streamFile%26path="+ row.path);
						so.addVariable("hiddenGui", "false");
						so.addVariable("showScaleModes", "true");
						so.addVariable("autoStart", "false");
						so.addParam("allowFullScreen", "true");
						so.write("singlecontent");
						break;
				}

				tpl.addEncoder('sizefix', im.fixSize);
				tpl.processTable(data);

				s = im.getParent().ImageManagerTheme.getToolState('edit');

				if (s == 'disabled')
					DOM.replaceClass('edit', 'edt', 'edt_disabled');

				if (s == 'hidden' || (row.custom && row.custom.editable == false))
					DOM.remove('edit');

				s = im.getParent().ImageManagerTheme.getToolState('delete');

				if (s == 'disabled')
					DOM.replaceClass('deleteit', 'delete', 'delete_disabled');

				if (s == 'hidden')
					DOM.remove('deleteit');

				im.resizeView();
			});	
		}
	});

	// Setup global image manager
	window.mcImageManager = new moxiecode.manager.ImageManager();

	ViewDialog.init();
});
