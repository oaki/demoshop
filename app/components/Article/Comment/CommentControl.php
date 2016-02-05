<?php
class CommentControl extends NControl{
 	
	function render($id_node){
		$this['commentForm']['id_node']->setValue($id_node);
		
		$template = $this->template;
		$template->setFile(dirname(__FILE__)."/Comment.phtml");
		$template->comments = $this->getComment($id_node);
		
		$template->counter = 0;
		
		
		$template->id_node = $id_node;
		$template->render();	
	}
	
	function getComment($id_node){
		return dibi::query("SELECT * FROM [comment] WHERE id_node=%i", $id_node,"AND status=1 ORDER BY adddate")->fetchAssoc('comment_parent,#');
	}
	
	private static function doTree(&$array, $parent){
	//	$array[]
	}
	
	function createComponentCommentForm(){
		
		$form = new NAppForm();
		
		$form->addProtection('Bohužial Váš formulár expiroval. Prosím odošlite formulár znovu.');
		
		$form->addText('name', 'Meno: ')
			->addRule(NForm::FILLED, 'Meno musí byť vyplnené.')
			->getLabelPrototype()->addId('commentFormNameLabel');
		
		$form->addTextArea('text', 'Text: ')
			->addRule(NForm::FILLED, 'Text musí byť vyplnený.')
			->getLabelPrototype()->addId('commentFormTextLabel');	
			
		$form->addSubmit('submitComment', 'Pridať');
//			->onClick[] =  'processCommentForm');
			
		$form->addHidden('id_node');
		
		$form->addHidden('comment_parent');
//			->setValue($id_node);
			
		$form->onSuccess[] = array($this, 'processCommentForm');
			
//		$form->setDefaults(array('name'=>'palo', 'text'=>'tessslks isnks'));
		return $form;
		
	}
	
	
	function createComponentCommentAnswerForm(){
		
		$form = new NAppForm();
		
		$form->addProtection('Bohužial Váš formulár expiroval. Prosím odošlite formulár znovu.', 360);
		
		$form->addText('name', 'Meno: ')
			->addRule(NForm::FILLED, 'Meno musí byť vyplnené.')
			->getLabelPrototype()->addId('commentAnswerFormNameLabel');
		
		$form->addTextArea('text', 'Text: ')
			->addRule(NForm::FILLED, 'Text musí byť vyplnený.')
			->getLabelPrototype()->addId('commentAnswerFormTextLabel');	
			
		$form->addSubmit('submitComment', 'Pridať');
//			->onClick[] =  'processCommentForm');
			
		$form->addHidden('id_node');
		
		$form->addHidden('comment_parent');
//			->setValue($id_node);
			
		$form->onSuccess[] = array($this, 'processCommentForm');
			
//		$form->setDefaults(array('name'=>'palo', 'text'=>'tessslks isnks'));
		return $form;
		
	}
	
	function processCommentForm( $form ){
		if ($form->isSubmitted()) {
			$values = $form->getValues();
		
			$this->add($form->getValues());
			
			$this->getPresenter()->flashMessage("Ďakujeme. Po schválení administrátorom bude komentár zverejnený.");
				
		}

		
		$this->redirect('this');
		
	}
	
	function add($values){
		$arr = array(
			'name'=>$values['name'],
			'text'=>$values['text'],
			'id_node'=>$values['id_node'],
			'addDate'=>new DateTime,
			'status'=>1,
			'comment_parent'=>(isset($values['comment_parent']) AND $values['comment_parent']!='')?$values['comment_parent']:NULL,
		);
		
		dibi::query("INSERT INTO [comment] ", $arr);
		
		$l = dibi::fetch("
			SELECT 
				menu_item.url_identifier AS menu_url_identifier,
				article.url_identifier
			FROM 
				[node]
				JOIN [menu_item] USING(id_menu_item)
				JOIN article USING(id_node)
			WHERE 
				node.id_node = %i", $values['id_node'],"
		");
		
        $template = $this->template;
        $template->setFile(dirname(__FILE__).'/CommentEmailNotification.phtml');
        $template->values = $l;
        $uri = NEnvironment::getHttpRequest()->getUri();
        
        $template->url = $uri->scheme.'://'.$uri->host.$this->getPresenter()->link(":Homepage:article", $l['menu_url_identifier'], $l['url_identifier']);
        
        
		
//		$mail = new MyMail();
//        $mail->addTo( 'info@sprievodcaockovanim.sk' );
//        $mail->addBcc('form@q7.sk');
//         
//        $mail->setSubject('Sprievodca ockovanim - Nový komentár.');
//        if(NEnvironment::isProduction())
//        	$mail->send($template);
	}
}