<?php
/**
 * EditableTree renderer
 *
 * @author     Pavol Hluchý (Lopo)
 */
use Nette\Object,
	Nette\Web\Html,
	Nette\Templates\TemplateHelpers;

class EditableTree
extends Object
implements ITreeViewRenderer
{
	/** @var TreeView */
	protected $tree;
	/** @var string nazov stlpca riadiaceho checkboxy */
	public $checkColumn='visible';
	/** @var string JS spojenia jQuery plguinu s kontajnerom */
	public $linkingJS="$('#%s').NestedSortable({
		handle: '.handler',
		onChange: function(serialized) {
			return jQuery.ajax({
				url: '%s',
				data: serialized[0].hash
				});
			},
		accept: '%s-item',
		opacity: 0.8,
		helperclass: 'helper',
		nestingPxSpace: 20,
		currentNestingClass: 'current-nesting',
		fx: 400,
		revert: true,
		autoScroll: false
		});";
	/** @var string event zmeny struktury */
	public $onChange='TChange';
	/** @var string JS spojenia jQuery pluginu editacie nazvu */
	public $editJS="$(function() {
	$('.%s_click').editable('%s', {
		indicator: \"<img src='/images/spinner.gif' alt='spinner'>\",
		tooltip: 'Klikni pre upravenie ...',
		placeholder: 'žiadny',
		submit: 'nastav',
		type: 'text',
		style: 'inherit'
		});
	});";
	/** @var string event zmeny nazvu */
	public $onEdit='EditTypeName';
	/** @var string img pridania node */
	public $img_add='/images/icons/page_add.png';
	/** @var string img upravy node */
	public $img_edit='/images/icons/page_edit.png';
	/** @var string img zrusenia node */
	public $img_cancel='/images/cancel.png';
	/** @var string img posuvneho bodu */
	public $img_move='/images/icons/1268227056_arrow_move.png';
	/** @var string styl pre node */
	public $styleItem=".%s-item > div {background: #f8f8f8; margin: 0.25em 0 0 0;}";
	/** @var bool pouzit CheckBoxy */
	public $useCB=TRUE;
	/** @var string event pre pridanie polozky */
	public $onAdd='addType';
	/** @var string event pre zmazanie polozky */
	public $onDel='remType';
	/** @var string event pre zmenu stavu CB */
	public $onCB='TVis';
	/** @var bool pouzit jQuery UI */
	public $useUI=TRUE;
	/** @var bool povolit editaciu mena polozky */
	public $enableEditName=TRUE;
	/** @var bool povolit pridanie polozky */
	public $enableAdd=TRUE;
	/** @var bool povolit odobranie polozky */
	public $enableDel=TRUE;

	/**
	 * @param TreeView
	 * @return Nette\Web\Html
	 */
	public function render(TreeView $tree)
	{
		if ($this->tree!==$tree)
			$this->tree=$tree;
		$snippetId=$this->tree->getName();
		$prez=$this->tree->getPresenter();

		$treeContainer=Html::el('div', array('class'=>'etwrap', 'style'=>'border: 1px solid #BBBBBB; padding: 1em 1em 1em 1em;')); //TODO: class 'etwrap' nikde nepouzite
		$css=Html::el('style', array('type' => 'text/css'));
		$css->add(sprintf($this->styleItem, $snippetId));
		if ($this->enableAdd)
			$css->add(".etbtn_add { background-image: url('".$this->img_add."'); background-color: transparent; border: none; width: 16px; height: 16px;}");
		if ($this->enableEditName)
			$css->add(".etbtn_edit { background-image: url('".$this->img_edit."'); background-color: transparent; border: none; width: 16px; height: 16px;}");
		if ($this->enableDel)
			$css->add(".etbtn_del { background-image: url('".$this->img_cancel."'); background-color: transparent; border: none; width: 16px; height: 16px;}");
		$treeContainer->add($css);

		if ($this->enableEditName)
			$treeContainer->add(
				Html::el('script', array('type' => 'text/javascript', 'charset' => 'utf-8'))
					->add(sprintf($this->editJS, $snippetId, $prez->link($this->onEdit.'!')))
				);
		$treeContainer->add($this->renderNodes($this->tree->getNodes(), Html::el('ul', array('id' => $snippetId, 'class' => 'page-list', 'style' => "list-style: none; margin: 0; padding: 0; display: block;"))));
		$treeContainer->add(
			Html::el('script', array('type' => 'text/javascript', 'charset' => 'utf-8'))
				->add(sprintf($this->linkingJS, $snippetId, $prez->link($this->onChange.'!'), $snippetId))
			);
		if ($this->useUI) {
			$treeContainer->add(Html::el('style', array('type' => 'text/css'))->add(".ui-dialog .ui-state-error { padding: .3em;}"));
			$treeContainer->add(
				Html::el('script', array('type' => 'text/javascript', 'charset' => 'utf-8'))
					->add(
						"$(function() {"
							."var id=$('#$snippetId-id'),meno=$('#$snippetId-meno'),allFields=$([]).add(id).add(meno),tips=$('.validateTips');\n"
							."function updateTips(t){ tips.text(t).addClass('ui-state-highlight');setTimeout(function() { tips.removeClass('ui-state-highlight', 1500);}, 500);}\n"
							."function checkLength(o,n,min,max){ if ( o.val().length > max || o.val().length < min ) { o.addClass('ui-state-error');updateTips('Length of ' + n + ' must be between '+min+' and '+max+'.');return false;}else return true;}\n"
							."function checkRegexp(o,regexp,n){ if (!(regexp.test(o.val()))){ o.addClass('ui-state-error');updateTips(n);return false;}else return true;}\n"
							."$('#$snippetId-dform').dialog({"
								."autoOpen: false,"
								."height: 200,"
								."width: 250,"
								."modal: true,"
								."buttons: {"
									."'Nastaviť': function() {"
										."var bValid=true;"
										."allFields.removeClass('ui-state-error');"
										."bValid=bValid && checkLength(meno, 'meno', 2, 50);"
										."if (bValid) {"
											."jQuery.ajax({"
												."url: $(this).dialog('option', 'nop')=='add'? ".TemplateHelpers::escapeJs($prez->link($this->onAdd.'!'))." : ".TemplateHelpers::escapeJs($prez->link($this->onEdit.'!')).","
												."type: 'post',"
												."data: {"
													."id: $(this).dialog('option', 'idval'),"
													."value: meno.val()"
													."},"
												."complete: function(data){ window.location.reload();}"
												."});"
											."$(this).dialog('close');"
											."}"
										."},"
									."Cancel: function() {"
										."$(this).dialog('close');"
										."}"
									."},"
								."open: function() {"
									."var dlgvals=$(this).dialog('option', 'dlgvals');"
									."id.val(dlgvals['id']);"
									."$(this).dialog('option', 'idval', dlgvals['id']);"
									."if ($(this).dialog('option', 'nop')=='update')"
										."meno.val(dlgvals['meno']);"
									."},"
								."close: function() {"
									."allFields.val('').removeClass('ui-state-error');"
									."}"
								."});\n"
							."$('#$snippetId-cnode').button().click(function(){ $('#$snippetId-dform').dialog('option', 'dlgvals', { id: 0, meno: ''});$('#$snippetId-dform').dialog('option', 'nop', 'add');$('#$snippetId-dform').dialog('open');});\n"
							."});\n"
							)
				);
			$fset=Html::el('fieldset', array('style'=>'padding:0; border:0;'));
			$fset->add(Html::el('input', array('type'=>'hidden', 'name'=>$snippetId.'-id', 'id'=>$snippetId.'-id')));
			$fset->add(Html::el('label', array('for'=>$snippetId.'-meno', 'style'=>'display: block;'))->add('Názov'));
			$fset->add(Html::el('input', array(
												'type' => 'text',
												'name' => $snippetId.'-meno',
												'id' => $snippetId.'-meno',
												'class' => 'text ui-widget-content ui-corner-all',
												'style' => 'display:block; width:95%;'
												)));
			$treeContainer->add(
				Html::el('div', array('id'=>$snippetId.'-dform', 'title'=>$snippetId, 'style'=>'font-size: 62.5%;'))
					->add(Html::el('p', array('class'=>'validateTips', 'style'=>'border: 1px solid transparent; padding: 0.3em;')))
					->add(Html::el('form')->add($fset))
				);
			$treeContainer->add(Html::el('button', array('id'=>$snippetId.'-cnode'))->add('Pridať položku'));
			}
		else
			if ($this->enableAdd)
				$treeContainer->add(Html::el('input', array(
														'type' => 'button',
														'class' => 'etbtn_add',
														'onClick' => "jQuery.ajax({ url: '".$prez->link($this->onAdd.'!')."', type: 'POST', complete: function(data){ window.location.reload();}});"
														)));
		return $treeContainer;
	}

	/**
	 * @param TreeViewNode nodes
	 * @param Nette\Web\Html wrapper
	 * @return Nette\Web\Html
	 */
	public function renderNodes($nodes, $wrapper=NULL)
	{
		if ($wrapper===NULL)
			$nodesContainer=Html::el('ul', array('class' => 'page-list', 'style' => "list-style: none; margin: 0; padding: 0; display: block;"));
		else
			$nodesContainer=$wrapper;
		foreach ($nodes as $n) {
			$child=$this->renderNode($n);
			if ($child!==NULL)
				$nodesContainer->add($child);
			}
		return $nodesContainer;
	}

	/**
	 * @param TreeViewNode
	 * @return Nette\Web\Html
	 */
	public function renderNode(TreeViewNode $node)
	{
		$nodes=$node->getNodes();
		$nodeContainer=Html::el('li', array(
											'class' => 'clear-element '.$this->tree->getName().'-item sort-handle left',
											'id' => $node->getDataRow()->id,
											'style' => 'clear: both; text-align: left;'
											));
		$link=$this->renderLink($node, 'nodeLink');
		if ($link!==NULL)
			$nodeContainer->add($link);
		$this->tree->onNodeRender($this->tree, $node, $nodeContainer);
		if (count($nodes)>0) {
			$nodesContainer=$this->renderNodes($nodes);
			if ($nodesContainer!==NULL)
				$nodeContainer->add($nodesContainer);
			}
		$html=isset($nodeContainer)? $nodeContainer : $nodesContainer;
		if ($node->isInvalid())
			$this->tree->getPresenter()->getPayload()->snippets[$node->getSnippetId()]=(string)$html;
		return $html;
	}

	/**
	 * @param TreeViewNode
	 * @param string name
	 * @return Nette\Web\Html
	 */
	public function renderLink(TreeViewNode $node, $name)
	{
		$pres=$this->tree->getPresenter();
		$id=$node->getDataRow()->id;
		$nname=$this->tree->getName();
		$link=$node[$name];
		$label=$link->getLabel();
		$el=Html::el('div');
		$el->add(Html::el('img', array('src'=>$this->img_move, 'height'=>'16', 'class'=>'handler', 'alt'=>'move', 'style'=>'cursor: move;')));
		if ($this->useCB) {
			$cbx=Html::el('input', array('type'=>'checkbox', 'name'=>$nname.'[]', 'id'=>'cbx-'.$id, 'style'=>'cursor: default;'));
			$ck=$this->checkColumn;
			if ($node->getDataRow()->$ck)
				$cbx->checked='checked';
			$url=$pres->link($this->onCB.'!');
			$cbx->onChange="jQuery.ajax({ url: '$url', type: 'POST', data: { tid: $id, vis: this.checked}})";
			$el->add($cbx);
			}
		if (!$this->enableEditName)
			$el->add($label);
		else {
			if ($this->useUI)
//				$el->add($label);
				$el->add(
						Html::el('span', array(
												'style' => 'cursor: text;',
												'onClick' => "$('#$nname-dform').dialog('option', 'dlgvals', { id: $id, meno: '$label'}); $('#$nname-dform').dialog('option', 'nop', 'update'); $('#$nname-dform').dialog('open');"
												))
							->add($label)
						);
			else
				$el->add(
						Html::el('span', array(
												'class' => $nname.'_click',
												'id' => $id
												))
							->add($label)
						);
			}
		if ($this->enableDel)
			$el->add(Html::el('input', array(
										'type' => 'button',
										'class' => 'etbtn_del',
										'onClick' => "jQuery.ajax({ url: '".$pres->link($this->onDel.'!', array('id' => $id))."', type: 'post', complete: function(data){ window.location.reload();}});"
										)));
		if ($this->useUI) {
			if ($this->enableEditName) {
/* editBTN
				$el->add(Html::el('input', array(
											'type' => 'button',
											'class' => 'etbtn_edit',
											'onClick' => "$('#$nname-dform').dialog('option', 'dlgvals', { id: $id, meno: '$label'}); $('#$nname-dform').dialog('option', 'nop', 'update'); $('#$nname-dform').dialog('open');"
											)));
*/
				$el->add(Html::el('input', array(
											'type' => 'button',
											'class' => 'etbtn_add',
											'onClick' => "$('#$nname-dform').dialog('option', 'dlgvals', { id: $id, meno: '$label'}); $('#$nname-dform').dialog('option', 'nop', 'add'); $('#$nname-dform').dialog('open');"
											)));
				}
			}
		elseif ($this->enableAdd)
				$el->add(Html::el('input', array(
											'type' => 'button',
											'class' => 'etbtn_add',
											'onClick' => "jQuery.ajax({ url: '".$pres->link($this->onAdd.'!', array('id' => $id))."', type: 'post', complete: function(data){ window.location.reload();}});"
											)));
		return $el;
	}

	/**
	 * spracovanie JSON reprezentacie stromu do PHP pola
	 * @param string tree (JSON)
	 * @return array
	 */
	static public function parseTree($tree)
	{
		$rtree=array();
		foreach ($tree as $node)
			$rtree[$node['id']]=isset($node['children'])? self::parseTree($node['children']) : null;
		return $rtree;
	}
}
