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

class TreeViewRenderer
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
			'container' => 'ul'
			),
		'node' => array(
			'icon' => NULL,
			'container' => 'li',
			'.selected' => 'current',
			'.expanded' => 'expanded',
			),
		'link' => array(
			'node' => 'a',
			'collapse' => 'a',
			'expand' => 'a',
			'.ajax' => 'ajax',
			),
		);

	public function render(TreeView $tree)
	{
		if($this->tree!==$tree)
			$this->tree=$tree;
		$snippetId=$this->tree->getSnippetId();
		$html=$this->renderNodes($this->tree->getNodes(), 'nodes root');
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
		$snippetId=$node->getSnippetId();
		$nodeContainer=$this->getWrapper('node container');
		$nodeContainer->id=$snippetId;
		if ($this->tree->getSelected()==$node->name)
			$nodeContainer->addClass($this->getValue('node .selected'));
		if ($node->getState()==TreeViewNode::EXPANDED && count($nodes)>0)
			$nodeContainer->addClass($this->getValue('node .expanded'));
		if (count($nodes)>0) {
			switch($node->getState()) {
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
		return $el;
	}

	protected function getWrapper($name)
	{
		$data=$this->getValue($name);
		if (empty($data))
			return $data;
		return $data instanceof Html ? clone $data : Html::el($data);
	}

	protected function getValue($name)
	{
		$name=explode(' ', $name);
		$data=&$this->wrappers[$name[0]][$name[1]];
		return $data;
	}
}