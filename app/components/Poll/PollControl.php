<?php

class PollControl extends NControl{

	public static $expirate = 1;

	/** @persistent */
	public $id_poll;

	function render( $id_poll = NULL ){

		if( $id_poll == NULL )
			$id_poll = $this->id_poll;
		$template = $this->template;

		$template->setFile(dirname(__FILE__).'/poll.phtml');
		
		$template->l = dibi::fetch("SELECT * FROM poll WHERE id_poll=%i",$id_poll," AND from_date <= CURRENT_DATE() AND to_date >= CURRENT_DATE()");
		
		$template->answer = dibi::fetchAll("SELECT *, (SELECT COUNT(id_poll_ip_vol) FROM poll_ip_vol WHERE id_poll_answer = poll_answer.id_poll_answer) AS c FROM poll_answer WHERE id_poll=%i",$id_poll,"ORDER BY sequence");

		$template->answer_count = 0;
		//spocita iba tie co uz maju vysledok
		foreach($template->answer as $a){
			$template->answer_count+=$a['c'];
		}
		$template->render();
	}

	function handleVote($id_poll_answer , $id_poll){
//		$this->invalidateControl('pollanswer');
		
		if( !self::check_vote($id_poll) ){

			$c = dibi::fetchSingle("SELECT
				COUNT(id_poll_answer)
				FROM poll_answer
				WHERE id_poll=%i",$id_poll," AND id_poll_answer=%i",$id_poll_answer);
			
			if($c==1){
			  dibi::query("
				  INSERT INTO poll_ip_vol ",
					  array(
						  'id_poll_answer'=>$id_poll_answer,
						  'id_poll'=>$id_poll,
						  'ip'=>$_SERVER['REMOTE_ADDR'],
						  'date'=>date('Y-m-d H:i:s')
					)
				);
			  $this->getPresenter()->flashMessage('Váš hlas bol zaznamenaný.');
			}else{
				$this->getPresenter()->flashMessage('Nastala chyba. Skuste hlasovať znovu.');
			}

		}else{
			$this->getPresenter()->flashMessage('Už ste hlasovali.');
		}

		$this->id_poll = $id_poll;
		$this->invalidateControl('pollanswer');
		//$this->getPresenter()->redirect('this');
	}

	private function check_vote($id_poll){
		return (bool)dibi::fetchSingle("SELECT COUNT(id_poll_ip_vol) FROM poll_ip_vol WHERE  ip=%s",$_SERVER['REMOTE_ADDR']," AND id_poll=%i",$id_poll," AND date>NOW()-".self::$expirate);
	}
}