<?php

class MsgControl extends NControl {

	public function render($title,$text='',$type = '' /* type = error */)
	{
		$this->template->setFile(dirname(__FILE__) . '/msg.phtml');
		
		$this->template->title = $title;
		$this->template->text = $text;
		$this->template->type = $type;
		$this->template->render();

	}
}