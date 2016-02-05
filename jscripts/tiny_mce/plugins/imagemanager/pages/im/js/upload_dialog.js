/**
 * $Id: dialog.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

mox.require([
	'mox.lang.LangPack',
	'mox.Event',
	'mox.DOM',
	'mox.tpl.Template',
	'mox.data.ResultSet',
	'mox.dom.Form',
	'moxiecode.manager.ImageManager',
	'mox.List',
	'mox.geom.Point',
	'mox.geom.Rect',
	'moxiecode.manager.BaseManager',
	'mox.util.Dispatcher',
	'mox.net.JSON'
], function() {
	// Shorten class names
	var Event = mox.Event;
	var DOM = mox.DOM;
	var LangPack = mox.lang.LangPack;
	var Template = mox.tpl.Template;
	var ResultSet = mox.data.ResultSet;
	var Form = mox.dom.Form;
	var List = mox.List;

	// Class that contains all theme specific logic
	mox.create('static UploadDialog', {
		uploading : false,
		formCount : 0,
		uploadCount : 0,
		winReturn : false,

		init : function() {
			var t = this;

			this.win = this.getWindowManager().getLastWindow();
			Event.add(window, 'init', this.onDOMContentLoaded, this);

			this.win.onClose.add(function() {
				if (!t.winReturn && t.uploading) {
					t.getWindowManager().confirm(LangPack.get('upload', 'upload_warn'), function() {
						t.winReturn = true;
						t.close();
					});
				} else
					t.winReturn = true;

				return t.winReturn;
			});
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
			var win = this.getParent(), t = this;

			LangPack.translatePage();
			this.win.setTitle(document.title);

			mcImageManager.execRPC('im.getConfig', {path : win.ImageManagerTheme.path}, function(r) {
				var config = r.result, maxSize, upExt, fsExt, outExt = [], i, x, found, swfExts = '', useFlash, maxSizeNum;

				DOM.show('content');

				maxSize = config['upload.maxsize'];
				useFlash = config['upload.use_flash'] != 'false';
				fsExt = config['filesystem.extensions'].split(',');
				upExt = config['upload.extensions'].split(',');

				maxSize = maxSize.replace(/\s+/, '');
				maxSize = maxSize.replace(/([0-9]+)/g, '$1 ');

				for (i=0; i<upExt.length; i++) {
					found = false;

					for (x=0; x<fsExt.length; x++) {
						if (upExt[i] == fsExt[x]) {
							found = true;
							break;
						}
					}

					if (found)
						outExt.push(upExt[i]);
				}

				new Template('facts', 'facts_template').process({extensions : outExt.join(', '), maxsize : maxSize, path : win.ImageManagerTheme.visualPath});
			});
		},

		onUploadSubmit : function(f) {
			var nf, row, que = DOM.get('uploadQue');

			// No file or name
			if (!f.file0.value || !f.name0.value)
				return false;

			// Add file to visual que
			row = DOM.create('div', {id : 'row_' + this.formCount, 'class' : 'row'});
			DOM.add(row, 'span', {'class' : 'name'}, f.name0.value);
			DOM.add(row, 'span', {id : 'status_' + this.formCount, 'class' : 'status'}, '<a href="javascript:UploadDialog.cancelUpload(' + this.formCount + ');" class="remove">Cancel</a>');
			que.appendChild(row);

			// Start upload if no upload in progress
			if (!this.uploading) {
				this.submitForm();
				DOM.addClass('status_' + this.uploadCount, 'uploading');
				DOM.get('status_' + this.uploadCount).innerHTML = LangPack.get('upload', 'progress');
				this.uploading = true;
			}

			// Add new empty upload form
			nf = f.cloneNode(true);

			// Since old Safari sux we need to fake hidden this way
			if (mox.isSafari) {
				DOM.get("uploadFormContents_" + this.formCount).style.overflow = 'hidden';
				DOM.get("uploadFormContents_" + this.formCount).style.height = '0px';
			} else
				DOM.hide("uploadFormContents_" + this.formCount); // Needed for access denied error in IE

			this.formCount++;
			nf.innerHTML = nf.innerHTML; // Remove file selection (Safari)
			nf.setAttribute("id", "uploadForm_" + this.formCount);
			nf.getElementsByTagName('div')[0].setAttribute("id", "uploadFormContents_" + this.formCount);
			DOM.get('uploadForms').appendChild(nf);

			return false;
		},

		handleJSONResponse : function(o) {
			var status, t = this, rs = new ResultSet(o.result);

			t.refreshList();
			if (rs.getRow(0).status != 'OK')
				DOM.addClass('status_' + this.uploadCount, 'failed');

			DOM.get('status_' + this.uploadCount).innerHTML = LangPack.translate(rs.getRow(0).message);
			DOM.removeClass('status_' + this.uploadCount, 'uploading');
			this.uploadCount++;

			// Check if files left in que
			if (this.uploadCount < this.formCount) {
				// Find first valid one, might be skipped ones
				for (; (status = DOM.get('status_' + this.uploadCount)) == null && this.uploadCount < this.formCount; this.uploadCount++) ;

				// Item found start upload
				if (status) {
					DOM.addClass(status, 'uploading');
					status.innerHTML = LangPack.get('upload', 'progress');
					this.submitForm();
				}
			} else
				this.uploading = false;
		},

		cancelUpload : function(c) {
			DOM.remove('row_' + c);
		},

		submitForm : function() {
			var f = DOM.get('uploadForm_' + this.uploadCount);

			f.path.value = this.getParent().ImageManagerTheme.path;
			f.submit();
		},

		onUploadChangeFile : function(e) {
			var f = e.form, p;

			p = '' + e.value.replace(/\\/g, '/'); // To unix style
			p = p.substring(p.lastIndexOf('/') + 1);
			p = p.substring(0, p.lastIndexOf('.'));

			// Implement stange characters replacement here

			f.name0.value = p;
		}
	});

	// Setup global file manager
	window.mcImageManager = new moxiecode.manager.ImageManager();

	UploadDialog.init();
});

function handleJSON(o) {
	UploadDialog.handleJSONResponse(o);
}
