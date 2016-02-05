<?php
class ArticleException extends Exception{}

class ArticleControl extends BaseControl {
	
	
	public function render($id_node, $id_menu_item, $full = false)
	{
		
		$template = $this->template;
		$template->article = $this->getService('Article')->get($id_node);
		
		
		$template->link = $this->getPresenter()->link(':Front:Article:default', array('id'=>$id_node, 'id_menu_item'=>$id_menu_item));
				
//		var_dump($template->link);exit;
		$template->setFile(dirname(__FILE__) . '/ArticleAnnotation.phtml');
		
			
		$template->render();
	}
	
	
	
	protected function createComponent($name){

		switch($name){
			case 'comment':
				return new CommentControl;
				break;
			case 'attachment':
				return new AttachmentControl;
				break;
		}
	}
	
}