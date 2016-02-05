/**
 * $Id: validate.js 18 2006-06-29 14:11:23Z spocke $
 *
 * Various form validation methods.
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

mox.require([
	'mox.DOM',
	'mox.Event',
	'mox.String',
	'mox.dom.Tween'
], function() {
	// Shorten class names
	var DOM = mox.DOM;
	var Event = mox.Event;
	var String = mox.String;
	var Tween = mox.dom.Tween;

	/**#@+
	 * @member mox.ui.TreeNode
	 */
	mox.create('mox.ui.TreeNode', {
		childNodes : null,
		parentNode : null,
		open : false,
		type : null,
		itemId : null,
		rowId : null,
		foldLinkId : null,
		foldImgId : null,
		iconImgId : null,
		textId : null,
		containerId : null,
		args : null,
		tree : null,

		TreeNode : function(tree, text, args) {
			var iu, itemEl, rowEl, textEl, containerEl, foldLinkEl, foldImgEl, iconImgEl;

			function get(n, d) {
				return args && typeof(args[n]) != 'undefined' ? args[n] : d;
			}

			this.type = get('type', 'node');
			this.tree = tree;
			this.childNodes = [];
			this.id = DOM.uniqueId();
			this.open = get('open', false);
			this.args = args;

			this.itemId = DOM.uniqueId();
			this.rowId = DOM.uniqueId();
			this.foldLinkId = DOM.uniqueId();
			this.foldImgId = DOM.uniqueId();
			this.iconImgId = DOM.uniqueId();
			this.textId = DOM.uniqueId();
			this.containerId = DOM.uniqueId();

			// Setup node elements
			itemEl = DOM.createTag('div', {id : this.itemId});
			rowEl = DOM.createTag('div', {id : this.rowId, 'class' : 'mctree-row'});
			textEl = DOM.createTag('a', {id : this.textId, 'class' : 'mctree-text ' + (this.type == 'loading' ? 'mctree-loading' : '') + get('text_class', ''), href : '#'}, String.xmlEncode(text));
			containerEl = DOM.createTag('div', {id : this.containerId, 'class' : (this.open ? 'mctree-open' : 'mctree-closed')});

			// Append node elements
			if (get('folding_root', true)) {
				foldLinkEl = DOM.createTag('a', {id : this.foldLinkId, href : '#', 'class' : 'mctree-fold'});
				foldImgEl = DOM.createTag('img', {id : this.foldImgId, 'class' : (this.open ? 'mctree-open' : 'mctree-closed'), src : 'img/tree/transparent.gif'});
				foldLinkEl.appendChild(foldImgEl);
				rowEl.appendChild(foldLinkEl);
			}

			if ((iu = get('icon_url', tree.get('default_icon_url', ''))) != '' && this.type != 'loading') {
				iconImgEl = DOM.createTag('img', {id : this.iconImgId, 'class' : 'mctree-icon', src : iu});
				rowEl.appendChild(iconImgEl);
			}

			rowEl.appendChild(textEl);
			itemEl.appendChild(rowEl);
			itemEl.appendChild(containerEl);

			DOM.get(tree.target).appendChild(itemEl);

			// Setup event handlers
			Event.add(textEl, 'contextmenu', this.onEvent, this);
			Event.add(textEl, 'click', this.onEvent, this);

			if (foldLinkEl) {
				Event.add(foldLinkEl, 'click', this.onFoldClick, this);
				Event.add(foldLinkEl, 'mousedown', this.onMouseDown, this);
			}

			Event.add(textEl, 'mousedown', this.onMouseDown, this);
		},

		get : function(n, d) {
			return this.args && typeof(this.args[n]) != 'undefined' ? this.args[n] : d;
		},

		/**
		 * Returns a array of TreeNodes when the specified function matches a TreeNode.
		 *
		 * @param {function} f Function that returns true/false if the node is to be added or not.
		 * @param {Array} a Optional array to fill with TreeNodes.
		 * @return Array with selected TreeNodes.
		 * @type Array
		 */
		selectNodes : function(f, a) {
			var i, n = this, cn = n.childNodes;

			if (!a)
				a = [];

			if (f(n))
				a.push(n);

			if (n.hasChildNodes()) {
				for (i=0; i<cn.length; i++)
					this.selectNodes(cn[i], f, a);
			}

			return a;
		},

		select : function() {
			var l;

			if ((l = this.tree.lastSel) != null)
				l.unselect();

			DOM.addClass(DOM.get(this.textId), 'mctree-selected');

			this.doCallback('select', this, l);

			this.tree.lastSel = this;
		},

		unselect : function() {
			DOM.removeClass(DOM.get(this.textId), 'mctree-selected');
		},

		setOpen : function(s) {
			this.open = s;

			if (this.open) {
				DOM.replaceClass(DOM.get(this.foldImgId), 'mctree-tclosed', 'mctree-topen');
				DOM.replaceClass(DOM.get(this.foldImgId), 'mctree-closed', 'mctree-open');
				DOM.replaceClass(DOM.get(this.containerId), 'mctree-closed', 'mctree-open');

				//DOM.setStyle(this.containerEl, 'opacity', 0);
/*
				var t = new Tween(this.containerEl, {
					o2pacity : {from : 0, to : 99},
					height : {from : 0, unit : 'px'}
				}, {
					frames : 100,
					onend : function() {
						//alert(this.target);
						this.target.style.overflow = 'visible';
					}
				});

				t.start();*/
			} else {
				DOM.replaceClass(DOM.get(this.foldImgId), 'mctree-open', 'mctree-closed');
				DOM.replaceClass(DOM.get(this.foldImgId), 'mctree-topen', 'mctree-tclosed');
				DOM.replaceClass(this.containerId, 'mctree-open', 'mctree-closed');
			}
		},

		expand : function() {
			this.setOpen(true);
		},

		onMouseDown : function(e) {
			this.onEvent(e);

			return Event.cancel(e);
		},

		appendChild : function(n) {
			var p, lc = this.getLastChild();

			n.parentNode = this;

			p = n;
			while ((p = p.parentNode) != null) {
				if (p.isLast())
					DOM.get(n.rowId).insertBefore(DOM.createTag('img', {src : 'img/tree/transparent.gif', 'class' : 'mctree-blank'}), DOM.get(n.rowId).firstChild);
				else
					DOM.get(n.rowId).insertBefore(DOM.createTag('img', {src : 'img/tree/transparent.gif', 'class' : 'mctree-line'}), DOM.get(n.rowId).firstChild);
			}

			DOM.setAttrib(DOM.get(n.parentNode.foldImgId), 'class', this.open ? 'mctree-open' : 'mctree-closed');
			DOM.setAttrib(DOM.get(n.foldImgId), 'class', 'mctree-lastline');

			if (lc) {
				if (lc.hasChildNodes()) {
					DOM.setAttrib(DOM.get(lc.foldImgId), 'class', lc.open ? 'mctree-topen' : 'mctree-tclosed');
				} else if (!lc.hasChildNodes())
					DOM.setAttrib(DOM.get(lc.foldImgId), 'class', 'mctree-tline');
			}

			if (n.type == 'loading') {
				DOM.setAttrib(DOM.get(n.foldImgId), 'class', 'mctree-loading');
			}

			DOM.get(this.containerId).appendChild(DOM.get(n.itemId));
			this.childNodes.push(n);

			if (lc && lc.hasChildNodes())
				this._fix(this);

			return n;
		},

		_fix : function(n) {
			var i, cn, p, im = [];

			for (i=0, cn = DOM.get(n.rowId).childNodes; i<cn.length; i++) {
				if (cn[i].nodeName == 'IMG' && !DOM.hasClass(cn[i], 'mctree-icon'))
					im.push(cn[i]);
			}

			p = n;
			for (i=im.length - 1; (p = p.parentNode) != null; i--) {
				if (p.isLast())
					DOM.setAttrib(im[i], 'class', 'mctree-blank');
				else
					DOM.setAttrib(im[i], 'class', 'mctree-line');
			}

			for (i=0, cn = n.childNodes; i<cn.length; i++)
				this._fix(cn[i]);
		},

		getLastChild : function() {
			if (this.childNodes.length > 0)
				return this.childNodes[this.childNodes.length - 1];

			return null;
		},

		isLast : function() {
			var p = this.parentNode;

			if (p)
				return p.getLastChild() == this;

			return true;
		},

		hasChildNodes : function() {
			return this.childNodes.length > 0;
		},

		getNodeById : function(id) {
			var i, cn = this.childNodes, n;

			if (this.id == id)
				return this;

			for (i=0; i<cn.length; i++) {
				if ((n = cn[i].getNodeById(id)) != null)
					return n;
			}

			return null;
		},

		remove : function() {
			var i, p = this.parentNode, cn = p ? p.childNodes : null, s = false;

			if (cn) {
				for (i=0; i<cn.length; i++) {
					if (cn[i] == this) {
						cn.splice(i, 1);
						s = true;
						break;
					}
				}

				if (s) {
					if (this.parentNode.getLastChild() && !this.parentNode.getLastChild().hasChildNodes())
						DOM.setAttrib(DOM.get(this.parentNode.getLastChild().foldImgId), 'class', 'mctree-lastline');

					if (!this.parentNode.hasChildNodes()) {
						if (this.parentNode.isLast())
							DOM.setAttrib(DOM.get(this.parentNode.foldImgId), 'class', 'mctree-lastline');
						else
							DOM.setAttrib(DOM.get(this.parentNode.foldImgId), 'class', 'mctree-tline');
					}

					DOM.get(this.itemId).parentNode.removeChild(DOM.get(this.itemId));

					this._fix(this.parentNode);
				}
			}
		},

		onEvent : function(e, t) {
			this.doCallback(!t ? e.type : t, e, this);
		},

		doCallback : function(t, a1, a2) {
			var s = this.tree.settings;

			t = 'on' + t;

			if (typeof(s[t]) != 'undefined')
				s[t](a1, a2);			
		},

		onFoldClick : function(e) {
			this.setOpen(!this.open);
			this.onEvent(e, this.open ? 'open' : 'close');
		}

		/**#@-*/
	});

	/**#@+
	 * @member mox.ui.TreeNode
	 * @base mox.ui.TreeNode
	 */
	mox.create('mox.ui.TreeList:mox.ui.TreeNode', {
		settings : null,
		lastSel : null,
		target : null,

		TreeList : function(t, text, s) {
			var n;

			this.target = t;

			this.parent(this, text, s);

			// Defulat settings
			this.settings = {
			};

			// Override settings
			for (n in s)
				this.settings[n] = s[n];
		},

		createNode : function(text, args) {
			return new mox.ui.TreeNode(this, text, args);
		},

		createLoadingNode : function(text, args) {
			args = !args ? {} : args;
			args.type = 'loading';

			return this.createNode(text, args);
		}

		/**#@-*/
	});
});
