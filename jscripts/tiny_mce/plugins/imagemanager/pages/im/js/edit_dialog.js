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
	'mox.ui.SelectionRect',
	'mox.geom.Rect',
	'mox.dom.Form',
	'mox.dom.Layer',
	'mox.dom.Drag',
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
	var SelectionRect = mox.ui.SelectionRect;
	var Rect = mox.geom.Rect;
	var Form = mox.dom.Form;

	// Class that contains all theme specific logic
	mox.create('static EditDialog', {
		selectionRect : null,
		editView : null,
		lastRect : null,
		lastWin : null,
		imageRect : null,
		editImgPath : null,

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

		close : function() {
			// Fix for odd focus bug in IE6
			try {document.body.appendChild(document.createElement('input')).focus();} catch (e) {}
			this.getWindowManager().closeAll();
		},

		onDOMContentLoaded : function() {
			var sr, t = this, vp = DOM.getViewPort();

			LangPack.translatePage();

			this.lastWin.setTitle(document.title);

			Event.add(window, 'resize', function(e) {
				var vp = DOM.getViewPort();

				DOM.setStyles('imageWrapper', {'width' : (vp.w - 30) + 'px', 'height' : (vp.h - 110) + 'px'});

				if (t.selectionRect)
					t.selectionRect.viewPort = DOM.getRect('imageWrapper');
			});

			this.editImgPath = this.args.path;
			this.orgImgPath = this.editImgPath;

			DOM.setStyles('imageWrapper', {'width' : (vp.w - 30) + 'px', 'height' : (vp.h - 110) + 'px'});

			sr = new SelectionRect('imageWrapper', 'eventElement', 'sel', 'selImage');

			this.selectionRect = sr;
			this.loadTargetImage();

			// Fix for odd focus bug in IE
			if (mox.isIE)
				window.focus();
		},

		loadTargetImage : function() {
			var t = this, sr = this.selectionRect;

			t.getWindowManager().showProgress(LangPack.get("edit_image", "loading"));

			mcImageManager.execRPC('im.getMediaInfo', {path : this.args.path}, function (data) {
				var width, height, rs = new ResultSet(data.result), r = rs.getRow(0);

				DOM.get('imagereload').contentWindow.location = r.url;

				t.imageRect = new Rect(0, 0, parseInt(r.width), parseInt(r.height));

				r.width = parseInt(r.width);
				r.height = parseInt(r.height);

				DOM.setAttribs('editImage', {width : t.imageRect.w, height : t.imageRect.h});

				// Add onload handler
				Event.remove('editImage', 'load', t.imageLoaded);
				Event.add('editImage', 'load', t.imageLoaded, t);
				Event.remove('editImage', 'error', t.imageLoadedErr);
				Event.add('editImage', 'error', t.imageLoadedErr, t);

				DOM.setAttribs('editImage', {
					src : '../../stream/' + mox.defaultDoc + '?cmd=im.streamFile&path=' + encodeURIComponent(r.path) + "&rnd=" + new Date().getTime(),
					width : r.width,
					height : r.height
				});

				DOM.setAttribs('selImage', {
					src : '../../stream/' + mox.defaultDoc + '?cmd=im.streamFile&path=' + encodeURIComponent(r.path) + "&rnd=" + new Date().getTime(),
					width : r.width,
					height : r.height
				});

				sr.onSelection.add(function(rect) {
					var f = document.forms[0], DOM = mox.DOM;
					var r = this.getRect();

					f.crop_x.value = r.x;
					f.crop_y.value = r.y;
					f.crop_w.value = r.w;
					f.crop_h.value = r.h;

					f = null;
				});

				sr.setVisible(false);
				sr.setBounderies(0, 0, r.width, r.height);
				sr.addEventDoc(t.getParent().document);
				sr.set(0, 0, 0, 0);
			});
		},

		execEditCommand : function(cmd, val) {
			var sr = this.selectionRect, r = sr.getRect(), f = document.forms[0], t = this, angle;

			if (this.lastRect && cmd != 'apply_resize') {
				DOM.setAttribs('editImage', {width : this.lastRect.w, height : this.lastRect.h});
				DOM.setAttribs('selImage', {width : this.lastRect.w, height : this.lastRect.h});

				this.lastRect = null;
			}

			DOM.removeClass('editImage', 'crop');

			switch (cmd) {
				case "resize":
					DOM.setAttribs('editImage', {width : t.imageRect.w, height : t.imageRect.h});
					DOM.setStyles('selImage', {left : 0, top: 0})
					sr.setVisible(false);
					sr.setEnabled(false);

					f.resize_w.value = '' + this.imageRect.w;
					f.resize_h.value = '' + this.imageRect.h;

					DOM.setStyle('crop_tools,save_tools,rotate_tools,flip_tools', 'display', 'none');
					DOM.setStyle('resize_tools', 'display', 'block');
					this.editView = 'resize';
					break;

				case "flip":
					DOM.setAttribs('editImage', {width : t.imageRect.w, height : t.imageRect.h});
					DOM.setStyles('selImage', {left : 0, top: 0})
					sr.settings.image_mode = 'resize';
					sr.setVisible(false);

					DOM.setStyle('resize_tools,crop_tools,save_tools,rotate_tools', 'display', 'none');
					DOM.setStyle('flip_tools', 'display', 'block');
					this.editView = 'resize';
					break;

				case "rotate":
					DOM.setAttribs('editImage', {width : t.imageRect.w, height : t.imageRect.h});
					DOM.setStyles('selImage', {left : 0, top: 0})
					sr.settings.image_mode = 'resize';
					sr.setVisible(false);

					DOM.setStyle('resize_tools,crop_tools,save_tools,flip_tools', 'display', 'none');
					DOM.setStyle('rotate_tools', 'display', 'block');
					this.editView = 'resize';
					break;

				case "crop":
					DOM.setAttribs('editImage', {width : t.imageRect.w, height : t.imageRect.h});
					sr.settings.image_mode = 'crop';
					DOM.setStyles('selImage', {left : 0, top: 0});
					DOM.setAttribs('selImage', {width : sr.boundery.w, height : sr.boundery.h});
					DOM.addClass('editImage', 'crop');
					sr.setVisible(false);
					sr.setEnabled(true);

					DOM.setStyle('resize_tools,save_tools,rotate_tools,flip_tools', 'display', 'none');
					DOM.setStyle('crop_tools', 'display', 'block');
					this.editView = 'crop';
					break;

				case "refresh":
					if (this.editView == 'resize') {
						if (f.resize_prop.checked) {
							if (val == "width")
								f.resize_h.value = Math.round(this.imageRect.h * (parseInt(f.resize_w.value) / this.imageRect.w));
							else
								f.resize_w.value = Math.round(this.imageRect.w * (parseInt(f.resize_h.value) / this.imageRect.h));
						}

						DOM.setAttribs('editImage', {width : f.resize_w.value, height : f.resize_h.value});
					}

					if (this.editView == 'crop') {
						DOM.addClass('editImage', 'crop');
						sr.setVisible(true);
						sr.setEnabled(true);

						sr.moveTo(parseInt(f.crop_x.value), parseInt(f.crop_y.value));
						sr.resizeTo(parseInt(f.crop_w.value), parseInt(f.crop_h.value));
					}

					break;

				case "revert":
					sr.setVisible(false);
					sr.setEnabled(false);

					this.getWindowManager().confirm(LangPack.get("edit_image", "confirm_revert"), function() {
						DOM.setStyle('resize_tools,crop_tools,save_tools,rotate_tools,flip_tools', 'display', 'none');
						t.loadTargetImage();
					});

					this.editImgPath = this.orgImgPath;

					break;

				case "save":
					DOM.setAttribs('editImage', {width : t.imageRect.w, height : t.imageRect.h});
					sr.setVisible(false);
					sr.setEnabled(false);

					f.save_filename.value = this.args.path.substring(this.args.path.lastIndexOf('/') + 1);
					sr.settings.image_mode = 'none';

					DOM.setStyle('resize_tools,crop_tools,rotate_tools,flip_tools', 'display', 'none');
					DOM.setStyle('save_tools', 'display', 'block');
					this.editView = 'save';
					break;

				case "apply_save":
					if (t.getParent().ImageManagerTheme.checkDemo())
						return;

					t.getWindowManager().showProgress(LangPack.get("edit_image", "saving_wait"));
					mcImageManager.execRPC('im.saveImage', {path : this.editImgPath, target : f.save_filename.value}, function(data) {
						var rs = new ResultSet(data.result);

						t.getWindowManager().hideProgress();

						if (!t.getParent().ImageManagerTheme.showStatusDialog(LangPack.get('error', 'save_failed'), rs)) {
							// Safari might fail!
							if (DOM.get('imagereload').contentWindow)
								DOM.get('imagereload').contentWindow.location.reload(true);

							t.refreshList();
							t.editImgPath = rs.getRow(0).file;
						}

						DOM.setStyle('resize_tools,crop_tools,save_tools,rotate_tools,flip_tools', 'display', 'none');
					});

					break;

				case "apply_crop":
					t.getWindowManager().showProgress(LangPack.get("edit_image", "please_wait"));
					mcImageManager.execRPC('im.cropImage', {path : this.editImgPath, left : f.crop_x.value, top : f.crop_y.value, width : f.crop_w.value, height : f.crop_h.value, temp : true}, function(data) {
						t.imageEditApplyCallback('crop', data);
					});
					break;

				case "apply_resize":
					t.getWindowManager().showProgress(LangPack.get("edit_image", "please_wait"));
					mcImageManager.execRPC('im.resizeImage', {path : this.editImgPath, width : f.resize_w.value, height : f.resize_h.value, temp : true}, function(data) {
						t.imageEditApplyCallback('resize', data);
					});
					break;

				case "apply_flip":
					t.getWindowManager().showProgress(LangPack.get("edit_image", "please_wait"));
					mcImageManager.execRPC('im.flipImage', {path : this.editImgPath, horizontal : Form.getRadioValue(f, 'flip') == 'h', vertical : Form.getRadioValue(f, 'flip') == 'v', temp : true}, function(data) {
						t.imageEditApplyCallback('flip', data);
					});
					break;

				case "apply_rotate":
					angle = Form.getRadioValue(f, 'rotate_angle');

					t.getWindowManager().showProgress(LangPack.get("edit_image", "please_wait"));
					mcImageManager.execRPC('im.rotateImage', {path : this.editImgPath, angle : angle, temp : true}, function(data) {
						t.imageEditApplyCallback('rotate', data, angle);
					});
					break;

				case "cancel":
					sr.setVisible(false);
					sr.setEnabled(false);

					DOM.setStyle('resize_tools,crop_tools,save_tools,rotate_tools,flip_tools', 'display', 'none');
					DOM.setAttribs('editImage', {width : t.imageRect.w, height : t.imageRect.h});
					this.editView = null;
					break;
			}
		},

		imageEditApplyCallback : function(action, data, angle) {
			var result = new ResultSet(data.result), f = document.forms[0], r, t = this, sr = t.selectionRect;

			t.getParent().ImageManagerTheme.showStatusDialog(LangPack.get('error', action + '_failed'), new ResultSet(data.result));
			t.getWindowManager().hideProgress();

			if (result.getRowCount() > 0) {
				r = result.getRow(0);
				t.editImgPath = r.file;

				switch (action) {
					case 'resize':
						t.imageRect = new Rect(0, 0, parseInt(f.resize_w.value), parseInt(f.resize_h.value));
						break;

					case 'crop':
						t.imageRect = new Rect(0, 0, parseInt(f.crop_w.value), parseInt(f.crop_h.value));
						break;

					case 'rotate':
						if (angle == 90 || angle == 270)
							t.imageRect = new Rect(0, 0, t.imageRect.h, t.imageRect.w);

						break;
				}

				// Add onload handler
				Event.remove('editImage', 'load', t.imageLoaded);
				Event.add('editImage', 'load', t.imageLoaded, t);
				Event.remove('editImage', 'error', t.imageLoadedErr);
				Event.add('editImage', 'error', t.imageLoadedErr, t);

				DOM.setAttribs('editImage', {
					src : '../../stream/' + mox.defaultDoc + '?cmd=im.streamFile&path=' + encodeURIComponent(r.file) + "&rnd=" + new Date().getTime(),
					width : t.imageRect.w,
					height : t.imageRect.h
				});

				DOM.setAttribs('selImage', {
					src : '../../stream/' + mox.defaultDoc + '?cmd=im.streamFile&path=' + encodeURIComponent(r.file) + "&rnd=" + new Date().getTime(),
					width : t.imageRect.w,
					height : t.imageRect.h
				});

				sr.moveTo(0, 0);
				sr.resizeTo(0, 0);
				sr.setBounderies(0, 0, t.imageRect.w, t.imageRect.h);
				DOM.setStyle('resize_tools,crop_tools,save_tools,rotate_tools,flip_tools', 'display', 'none');
				t.editView = null;
				sr.settings.image_mode = 'none';
				sr.setVisible(false);
			}
		},

		imageLoaded : function() {
			this.getWindowManager().hideProgress();
		},

		imageLoadedErr : function() {
			this.getWindowManager().error('Fatal error: Could not load image.');
		}
	});

	// Setup global image manager
	window.mcImageManager = new moxiecode.manager.ImageManager();

	EditDialog.init();
});
