<?php

class WidgetModel extends BaseModel{
	
	private $widgetParamModel;
	
	function __construct(DibiConnection $connection, ICacheStorage $cache, WidgetParamModel $widgetParamModel) {
		parent::__construct($connection, $cache);
		$this->table = 'widget';
		$this->widgetParamModel = $widgetParamModel;
		
		$this->__cronDelete();
	}
	
	function insertNew(){		
		return $this->insert( array('addDate'=>new DibiDateTime, 'added'=>0,'sequence'=>$this->getNextSequence()) );
	}
	
	function getByIdentifier($identifier){
		$id = $this->getFluent()->removeClause('select')->select('id_widget')->where('identifier = %s',$identifier)->fetchSingle();
		return $this->get($id);
	}
	
	function update($values, $id) {
		$values = (array)$values;
		foreach($values['params'] as $id_widget_param=>$value){
			
			if($this->widgetParamModel->isExist($id_widget_param)){
				$this->widgetParamModel->update($value,$id_widget_param);
			}else{
				$this->widgetParamModel->insert(array('id_widget'=>$id)+(array)$value);
			};			
		}
		
		unset($values['params']);
		
		$values['added'] = 1;
		parent::update($values, $id);
	}
	
	function get( $id ){
		$val = $this->getFluent()->where( $this->getTableIdName().'=%i',$id)->fetch();
		$val['params'] = $this->widgetParamModel->fetchAssoc($id);
		return $val;
	}
	
	function addParam( $id_widget ){
		$this->update($val, $id_widget);		
	}
	
	function getNextSequence(){
		$sequence = $this->getFluent()->removeClause('select')->select('MAX(sequence)')->fetchSingle();
		if(!$sequence)
			$sequence = 1;
		return $sequence;
	}
	
	function getFileNode($id){
		$files = new FilesNode( 'widget', $id);
		$files->type = 'all';
		return $files;
	}
	
	//vymaze vsetky vytvorene widget, ktore neboli ulozene
	function __cronDelete(){
		$ids = $this->getFluent()->removeClause('select')
				->select( $this->getTableIdName() )->where('added = 0 AND NOW() > (adddate + INTERVAL 1 DAY)')->fetchAll();
		
		if($ids){
			foreach($ids as $id){
				$fileNode = $this->getFileNode($id);
				$fileNode->deleleFiles('widget', $id);
//				$this->delete($id);
			}
		}
		
		
	}
}