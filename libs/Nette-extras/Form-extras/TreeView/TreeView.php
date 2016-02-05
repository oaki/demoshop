<?php
/**
 * TreeView control
 *
 * Copyright (c) 2009 Roman Novák (http://romcok.eu)
 *
 * This source file is subject to the New-BSD licence.
 *
 * For more information please see http://nettephp.com
 *
 * @copyright  Copyright (c) 2009 Roman Novák
 * @license    New-BSD
 * @link       http://nettephp.com/cs/extras/treeview
 * @version    0.6.0a
 */

/* Changes */
/* v0.4
 * - RowLink rendering fix
 * v0.4.1
 * - array support
 * - ajaxClass
 * v0.5 - incompatible
 * - events onNameRender, onRowRender, onActionRender
 * - ul containers
 * - actions
 * v0.5.1
 * - added primaryKey
 * v0.5.2
 * - non implemented message
 * - removed typ comparsion from template
 * v0.6.0a
 * - throws exception if datasource is missing
 * - array access
 * - default parameters
 * - nodes (components
 * - expanded and ajax modes
 * - recursive mode
*/

/**
 * TreeView Control
 *
 * @author     Roman Novák
 * @copyright  Copyright (c) 2009, 2010 Roman Novák
 * @package    nette-treeview
 */

use \IDataSource;

class TreeView
extends TreeViewNode
{
	const AJAX=0;
	const EXPANDED=0;
	/******************** variables ********************/
	/** @var event */
	public $onNodeRender;
	/** @var event */
	public $onFetchDataSource;
	/** @var bool */
	public $useAjax=TRUE;
	/** @var bool */
	public $isSortable=FALSE;
	/** @var bool */
	public $rememberState=TRUE;
	/** @var bool */
	public $recursiveMode=FALSE;
	/** @var var */
	public $labelColumn='name';
	/** @var string */
	public $primaryKey='id';
	/** @var string */
	public $parentColumn='parentId';
	/** @var string */
	public $startParent;
	/** @var ITreeViewRenderer */
	protected $renderer;
	/** @var IDataSource */
	protected $dataSource;
	/** @var int */
	protected $mode=0;
	/** @var array used for expanded mode */
	protected $dataRows;
	/** @var string */
	protected $selected;

	/**
	 * Adds link
	 * @param string link destination
	 * @param string labelKey
	 * @param string paramKey
	 * @param bool useAjax
	 * @param bool presenterComponent
	 * @return TreeViewLink
	 */
	public function addLink($destination='this', $labelKey='name', $paramKey=NULL, $useAjax=FALSE, $presenterComponent=NULL)
	{
		if (NULL===$paramKey)
			$paramKey=$this->primaryKey;
		if (!empty($this->parent) && empty($presenterComponent))
			$presenterComponent=$this->parent;
		return $this['nodeLink']=new TreeViewLink($destination, $labelKey, $paramKey, $useAjax, $presenterComponent);
	}

	/**
	 * Sets data source
	 * @param mixed data source
	 * @return void
	 */
	function setDataSource(IDataSource $dataSource)
	{
		if (!$dataSource instanceof IDataSource)
			throw new \InvalidArgumentException('DataSource must implement IDataSource');
		$this->dataSource=$dataSource;
	}

	/**
	 * Gets data source
	 * @return \DibiDataSource
	 */
	function getDataSource()
	{
		return $this->dataSource;
	}

	protected function getDataRows()
	{
		if (TreeView::EXPANDED===$this->mode) {
			if (NULL===$this->dataRows)
				$this->dataRows=$this->dataSource->fetchAssoc($this->primaryKey);
			return $this->dataRows;
			}
		return parent::getDataRows();
	}

	/******************** rendering ********************/
	public function setRenderer(ITreeViewRenderer $renderer)
	{
		$this->renderer=$renderer;
	}

	public function getRenderer()
	{
		if (NULL===$this->renderer)
			$this->renderer= $this->isSortable? new SortableTreeViewRenderer() : new TreeViewRenderer();
		return $this->renderer;
	}

	public function render()
	{
		$this->load();
		$args=func_get_args();
		array_unshift($args, $this);
		echo call_user_func_array(array($this->getRenderer(), 'render'), $args);
	}

	public function __toString()
	{
		$this->load();
		$args=func_get_args();
		array_unshift($args, $this);
		return call_user_func_array(array($this->getRenderer(), 'render'), $args);
	}

	public function getState()
	{
		if (NULL===$this->state)
			$this->state=self::EXPANDED;
		return $this->state;
	}

	public function getTreeView()
	{
		return $this;
	}

	public function getMode()
	{
		return $this->mode;
	}

	public function setMode($mode)
	{
		$this->mode=(int)$mode;
	}

	/**
	 * Sets a selected item
	 *
	 * @param string
	 *
	 * @return void
	 */
	function setSelected($selected)
	{
		$this->selected=$selected;
	}

	/**
	 * Returns selected item
	 *
	 * @return string
	 */
	function getSelected()
	{
		return $this->selected;
	}

/******************** properties ********************/
}
