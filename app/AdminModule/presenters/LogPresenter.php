<?php
class Admin_LogPresenter extends Admin_BasePresenter{
	
	protected function createComponent($name)
	{
		switch($name){
			case 'logGrid':
//		    $grid = new DataGrid();
//		    $model = new LogModel;
//		    $grid->bindDataTable($model->getDataSource());
//		    return $grid;
		   	break;
			
			default:
				return parent::createComponent($name);
				break;
		}
	}

	//BASIC
		protected function createComponentBasicTabella( $name ) {
			// asi nepotřebuje komentář :-)
			$model = new LogModel;
			
			$grid = new Tabella( $model->getDataSource(), 
				array(
					'sorting'=>'id_log', 
					'order'=>'id_log',
					"onSubmit" => function( $post ) {
				print_r($post);
	    				LogModel::save( $post, $post['id_log'] );
					})
			);
			
			$grid->addColumn( "id_log", "id_log", array( "width" => 30 ) );
			$grid->addColumn( "name_modul", "name_modul", array( "width" => 100, "editable" => true ) );
			$grid->addColumn( "description", "description", array( "width" => 100 ) );
			$grid->addColumn( "value", "value", array( "width" => 100 ) );
			$grid->addColumn( "query", "query", array( "width" => 100 ) );
			$grid->addColumn( "date", "date", array( "width" => 100 ) );
			$grid->addColumn( "ip", "ip", array( "width" => 100 ) );

		$this->addComponent( $grid, $name );		    
		}//BASIC
		
	function beforeRender(){
		parent::beforeRender();
//		$this['header']['css']->addFile('/DataGrid/datagrid.css');
//		$this['header']['js']->addFile('/jquery/datagrid.js');		
//		
		$this['header']['css']->addFile('../jscripts/tabella/nette.tabella.css');
		$this['header']['js']->addFile('/tabella/nette.tabella.js');
	}
	
	
}