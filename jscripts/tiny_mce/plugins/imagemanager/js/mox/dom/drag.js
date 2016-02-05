/**
 * $Id: TinyMCE_DOMUtils.class.js 91 2006-10-02 14:53:22Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

mox.require([
	'mox.DOM',
	'mox.Event',
	'mox.geom.Point',
	'mox.geom.Rect',
	'mox.List',
	'mox.util.Dispatcher'
], function() {
	// Shorten class names
	var DOM = mox.DOM;
	var Event = mox.Event;
	var Point = mox.geom.Point;
	var Rect = mox.geom.Rect;
	var List = mox.List;
	var Dispatcher = mox.util.Dispatcher;

	/**#@+
	 * @class This class is used to make items draggable.
	 * @member mox.dom.Drag
	 * @base mox.dom.Layer'
	 */
	mox.create('mox.dom.Drag:mox.dom.Layer', {
		settings : null,
		started : false,
		startPos : null,
		startRect : null,
		boundery : null,
		eventDocs : null,
		dropRects : null,
		hoverRect : null,

		/**
		 * Constructs a new draggable instance of the specified target element.
		 *
		 * @param {string} t Id of target element to make dragable.
		 * @param {Object} s Optional name/value settings object.
		 * @constructor
		 */
		Drag : function(t, s) {
			var n, dr = this;

			this.parent(t, false);

			this.eventDocs = [document];

			// Default settings
			this.settings = {
				drag_handle : t,
				drop_points : '',
				ondrag : mox.bind(this, this.onDragEvent)
			};

			for (n in s)
				this.settings[n] = s[n];

			s = this.settings;

			this.setDropPoints(s.drop_points);

			List.map(s.drag_handle.split(','), function(i, v) {
				Event.add(DOM.get(v), 'mousedown', dr.onMouseDownDrag, dr);
			});

			this.onEnter = new Dispatcher(this);
			this.onLeave = new Dispatcher(this);
			this.onDragStart = new Dispatcher(this);
			this.onDragEnd = new Dispatcher(this);
			this.onDrag = new Dispatcher(this);
			this.onDrop = new Dispatcher(this);
			this.onOver = new Dispatcher(this);
			this.onMouseDown = new Dispatcher(this);
		},

		/**#@+
		 * @method
		 */

		/**
		 * Starts the drag operation. This is automaticly done if the
		 * user clicks the element or drag handle.
		 *
		 * @param {Element} he Optinal drag handle element. Will be passed to dragstart callback.
		 */
		start : function(he) {
			if (!this.started) {
				this.updateRect();
				this.startRect = new Rect(this.x, this.y, this.w, this.h);

				this.addDocEvent('mousemove', this.onMouseMove, this);
				this.addDocEvent('mouseup', this.end, this);
				this.onDragStart.dispatch(he);
				this.started = true;
			}
		},

		/**
		 * Ends the drag operation. This is automaticly done if the
		 * user releases the mouse button.
		 */
		end : function() {
			if (this.started) {
				this.removeDocEvent('mousemove', this.onMouseMove, this);
				this.removeDocEvent('mouseup', this.end, this);
				this.onDragEnd.dispatch();

				if (this.hoverRect)
					this.onDrop.dispatch(this.hoverRect.el);

				this.hoverRect = null;
				this.started = false;
			}
		},

		/**
		 * Sets the boundery rectance of the draggable instance.
		 * This boundery restricts where the user might drag the element.
		 *
		 * @param {Number} x X cordinate/Horizontal position.
		 * @param {Number} y Y cordinate/Vertical position.
		 * @param {Number} w Width of the rectange.
		 * @param {Number} h Height of the rectange.
		 */
		setBounderies : function(x, y, w, h) {
			return this.boundery = new Rect(x, y, w, h);
		},

		setDropPoints : function(e) {
			var i, r;

			if (!e)
				return;

			for (i=0, e = e.split(','), this.dropRects = []; i<e.length; i++) {
				r = DOM.getRect(e[i]);
				r.el = e[i];

				this.dropRects.push(r);
			}
		},

		onMouseMove : function(e) {
			var x, y, sr = this.startRect, sp = this.startPos, b = this.boundery, dx, dy;

			dx = e.screenX - sp.x;
			dy = e.screenY - sp.y;

			x = sr.x + dx;
			y = sr.y + dy;

			if (b) {
				if (x < b.x)
					x = b.x;

				if (y < b.y)
					y = b.y;

				if (x + this.w > b.x + b.w)
					x = (b.x + b.w) - this.w;

				if (y + this.h > b.y + b.h)
					y = (b.y + b.h) - this.h;
			}

			if (this.onDrag.dispatch(dx, dy, x, y) !== false)
				this.moveTo(x, y);

			this.handleHoverRect(dx, dy);

			return Event.cancel(e);
		},

		handleHoverRect : function(dx, dy) {
			var mx, my, i, l, dr = this.dropRects;

			if (dr) {
				mx = this.mouseDownPos.x + dx;
				my = this.mouseDownPos.y + dy;

				for (i=0, l=dr.length; i<l; i++) {
					if (dr[i].containsXY(mx, my)) {
						if (this.hoverRect != dr[i]) {
							if (this.hoverRect)
								this.onLeave.dispatch(this.hoverRect.el);

							this.onEnter.dispatch(dr[i].el);
							this.hoverRect = dr[i];
						}

						this.onOver.dispatch(dr[i].el);
						return;
					}
				}

				if (this.hoverRect) {
					this.onLeave.dispatch(this.hoverRect.el);
					this.hoverRect = null;
				}
			}
		},

		startDrag : function(e) {
			var mx, my;

			mx = !e.pageX ? e.x + document.body.scrollLeft : e.pageX;
			my = !e.pageY ? e.y + document.body.scrollTop : e.pageY;

			this.start(e.target);

			this.startPos = new Point(e.screenX, e.screenY);
			this.mouseDownPos = new Point(mx, my);
		},

		onMouseDownDrag : function(e) {
			this.onMouseDown.dispatch(e);
			this.startDrag(e, 'move');

			return Event.cancel(e);
		},

		addEventDoc : function(d) {
			this.eventDocs.push(d);
		},

		addDocEvent : function(n, f, s) {
			var i, dl = this.eventDocs;

			for (i=0; i<dl.length; i++)
				Event.add(dl[i], n, f, s);
		},

		removeDocEvent : function(n, f, s) {
			var i, dl = this.eventDocs;

			for (i=0; i<dl.length; i++)
				Event.remove(dl[i], n, f, s);
		}

		/**#@-*/
	});
});
