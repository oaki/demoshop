var XMLStringWriter = new Class({
	doc : null,
	node : null,

	initialize : function(doc, node) {
		this.setDocument(doc, node);
	},

	setDocument : function(doc, node) {
		this.doc = doc;

		if (doc)
			this.node = !node ? doc.documentElement : node;
	},

	writeAttributes : function(attribs) {
		var n, v;

		if (!attribs)
			return;

		for (n in attribs) {
			v = attribs[n];

			if (v && typeof(v) != 'function')
				this.node.setAttribute(n, v);
		}
	},

	writeElement : function(name, attribs, value) {
		this.writeStartElement(name, attribs);
		this.writeString(value);
		this.writeEndElement();
	},

	writeCDATAElement : function(name, attribs, value) {
		this.writeStartElement(name, attribs);
		this.writeCDATA(value);
		this.writeEndElement();
	},

	writeStartElement : function(name, attribs) {
		this.node = this.writeNode(this.doc.createElement(name));
		this.writeAttributes(attribs);
	},

	writeEndElement : function() {
		return (this.node = this.node.parentNode);
	},

	writeComment : function(str) {
		return this.writeNode(this.doc.createComment(str));
	},

	writeString : function(str) {
		if (!str)
			return null;

		return this.writeNode(this.doc.createTextNode(str));
	},

	writeCDATA : function(str) {
		return this.writeNode(this.doc.createCDATASection(str));
	},

	writeNode : function(node) {
		return this.node.appendChild(node);
	},

	close : function() {
		this.doc = null;
	}
});
