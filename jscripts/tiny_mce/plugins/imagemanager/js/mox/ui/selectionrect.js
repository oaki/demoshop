/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

mox.require([
	'mox.DOM',
	'mox.Event',
	'mox.dom.Drag',
	'mox.geom.Point',
	'mox.geom.Rect',
	'mox.util.Dispatcher'
], function() {
	// Shorten class names
	var DOM = mox.DOM;
	var Event = mox.Event;
	var Drag = mox.dom.Drag;
	var Point = mox.geom.Point;
	var Rect = mox.geom.Rect;
	var Dispatcher = mox.util.Dispatcher;

	/**#@+
	 * @member mox.ui.SelectionRect
	 * @base mox.dom.Drag
	 */
	mox.create('mox.ui.SelectionRect:mox.dom.Drag', {
		corners : null,
		startRect : null,
		startPosLayer : null,
		selectionStarted : false,
		containerId : null,
		containerPos : null,
		viewPort : null,
		enabled : null,

		SelectionRect : function(co, ev, t, img, s) {
			var d = document, cl, n;

			this.parent(t, s);

			this.onDragStart.add(function() {
				this.setCornersVisibility(0);
			});

			this.onDragEnd.add(function() {
				this.setCornersVisibility(1);
			});

			this.settings.corner_element = 'input';

			for (n in s)
				this.settings[n] = s[n];

			this.containerId = co;
			this.eventElId = ev;
			co = this.getContainer();
			this.containerPos = DOM.getPos(co);
			this.imgId = img;

			DOM.addClass(this.getEl(), 'selection');

			this.setBounderies(10, 10, 1024, 768);
			this.viewPort = DOM.getRect(co);
			this.enabled = true;

			cl = {};

			cl.tl = this.createCorner('tl');
			cl.tc = this.createCorner('tc');
			cl.tr = this.createCorner('tr');
			cl.bl = this.createCorner('bl');
			cl.bc = this.createCorner('bc');
			cl.br = this.createCorner('br');
			cl.cl = this.createCorner('cl');
			cl.cr = this.createCorner('cr');

			this.corners = cl;
			this.update();

			this.onSelection = new Dispatcher(this);

			Event.add(ev, 'mousedown', this.startSelect, this);
		},

		setEnabled : function(s) {
			this.enabled = s;
		},

		getImg : function() {
			return DOM.get(this.imgId);
		},

		getContainer : function() {
			return DOM.get(this.containerId);
		},

		startSelect : function(e) {
			var b = this.boundery, x, y, px, py, s = this.settings, ce = this.getContainer(), p = Event.getPageXY(e);

			if (!this.enabled)
				return;

			if (s.image_mode == 'resize' || (s.image_mode != 'crop' && !this.isVisible()))
				return Event.cancel(e);

			if (!this.selectionStarted) {
				p.x -= 1;
				p.y -= 1;

				px = p.x;
				py = p.y;
				x = p.x - this.containerPos.x + ce.scrollLeft - 10;
				y = p.y - this.containerPos.y + ce.scrollTop - 10;

				if (this.viewPort && !this.viewPort.containsXY(px, py))
					return Event.cancel(e);

				if (!this.boundery.containsXY(x, y))
					return Event.cancel(e);

				this.startPos = new Point(e.screenX, e.screenY);
				this.startPosLayer = new Point(x, y);

				this.addDocEvent('mouseup', this.endSelect, this);
				this.addDocEvent('mousemove', this.selectionChange, this);

				this.setCornersVisibility(0);
				this.selectionStarted = true;
			}

			return Event.cancel(e);
		},

		endSelect : function(e) {
			if (this.selectionStarted) {
				this.removeDocEvent('mouseup', this.endSelect, this);
				this.removeDocEvent('mousemove', this.selectionChange, this);

				this.setCornersVisibility(1);
				this.selectionStarted = false;
			}

			return Event.cancel(e);
		},

		selectionChange : function(e) {
			var x, y, w, h, b = this.boundery;

			if (this.settings.image_mode == 'crop' && !this.isVisible()) {
				this.setVisible(1);
				this.setCornersVisibility(0);
			}

			x = this.startPosLayer.x;
			y = this.startPosLayer.y;
			w = e.screenX - this.startPos.x;
			h = e.screenY - this.startPos.y;

			if (w < 0) {
				w = Math.abs(w);
				x -= w;
			}

			if (h < 0) {
				h = Math.abs(h);
				y -= h;
			}

			if (x < b.x) {
				x = b.x;
				w = this.w;
			}

			if (y < b.y) {
				y = b.y;
				h = this.h;
			}

			if (x + w > b.x + b.w)
				w -= (x + w) - (b.x + b.w);

			if (y + h > b.y + b.h)
				h -= (y + h) - (b.y + b.h);

			this.moveTo(x, y);
			this.resizeTo(w, h);

			return Event.cancel(e);
		},

		createCorner : function(c) {
			var d = document, cursors, dr, id = DOM.uniqueId(), en = this.settings.corner_element, t = this; // input

			cursors = {tl : 'nw-resize', tc : 'n-resize', tr : 'ne-resize', cr : 'e-resize', cl : 'w-resize', bl : 'sw-resize', bc : 's-resize', br : 'se-resize'};

			DOM.get(this.eventElId).appendChild(DOM.createTag(en, {id : id, 'class' : 'selection-corner selection-corner-' + c, href : '#'}));

			dr = new Drag(id, {});

			dr.onMouseDown.add(function(e) {
				t.getEl().parentNode.style.cursor = cursors[c];
				t.getEl().style.cursor = cursors[c];

				t.updateRect();
				t.startRect = t.clone();
			});

			dr.onDragStart.add(function() {
				t.setCornersVisibility(0);
			});

			dr.onDragEnd.add(function() {
				t.getEl().parentNode.style.cursor = 'crosshair';
				t.getEl().style.cursor = 'move';
				t.setCornersVisibility(1);
				t.updateRect();
				t.startRect = this.clone();
			});

			dr.onDrag.add(function(dx, dy) {
				t.onDragCorner(c, dx, dy);
			});

			if (this.boundery)
				dr.setBounderies(this.boundery.x, this.boundery.y, this.boundery.w, this.boundery.h);

			return dr;
		},

		onDragCorner : function(c, dx, dy) {
			var sr = this.startRect, x = sr.x, y = sr.y, w = sr.w, h = sr.h, b = this.boundery;

			switch (c) {
				case 'tl':
					x += dx;
					y += dy;
					w -= dx;
					h -= dy;
					break;

				case 'tc':
					y += dy;
					h -= dy;
					break;

				case 'tr':
					y += dy;
					w += dx;
					h -= dy;
					break;

				case 'bl':
					x += dx;
					w -= dx;
					h += dy;
					break;

				case 'bc':
					h += dy;
					break;

				case 'br':
					w += dx;
					h += dy;
					break;

				case 'cl':
					x += dx;
					w -= dx;
					break;

				case 'cr':
					w += dx;
					break;
			}

			if (w < 0) {
				w = Math.abs(w);
				x -= w;
			}

			if (h < 0) {
				h = Math.abs(h);
				y -= h;
			}

			if (b) {
				if (x < b.x) {
					w -= b.x - x;
					x = b.x;
				}

				if (y < b.y) {
					h -= b.y - y;
					y = b.y;
				}

				if (x > b.x + b.w)
					x = b.x + b.w;

				if (y > b.y + b.h)
					y = b.y + b.h;

				if (x + w > b.x + b.w)
					w -= (x + w) - (b.x + b.w);

				if (y + h > b.y + b.h)
					h -= (y + h) - (b.y + b.h);
			}

			//this.getContainer().scrollLeft = Math.max((x + w + 30) - this.viewPort.w, 0);
			//this.getContainer().scrollTop = Math.max((y + h + 30)- this.viewPort.h, 0);

			this.set(x, y, w, h);
			this.update();
		},

		setVisible : function(s) {
			this.parent(s);
			this.setCornersVisibility(s);
		},

		getRect : function() {
			return new Rect(this.x - this.boundery.x, this.y - this.boundery.y, this.w, this.h);
		},

		addEventDoc : function(d) {
			var cl = this.corners;

			this.parent(d);

			cl.tl.addEventDoc(d);
			cl.tc.addEventDoc(d);
			cl.tr.addEventDoc(d);
			cl.bl.addEventDoc(d);
			cl.bc.addEventDoc(d);
			cl.br.addEventDoc(d);
			cl.cl.addEventDoc(d);
			cl.cr.addEventDoc(d);
		},

		setCornersVisibility : function(s) {
			var cl = this.corners;

			this.cornersVisible = s;
			this.update();

			cl.tl.setVisible(s);
			cl.tc.setVisible(s);
			cl.tr.setVisible(s);
			cl.bl.setVisible(s);
			cl.bc.setVisible(s);
			cl.br.setVisible(s);
			cl.cl.setVisible(s);
			cl.cr.setVisible(s);
		},

		update : function() {
			var p, cl, t = this;

			this.parent();

			if (navigator.userAgent.indexOf('MSIE 5') < 0)
				DOM.setStyles(this.getElement(), {width : (this.w >= 2 ? this.w - 2 : 0) + 'px', height : (this.h >= 2 ? this.h - 2 : 0) + 'px'}); // Resize without borders

			if (this.cornersVisible) {
				cl = this.corners;

				p = this.getLocation('tl');
				cl.tl.updateRect();
				cl.tl.moveTo(p.x - (cl.tl.w / 2), p.y - (cl.tl.h / 2));

				p = this.getLocation('tc');
				cl.tc.updateRect();
				cl.tc.moveTo(p.x - (cl.tc.w / 2), p.y - (cl.tc.h / 2));

				p = this.getLocation('tr');
				cl.tr.updateRect();
				cl.tr.moveTo(p.x - (cl.tr.w / 2), p.y - (cl.tr.h / 2));

				p = this.getLocation('bl');
				cl.bl.updateRect();
				cl.bl.moveTo(p.x - (cl.bl.w / 2), p.y - (cl.bl.h / 2));

				p = this.getLocation('bc');
				cl.bc.updateRect();
				cl.bc.moveTo(p.x - (cl.bc.w / 2), p.y - (cl.bc.h / 2));

				p = this.getLocation('br');
				cl.br.updateRect();
				cl.br.moveTo(p.x - (cl.br.w / 2), p.y - (cl.br.h / 2));

				p = this.getLocation('cl');
				cl.cl.updateRect();
				cl.cl.moveTo(p.x - (cl.cl.w / 2), p.y - (cl.cr.h / 2));

				p = this.getLocation('cr');
				cl.cr.updateRect();
				cl.cr.moveTo(p.x - (cl.cr.w / 2), p.y - (cl.cr.h / 2));
			}

			if (this.settings.image_mode == 'crop') {
				DOM.setStyle(this.getImg(), 'left', (0 - this.x + this.boundery.x - 1) + 'px');
				DOM.setStyle(this.getImg(), 'top', (0 - this.y + this.boundery.y - 1) + 'px');
			}

			if (this.settings.image_mode == 'resize')
				DOM.setAttribs(this.getImg(), {width : this.w, height : this.h});

			window.setTimeout(function() {
				t.onSelection.dispatch();
			}, 10);
		}

		/**#@-*/
	});
});
