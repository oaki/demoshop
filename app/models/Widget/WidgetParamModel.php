<?php

class WidgetParamModel extends BaseModel{
	
	function __construct(DibiConnection $connection, ICacheStorage $cache) {
		parent::__construct($connection, $cache);
		$this->table = 'widget_param';
	}
	
	function fetchAssoc( $id_widget ){
		return $this->getFluent()->where( 'id_widget = %i',$id_widget)->fetchAssoc('id_widget_param');
	}
	
	function isExist($id_widget_param){
		return $this->connection->query("SELECT 1 FROM ".$this->table." WHERE ".$this->getTableIdName()."=%i",$id_widget_param)->fetchSingle();
	}
	
	function getIdentifyByIdWidgetAndName($id_widget, $name){
		return $this->getFluent()->where('id_widget = %i',$id_widget,"AND name = %s",$name)->fetch();
	}
}