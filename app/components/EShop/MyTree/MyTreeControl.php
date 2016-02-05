<?php

class MyTreeControl extends NControl{


    function render(){
		$tree = CategoryModel::getTreeAssoc($this->getPresenter()->id_lang);

		$template = $this->template;
		$template->setFile(dirname(__FILE__).'/default.phtml');
		$template->tree = $tree;
		$template->render();
    }

    function handleDeleteCategory($id_category){
		
		CategoryModel::delete($id_category);
		$this->redirect('this');
    }

    function handleMoveCategoryUp($id_category){
		$m = new CategoryModel($this->getPresenter()->id_lang);

		$m->moveUp($id_category);

		$this->redirect('this');
    }

    function handleMoveCategoryDown($id_category){
		$m = new CategoryModel($this->getPresenter()->id_lang);

		$m->moveDown($id_category);

		$this->redirect('this');
    }

}