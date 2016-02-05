<?php
/**
 * Tabella DibiFluent Datagrid
 *
 * This source file is subject to the "New BSD License".
 *
 * @author     Vojtěch Knyttl
 * @copyright  Copyright (c) 2010 Vojtěch Knyttl
 * @license    New BSD License
 * @link       http://tabella.knyt.tl/
 */

namespace Maite;


class Tabella extends \NControl {

	protected $source;

	protected $count;

	protected $cols;

	protected $params;

	protected $context;

	protected $defaultRowParams;

	const
		TEXT      = 'text',
		TEXTAREA  = 'textarea',
		SELECT    = 'select',
		CHECKBOX  = 'checkbox',
		DATE      = 'date',
		TIME      = 'time',
		DATETIME  = 'datetime',
		NUMBER    = 'number',
		DELETE    = 'delete',
		ADD       = 'addTabellaButton';


	public $offset, $limit, $sorting, $filter, $order;

	public static function getPersistentParams() {
		return array('offset', 'limit', 'sorting', 'filter', 'order');
	}



	/**
	 * Constructs the Tabella
	 * @param DibiDataSource
	 * @param array of default parameters
	 */
	public function __construct($params) {
		parent::__construct();

		$this->context = $params['context'];

		$this->source = $params['source'];

		$this->cols = array();

		if( !isset($params['id_table']) )
			throw new Exception('id_table is required.');

		unset($params['context']);
		unset($params['source']);
		// common default parameters
		$this->params = $params + array(
			'id_table'=> 'id',
			'offset'      => 1,         // default offset (page)
			'limit'       => 25,        // default rows on page
			'order'       => $params['id_table'],      // default ordering
			'sorting'     => 'asc',     // sorting [asc|desc]
			'filter'      => null,      // default filtering (in array)
			'onSubmit'    => null,
			'translator'  => false,
			'rowRenderer' => function($row) {
				return \NHtml::el('tr');
			},
			'rowClass' => array(),      // helper to render each row
			'userParams' => array(),
			'addedControls' => array()
		);
//		dump($this->params);
			
		// default parameters for each row
		$this->defaultRowParams = array(
			'filter'         => true,             // is to be filtered
			'truncate'       => 40,               // string truncate to length
			'order'          => true,             // orderable
			'width'          => 100,              // width of column
			'editable'       => false,            // non-editable by default
			'dateFormat'     => '%d/%m/%y',       // default datetime format
			'timeFormat'     => '%H:%M',          // default datetime format
			'datetimeFormat' => '%d/%m/%y %H:%M', // default datetime format
			'renderer'       => null,             // helper to render the column cell
			'class'          => array(),          // array of classes added to column
			'translate'      => false,            // columns are not translated by default
			'headerElement'  => \NHtml::el('th'),   // helper to render the header cell
			'type' => self::TEXT                  // default column type
		);
	}



	/**
	 * Load state (from $_GET) for the control
	 * @param array
	 */
	public function loadState(array $params) {

		$default = $this->params;		
//		dump($default);
		parent::loadState($params);
		$new_params = $this->params;
		foreach($new_params as $k=>$l){
			if($l==NULL)
				unset($new_params[$k]);
		}
		$this->params = $new_params + $default;
//		$this->params = (array) $default + $this->params;		
//		$this->params = $this->params + (array)$default;		
//		$this->params = array_merge($this->params, (array) $default);		
	}



	/**
	 * Adds a columnt to the grid
	 * @param string displayed name
	 * @param string column name (in db)
	 * @param array parameters for the column
	 */
	public function addColumn($name, $colName, $params = array()) {
		if (!is_array($params)) {
			throw(new Exception('Third argument must be an array.'));
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
//		$this->template->setFile(dirname(__FILE__).'/tabella.latte');
		$this->template->setSource('{snippet tabella}{$body}{/snippet}');
		$this->template->tabella_id = $this->getUniqueId();
		$this->template->controls = $this->params['addedControls'];
		$this->template->body =
				\NHtml::el('div', array(
					'class' => 'tabella',
					'data-id' => $this->getUniqueId(),
					'data-submit-url' => $this->link('submit!'),
					'data-params' => json_encode(array('cols' => $this->cols))))
				->add(\NHtml::el('table')->add($this->renderHeader())
				->add($this->renderBody()))
				->add($this->renderFooter())->add(\NHtml::el('br class=eol'));

		$this->template->render();
	}



	/**
	 * renders the header
	 * @return string
	 */
	public function renderHeader() {
		$header = \NHtml::el('tr');
		$anchor = array();
		// rendering column by column
		$columnParams = array();
		foreach ($this->cols as $col) {
			if ($col->colName == self::ADD) {
				$th = \NHtml::el('th class="center vcenter nopadding hover add"')->add($col->name);
				$col->colName = "";
			} else {
				if (isset($col->params['options']))
					$columnParams[$this->getUniqueId()]['columnInfo'][$col->colName] = $col->params['options'];

				if ($col->params['order'] && $col->params['type'] != self::DELETE) {
					$a = \NHtml::el("a");
					$a->class[] = "tabella_ajax";
					if ($col->colName == $this->params['order'])
						$a->class[] = $this->params['sorting'];

					$anchor['offset'] = 1;
					$anchor['order'] = $col->colName;
					$anchor['sorting'] =
						$this->params['order'] == $col->colName
						&& $this->params['sorting'] == 'asc'
						? 'desc' : 'asc';

					$a->href = $this->link("reset!", $anchor);

					if ($t = $this->params['translator'])
						$col->name = $t->translate($col->name);

					$a->add($col->name);
				} else {
					$a = $col->name ? \NHtml::el('span')->add($col->name) : '';
				}
				$th = clone $col->params['headerElement'];
				$th->add($a);

				if ($col->params['type'] != self::DELETE) {

					$th->style['width'] = $col->params['width']."px";

					if ($col->params['filter']) {
						$filter = "";
						if ($col->params['type'] == self::CHECKBOX)
							$col->params['filter'] = array('' => '', 'on' => 'on', 'off' => 'off');

						if (is_array($col->params['filter'])) {
							$filter = \NHtml::el('select class=filter')->name($col->colName);
							$pad = false;
							$pad_str = "";
							foreach ($col->params['filter'] as $f => $v) {

								$el = \NHtml::el('option');

								// disabled elements defined as array
								if (is_array($v)) {
									$v = $v[0];
									$el->disabled = true;
									$pad = true;
									$pad_str = "";
								} else {
									if ($pad)
										$pad_str = '&nbsp;&nbsp;';
								}

								// translating filtered elements if required
								if ($col->params['translate'] && ($t = $this->params['translator']))
									$v = $t->translate($v);

								$el->add($pad_str.$v);

								if (@$this->params['filter'][$col->colName]=="$f")
									$el->selected = true;

								$el->value = (string) $f;

								$filter->add($el);
							}
						} else {
							$filter = \NHtml::el('input');
							$filter->name($col->colName);
							$filter->class[] = 'filter';

							if ($col->params['type'] == self::DATE) {
								$filter->class[] = 'dateFilter';
								$th->{'data-format'} = $col->params['dateFormat'];
							}
							if (@$this->params['filter'][$col->colName])
								$filter->value = $this->params['filter'][$col->colName];
						}
						$th->add($filter);
					}
				}
			}
			$header->add($th);
		}

		return $header;
	}



	/**
	 * renders the body
	 * @return string
	 */
	public function renderBody() {

		$body = \NHtml::el('tbody');
		$body->class[] = 'tabella-body';

		if ($this->params['filter'])
		foreach ($this->params['filter'] as $column => $value) {
			if ((string) $value == '')
				continue;

			if (!isset($this->cols[$column]->params['filterHandler'])) {
				// filtering by column, which is not shown
				$fh = function($source, $value, $column) {
					return $source->where('%n = %s', $column, $value);
				};
			} else {
				$fh = $this->cols[$column]->params['filterHandler'];
			}
			$fh($this->source, $value, $column);
		}

//		dump($this->params);
		$count_source = clone($this->source);
		$this->source
				->offset(($this->params['offset']-1)*$this->params['limit'])->limit($this->params['limit'])
				->orderBy($this->params['order'], $this->params['sorting'])->fetchAll();
//		exit;
		$this->count = count($count_source);

		foreach ($this->source as $row) {
			$rR = $this->params['rowRenderer'];
			$r = $rR($row);
			
			if( isset($row->{$this->params['id_table']}) )
				$r->{"data-id"} = $row->{$this->params['id_table']};

			foreach ($this->cols as $col) {
				if ($col->params['type'] == self::DELETE) {
					$r->add(\NHtml::el("td class=delete"));
					continue;
				}

				if (!isset($row[$col->colName])) {
					$str = "";
				} else {
					$str = $row[$col->colName];
					if ($col->params['translate'] && ($t = $this->params['translator']))
						$str = $t->translate($str);
				}

				// in case of own rendering
				if ($c = $col->params['renderer']) {
					$c = $c($row);

				// or default rendering method
				} else {
					$c = \NHtml::el('td');
					$c->style['width'] = $col->params['width']."px";

					$c->class = array();
					if ($col->params['editable']) {
						$c->class[] = 'editable';
						$c->{'data-editable'} = $str;
						$c->{'data-type'} = $col->params['type'];
						$c->{'data-name'} = $col->colName;
					}

					switch($col->params['type']) {
						case self::CHECKBOX:
							$el = \NHtml::el('input type=checkbox')->disabled(true);
							$el->checked = $str ? true : false;
							$c->add($el);
							$str = '';
							break;
						case self::TEXT:
							$str = $col->params['truncate'] ? \NStrings::truncate($str, $col->params['truncate']) : $str;
							break;
						case self::TIME:
							// we format the time online if defined as UNIX timestamp
							if (is_numeric($str))
								$str = strftime($col->params['timeFormat'], $str);
							break;
						case self::DATE:
							if (is_numeric($str))
								$str = strftime($col->params['dateFormat'], $str);
							$c->{'data-format'} = $col->params['dateFormat'];
							break;
						case self::DATETIME:
							if (is_numeric($str))
								$str = strftime($col->params['datetimeFormat'], $str);
							break;
						case self::SELECT:
							if (is_array(@$col->params['options']) && isset($col->params['options'][$str]))
								$str = $col->params['options'][$str];
							break;
					}
					$c->add($str)->{'data-shown'} = $str;

					$c->class = array_merge($c->class,
								is_array($col->params['class']) ? $col->params['class'] : array($col->params['class']));

				}

				$r->add($c);
			}
			$body->add($r);
		}

		return $body;
	}



	/**
	 * renders the footer
	 * @return string
	 */
	public function renderFooter() {
		$footer = \NHtml::el('div')->class('tabella-footer');

		$pages = ceil($this->count / $this->params['limit']);

		$count = 10;

		if ($pages > 1) {
			if ($this->params['offset'] != 1) {
				$fst = \NHtml::el('a')->href($this->link('reset!', array('offset' => 1)));
			} else {
				$fst = \NHtml::el('span');
			}

			$footer->add($fst->add('«'));

			$range = range(max(1, $this->params['offset'] - $count), min($pages, $this->params['offset'] + $count));

			$quotient = ($pages - 1) / $count;
			for($i = 0; $i <= $count; $i++) {
				$range[] = round($quotient * $i) + 1;
			}

			sort($range);
			$range = array_values(array_unique($range));

			foreach ($range as $i) {
				$a = \NHtml::el('a')->add($i)->href($this->link('reset!', array('offset' => $i)));
				$a->class[] = 'tabella_ajax';
				if ($i == $this->params['offset'])
					$a->class[] = 'selected';

				$footer->add($a);
			}
			if ($this->params['offset'] != $pages) {
				$last = \NHtml::el('a')->href($this->link('reset!', array('offset' => $pages)));
			} else {
				$last = \NHtml::el('span');
			}

			$footer->add($last->add('»'));
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
		$this->invalidateControl();
		$submitted = $this->presenter->getRequest()->getPost();
		$payload = array();
		foreach ($this->presenter->getRequest()->getPost() as $key => $val) {
			if (strpos($key, $this->getUniqueId()) !== false) {
				$payload[str_replace($this->getUniqueId().'-', '', $key)] = $val;
			}
		}
		if (@$id = $payload['deleteId']) {
			$fn = $this->params['onDelete'];
			$fn($id);
		} else

		if (@$fn = $this->params['onSubmit']) {
			$fn($payload);
		}
	}


	protected function createTemplate($class = NULL) {
		return parent::createTemplate('NTemplate');
    }
}
