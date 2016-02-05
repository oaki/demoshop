<?php
/**
 * TreeView control
 *
 * Copyright (c) 2009, 2010 Roman Novák
 *
 * This source file is subject to the New-BSD licence.
 *
 * For more information please see http://nettephp.com
 *
 * @copyright  Copyright (c) 2009, 2010 Roman Novák
 * @license    New-BSD
 * @link       http://nettephp.com/cs/extras/treeview
 * @version    0.6.0a
 */

/**
 * TreeView renderer.
 *
 * @author     Roman Novák
 * @copyright  Copyright (c) 2009, 2010 Roman Novák
 * @package    nette-treeview
 */

use Nette\Object,
	Nette\Web\Html;

class SortableTreeViewRenderer
extends Object
implements ITreeViewRenderer
{
	/** @var TreeView */
	protected $tree;

	public $wrappers=array(
		'tree' => array(
			'container' => 'div'
			),
		'nodes' => array(
			'root' => 'ul',
			'#root' => 'sortableTree',
			'container' => 'ul',
			'.list' => 'page-list',
			),
		'node' => array(
			'icon' => null,
			'container' => 'li',
			'.item' => 'page-item',
			'subcontainer' => 'span',
			'.subcontainer' => 'node',
			),
		'link' => array(
			'node' => 'a',
			'collapse' => 'a',
			'expand' => 'a',
			'.ajax' => 'ajax',
			),
		'move' => array(
			'container' => 'span',
			'.class' => 'move',
			),
		'delete' => array(
			'container' => 'a',
			'.class' => 'delete',
			'.ajax' => 'ajaxDelete',
			'subcontainer' => 'span',
			),
		);

	public $script="$(function() {
		$('#%s').NestedSortable({
		onChange: function(serialized) {
			return jQuery.ajax({
				url: '%s',
				data: serialized[0].hash
				});
			},
		accept: '%s',
		opacity: 0.8,
		helperclass: 'helper',
		nestingPxSpace: '20',
		currentNestingClass: 'current-nesting',
		fx: 400,
		revert: true,
		autoScroll: false
		});
	});";
	public $onChange='nodeMove';
	public $onDelete='nodeDelete';
	public $onEdit='edit';

	public function render(TreeView $tree)
	{
		if ($this->tree!==$tree)
			$this->tree=$tree;
		$snippetId=$this->tree->getSnippetId();

		$html=$this->renderNodes($this->tree->getNodes(), 'nodes root');
		$html->add(
				Html::el('script', array('type'=>'text/javascript', 'charset'=>'utf-8'))
					->add(sprintf($this->script, $this->getValue('nodes #root'), $this->tree->getPresenter()->link($this->onChange.'!'), $this->getValue('node .item')))
				);
		if ($this->tree->isControlInvalid() && $this->tree->getPresenter()->isAjax())
			$this->tree->getPresenter()->getPayload()->snippets[$snippetId]=(string)$html;
		if (!$this->tree->getPresenter()->isAjax()) {
			$treeContainer=$this->getWrapper('tree container');
			$treeContainer->id=$snippetId;
			$treeContainer->add($html);
			return $treeContainer;
			}
	}

	public function renderNodes($nodes, $wrapper='nodes container')
	{
		$nodesContainer=$this->getWrapper($wrapper);
		if ($wrapper=='nodes root')
			$nodesContainer->id=$this->getValue('nodes #root');
		$nodesContainer->class=$this->getValue('nodes .list');
		foreach ($nodes as $n) {
			$child=$this->renderNode($n);
			if (NULL!==$child)
				$nodesContainer->add($child);
			}
		return $nodesContainer;
	}

	public function renderNode(TreeViewNode $node)
	{
		$nodes=$node->getNodes();
		$snippetId=$node->getDataRow()->id;
		$nodeContainer=$this->getWrapper('node container');
		$nodeContainer->id=$snippetId;
		$nodeContainer->class=$this->getValue('node .item');
		if (count($nodes)>0) {
			switch ($node->getState()) {
				case TreeViewNode::EXPANDED:
					$stateLink=$this->renderLink($node, 'stateLink', 'link collapse');
					break;
				case TreeViewNode::COLLAPSED:
					$stateLink=$this->renderLink($node, 'stateLink', 'link expand');
					break;
			}
			if (NULL!==$stateLink)
				$nodeContainer->add($stateLink);
			}
		else {
			$icon=$this->getWrapper('node icon');
			if (NULL!==$icon)
				$nodeContainer->add($icon);
			}
		$link=$this->renderLink($node, 'nodeLink');
		if (NULL!==$link)
			$nodeContainer->add($link);
		$this->tree->onNodeRender($this->tree, $node, $nodeContainer);
		if (TreeViewNode::EXPANDED===$node->getState() && count($nodes)>0) {
			$nodesContainer=$this->renderNodes($nodes);
			if (NULL!==$nodesContainer)
				$nodeContainer->add($nodesContainer);
			}
		$html=isset($nodeContainer)? $nodeContainer : $nodesContainer;
		if ($node->isInvalid())
			$this->tree->getPresenter()->getPayload()->snippets[$snippetId]=(string)$html;
		return $html;
	}

	public function renderLink(TreeViewNode $node, $name, $wrapper='link node')
	{
		$el=$this->getWrapper($wrapper);
		if (NULL===$el)
			return NULL;
		$link=$node[$name];
		if ($link->useAjax) {
			$class=$el->class;
			$ajaxClass=$this->getValue('link .ajax');
			if (!empty($class) && !empty($ajaxClass))
				$ajaxClass=$class.' '.$ajaxClass;
			$el->class=$ajaxClass;
			}
		$el->setText($link->getLabel());
		$el->href($link->getUrl());
		if ($name!='nodeLink') {
			$span=Html::el('span');
			$span->class='collapsable';
			$span->add($el);
			return $span;
			}
		else {
			$sub=$this->getWrapper('node subcontainer');
			$sub->class=$this->getValue('node .subcontainer');
			$sub->add($el);

			$move=$this->getWrapper('move container');
			$move->addClass($this->getValue('move .class'));
			//TODO: nefunguje v nove verzi jquery?
			//$move->addClass('handler');

			$delete=$this->getWrapper('delete container');
			$delete->href=$this->tree->getPresenter()->link($this->onDelete.'!', $node->getDataRow()->id);
			$delete->class=$this->getValue('delete .ajax');
			$delete->addClass($this->getValue('delete .class'));
			$deleteSpan=$this->getWrapper('delete subcontainer');
			$deleteSpan->add('delete');
			$delete->add($deleteSpan);

			$edit=Html::el('a');
			$edit->href=$this->tree->getPresenter()->link($this->onEdit, $node->getDataRow()->id);
			$edit->addClass('edit');
			$editSpan=Html::el('span');
			$editSpan->add('edit');
			$edit->add($editSpan);

			$sub->add($move);
			$sub->add($delete);
			$sub->add($edit);

			return $sub;
			}
		return $el;
	}

	protected function getWrapper($name)
	{
		$data=$this->getValue($name);
		if (empty($data))
			return $data;
		return $data instanceOf Html ? clone $data : Html::el($data);
	}

	protected function getValue($name)
	{
		$name=explode(' ', $name);
		$data=&$this->wrappers[$name[0]][$name[1]];
		return $data;
	}
}