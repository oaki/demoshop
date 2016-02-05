// Hightlight table rows
var tableHighlighter = new Class({	
	
	options: {
			rowColourClass: 'highlighted',
			rowHoverColourClass: 'hoverHighlighted',
			highlightRow: 'even',
			everyOther: 0
	},
	
	initialize: function( id, options ) {
		
		this.setOptions( options );
		
		if( this.options.highlightRow == 'odd' ){
			this.options.everyOther = 1;
		}
		
		this.rows = $(id).getElementsByTagName('tr');
		this.rowsLength = this.rows.length;
		
		this.addHighlighting();
				
	},
	
	addHighlighting: function(){
		
		var hoverClass = this.options.rowHoverColourClass;
			
		for( var i = 0; i < this.rowsLength; i++ ){

			$( this.rows[i] ).addEvents({
				'mouseover': function(){ this.addClass( hoverClass ); },
				'focus': function(){ this.addClass( hoverClass ); },
				'mouseout': function(){ this.removeClass( hoverClass ); },
				'blur': function(){ this.removeClass( hoverClass ); }
			});
			
			if( this.options.everyOther != 0 ){
				this.rows[i].addClass( this.options.rowColourClass );
				this.options.everyOther = -1;
			}
			
			this.options.everyOther++;
			
		}
		
	}
	
});

tableHighlighter.implement(new Options);