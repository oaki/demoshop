<?php
/**
 * This source file is subject to the "New BSD License".
 *
 * For more information please see http://nette.org
 *
 * @author     Vojtěch Knyttl
 * @copyright  Copyright (c) 2010 Vojtěch Knyttl
 * @license    New BSD License
 * @link       http://tabella.zatorsky.cz/
 */

	class Tabella extends NControl {
		protected $source;
		protected $count;
		protected $cols;
		protected $params;
		protected $linkOpts;
		protected $defaultRowParams;

		const TEXT = "text";
		const TEXTAREA = "textarea";
		const SELECT = "select";
		const CHECKBOX = "checkbox";
		const DATE = "date";
		const TIME = "time";
		const DATETIME = "datetime";
		const DELETE = "delete";
		const ADD = "addTabellaButton";

		/**
		 * Constructs the DibiGrid
		 * @param DibiDataSource
		 * @param array of default parameters 
		 */
		public function __construct( $dibiSource, $params = array() ) {
			parent::__construct();
			$this->source = $dibiSource;
			$this->cols = array();
			
			// common default parameters 
			$this->params = $params + array(
				'id_table'=> 'id',
				'current' => 1,							// current page
				'translator' => null,
				'limit' => 25,							// default rows on page
				'order' => 'id',						// default ordering
				'offset' => 1,							// default offset (page)
				'sorting' => 'asc',						// sorting [asc|desc]
				'filter' => null,						// default filtering (in array)
				'onSubmit' => null,
				'rowRenderer' => function( $row ) {
					return NHtml::el( "tr" );
				},
				'rowClass' => array()					// helper to render each row
			);
			
			// default parameters for each row
			$this->defaultRowParams = array( 
				'filter' => true, 						// is to be filtered
				'truncate' => 40,						// NString truncate to length
				'order' => true,						// orderable
				'width' => 100,							// width of column
				'editable' => false,					// non-editable by default
				'dateFormat' => "%d/%m/%y",				// default datetime format
				'timeFormat' => "%H:%M",				// default datetime format
				'datetimeFormat' => "%d/%m/%y %H:%M",	// default datetime format
				'renderer' => null,						// helper to render the column cell
				'class' => array(),						// array of classes added to column	
				'translate' => false, 					// columns are not translated by default
				'headerElement' => NHtml::el( "th" ),	// helper to render the header cell
				'filterHandler' => function( $val, $col ) {
						return "$col LIKE '$val%'";
				},										// helper to apply filters
				'type' => self::TEXT					// default column type
			);
		}
		
		/**
		 * Load state (from $_GET) for the control 
		 * @param array
		 */
		public function loadState(array $params) {	
			$foo = $this->params;
			parent::loadState( $params );
			$this->params = $this->params + $foo;
			
			$this->linkOpts = array_intersect_key( 
									$this->params,
									array( 'limit' => 0, 'order' => 0, 'sorting' => 0, 'offset' => 0, 'filter' => 0 ) );
			
		}

		/**
		 * Adds a columnt to the grid
		 * @param NString displayed name
		 * @param NString column name (in db)
		 * @param array parameters for the column
		 */
		public function addColumn( $name, $colName, $params = array() ) {
			if( !is_array( $params) ) {
				throw( new Exception( "Third argument must be an array." ) );
			}
			
			$this->cols[$colName] = (object) array(
				'name' => $name,
				'colName' => $colName,
				'params' => ($params + $this->defaultRowParams)
			);
			return $this;
		}
		
		/**
		 * renders the grid
		 */
		public function render() {
			$this->template->setFile( dirname( __FILE__ )."/tabella.phtml" );

			$this->template->name = $this->getUniqueId();

			$this->template->params = $this->linkOpts;
			$this->template->header = $this->renderHeader();
			$this->template->body =	$this->renderBody();
			
			$this->template->footer = $this->renderFooter();
			$this->template->tabellaParams = 
				json_encode( array( "cols" => $this->cols ) );
			$this->template->render();
		}
		
		/**
		 * renders the header
		 * @return NString
		 */
		public function renderHeader() {
			$header = NHtml::el( "tr" );
			$anchor = $this->linkOpts;
			// rendering column by column
			$columnParams = array();
			foreach( $this->cols as $col ) {
				if( $col->colName == self::ADD ) {
					$th = NHtml::el( "th class='center vcenter nopadding hover add'" )->add( $col->name );
					$col->colName = "";
				} else {
					if( isset( $col->params['options'] ) )
						$columnParams[$this->getUniqueId()]['columnInfo'][$col->colName] = $col->params['options'];
				
					if( $col->params['order'] ) {
						$a = NHtml::el( "a" );
						$a->class[] = "ajax";
						if( $col->colName == $this->params['order'] )
							$a->class[] = $this->params['sorting'];
						
		
						$anchor['order'] = $col->colName;
						$anchor['sorting'] = $this->params['order'] == $col->colName && $this->params['sorting'] == "asc" ? 
																	"desc" : "asc";
						 
						$a->href = $this->link( "reset!", $anchor );
						
						if( $t = $this->params['translator'] )
							$col->name = $t->translate( $col->name );

						$a->add( $col->name );
					} else {
						$a = NHtml::el( "span" )->add( $col->name );
					}	
					$th = clone $col->params['headerElement'];
					$th->add( $a );
				
					$th->style['width'] = $col->params['width']."px !important";
	
					if( $col->params['filter'] ) {
						$filter = "";
						if( is_array( $col->params['filter'] ) ) {
							$filter = NHtml::el( "select class=filter" )->name( $col->colName );
							foreach( $col->params['filter'] as $f => $v ) {
								$f = (string) $f;
								$filter->add( NHtml::el( "option value=$f".(@$this->params["filter"][$col->colName]=="$f" ?" selected":"") )->add( $v ) );
							}
						} else {
							$filter = NHtml::el( "input" );
							$filter->class[] = "filter";
							if( $col->params['type'] == self::DATE ) {
								$filter->class[] = "dateFilter";
								$th->{"data-format"} = $col->params['dateFormat'];
							}
							$filter->name( $col->colName );
							if( @$this->params["filter"][$col->colName] )
								$filter->value = $this->params["filter"][$col->colName];
						}
						$th->add( $filter );	
					}		
				}
				$header->add( $th );
			}
			
			return $header;
		}
		
		/**
		 * renders the body
		 * @return NString
		 */
		public function renderBody() {
		
			$body = NHtml::el( "tbody" );
			$body->class[] = "tabella-body";

			if( $this->params['filter'] )
			foreach( $this->params['filter'] as $col => $val ) {
				if( "$val" == "" ) 
					continue;
				$fh = $this->cols[$col]->params['filterHandler'];
				
				$this->source->where( $fh( $val, '`'.$col.'`' ) );
			}

			$this->count = $this->source->count();
			$this->source
					->applyLimit( $this->params['limit'], ($this->params['offset']-1)*$this->params['limit'] )
					->orderBy( $this->params['order'], $this->params['sorting'] );

			foreach( $this->source->fetchAll() as $row ) {
				$rR = $this->params['rowRenderer'];
				$r = $rR( $row );

				if( isset($row->{$this->params['id_table']}) )
					$r->{"data-id"} = $row->{$this->params['id_table']};
	
				foreach( $this->cols as $col ) {
					if( $col->params['type'] == self::DELETE ) {
						$r->add( NHtml::el( "td class=delete" ) );
						continue;
					} 
		
					if( !isset( $row[$col->colName]) ) {
						$str = "";
					} else {
						$str = $row[$col->colName];
						if( $t = $this->params['translator'] )
							$str = $t->translate( $str );
					}
					
					// in case of own rendering
					if( $c = $col->params['renderer'] ) {
						$c = $c( $row );
					
					// or default rendering method
					} else {
						$c = NHtml::el( "td" );
						$c->style['width'] = $col->params['width']."px";
						
						$c->class = array();
						if( $col->params['editable'] ) {
							$c->class[] = "editable";
							$c->{"data-editable"} = $str;
							$c->{"data-type"} = $col->params['type'];
							$c->{"data-name"} = $col->colName;
						}
						
						switch( $col->params['type'] ) {
							case self::TEXT:
								$str = $col->params['truncate'] ? NStrings::truncate( $str, $col->params['truncate'] ) : $str;
								break;
							case self::TIME: 	
								// we format the time online if defined as UNIX timestamp
								if( is_numeric( $str ) )							
									$str = strftime( $col->params['timeFormat'], $str ); 
								break;
							case self::DATE: 								
								if( is_numeric( $str ) )							
									$str = strftime( $col->params['dateFormat'], $str ); 
								$c->{"data-format"} = $col->params['dateFormat'];
								break;
							case self::DATETIME: 								
								if( is_numeric( $str ) )							
									$str = strftime( $col->params['datetimeFormat'], $str ); 
								break;
							case self::SELECT:
								break;
						}
						$c->add( $str )->{"data-shown"} = $str;
						
						$c->class = array_merge( $c->class,
									is_array( $col->params['class'] ) ? $col->params['class'] : array( $col->params['class'] ) );
						
					}
						
					$r->add( $c );
				}
				$body->add( $r );
			}
			return $body;	
		}
		
		/**
		 * renders the footer
		 * @return NString
		 */		
		public function renderFooter() {
			$footer = NHtml::el( 'div class="tabella-footer"' );

			$pages = ceil( $this->count / $this->params['limit'] );

			$count = 10;

			if( $pages > 1 ) {
				if( $this->params['offset'] != 1 ) {
					$fst = NHtml::el( "a" )->href( $this->link( "reset!", array( "offset" => 1 ) + $this->linkOpts ) );
				} else {
					$fst = NHtml::el( "span" );
				}
				
				$footer->add( $fst->add( "«" ) );
					
				$range = range( max( 1, $this->params['current'] - $count), min( $pages, $this->params['offset'] + $count));
			
				$quotient = ( $pages - 1 ) / $count;
				for( $i = 0; $i <= $count; $i++ ) {
					$range[] = round( $quotient * $i ) + 1;
				}
				
				sort( $range );
				$range = array_values( array_unique( $range ) );
			
				foreach( $range as $i ) {
					$a = NHtml::el( 'a' )->add( $i )->href( $this->link( "reset!", array( "offset" => $i ) + $this->linkOpts ) );
					$a->class[] = "ajax";
					if( $i == $this->params['offset'] )
						$a->class[] = "selected";
						
					$footer->add( $a );
				}
				if( $this->params['offset'] != $pages ) {
					$last = NHtml::el( "a" )->href( $this->link( "reset!", array( "offset" => $pages ) + $this->linkOpts ) );
				} else {
					$last = NHtml::el( "span" );
				}
				
				$footer->add( $last->add( "»" ) );
			}
			
			return $footer;
		}
		
		/**
		 * invalidating the control
		 */
		public function handleReset() {
			$this->invalidateControl();
		}
		
		/**
		 * react on inline edit
		 */
		public function handleSubmit() {
			
			$submitted = $this->presenter->getRequest()->getPost();
			$payload = array();
			foreach( $this->presenter->getRequest()->getPost() as $key => $val ) {
				if($key ==  $this->getUniqueId()."-id"){
				    $key = $this->getUniqueId()."-".$this->params['id_table'];
				}

				if( strpos( $key, $this->getUniqueId() ) !== false ) {
					$payload[str_replace( $this->getUniqueId()."-", "", $key )] = $val;
				}
			}
			if( @$id = $payload['deleteId'] ) {
				$fn = $this->params['onDelete'];
				$fn( $id );
			} else
			
			if( @$fn = $this->params['onSubmit'] ) {
				$fn( $payload );
			}
			$this->invalidateControl();
		}
	}