<?php
class MyExtractor extends NetteGettextExtractor{
	function __construct(){
		parent::__construct();
	}
	
	function getData(){
		$this->setupForms()->setupDataGrid(); // provede nastavení pro formuláře a DataGrid
		$this->scan(APP_DIR); // prohledá všechny aplikační soubory		
		return $this->data;
	}
}