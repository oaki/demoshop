<?php

/**
 * Description of Admin_LangPresenter
 *
 * @author oaki
 */
class Admin_TranslatePresenter extends Admin_BasePresenter {

	function  beforeRender() {
	    parent::beforeRender();

	    $this['header']['css']->addFile('../jscripts/tabella/nette.tabella.css');
	    $this['header']['js']->addFile('/tabella/nette.tabella.js');
	}

	function renderDefault(){
		
		
	}


	function  createComponent($name) {
		 switch ($name){
			 case 'translateTabella':

				$datasource = Lang::getDatasourceGroupByKey();
				 

				$grid = new Tabella( $datasource,
					array(
						'sorting'=>'desc',
						'order'=>'key',
						"onSubmit" => function( $post ) {
							Lang::save(@$post['key'], @$post);

							Lang::invalidateCache();
							
						}						
					)
				);

				$grid->addColumn( "Klúč", "key",
					array(
						"width" => 50,
						'editable' => true,
					)
				);

				foreach(Lang::getAll() as $l){

					$grid->addColumn( $l['iso'], $l['iso'],
						array(
							"width" => 50,
							'editable' => true,
						)
					);
				}

				$this->addComponent( $grid, $name );
			 break;

		 default:
			return parent::createComponent($name);
			 break;
		 }
	}//end createComponent

}