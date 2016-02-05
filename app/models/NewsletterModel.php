<?php 

class NewsletterModelExtension extends Exception{}

class NewsletterModel extends NObject{
	
	//pridanie viacej emailov naraz odelenych ciarkov
	static public function addEmails( $text ){
		$emails = self::getEmailsFromText($text);
		
		if(!empty($emails)){
			foreach($emails as $email){
				self::add( array('email'=>$email) );
			}
		}
		
		
	}
	
	static function getEmailsFromText($text){
		$matches = array();
		preg_match_all('/([\w\d\.\-\_]+)@([\w\d\.\_\-]+)/mi', $text, $matches);
		return $matches[0];
	}
	
	static public function add( $values ){
		if(self::getFluent()->where('email = %s',$values['email'])->fetchSingle())
			return false;
			
		$arr = array(
			'email'=>$values['email'],
			'active'=>1,
			'adddate%sql'=>'NOW()',
			'unsubscribeHash'=>sha1(time().$values['email'])
		);
		
		dibi::query("INSERT INTO [newsletter_emails]", $arr);
		
		return true;
	}

	static public function edit( array $values , $id_newsletter_emails){
		dibi::query("UPDATE [newsletter_emails] SET ",$values,"WHERE id_newsletter_emails = %i",$id_newsletter_emails);
	}

	
	public static function get(){
		return dibi::getFluent()->fetchAll();
	}

	public static function getFluent(){
		return dibi::select("*")->from('newsletter_emails');
	}
	
	public static function getDatasource(){
		return self::getFluent()->toDataSource();
	}
	
	public static function delete($id_newsletter_email){
	 	dibi::query("DELETE FROM [newsletter_emails] WHERE id_newsletter_emails = %i", $id_newsletter_email);
	}

	public static function sendEmail($email, $subject, $text){

		$mail = new MyMail();
		$mail->addTo( $email );

		$mail->setSubject( $subject );
		$mail->setHtmlBody($text);

		$mail->send();		
	}

	public static function sendEmailToAll( $subject, $text){

		$session = NEnvironment::getSession('checked_emails');
		if(!empty($session->emails)){
			$all_emails = $session->emails;
		}else{
			$all_emails = self::getFluent()->fetchAll();
		}
		
		
		foreach( $all_emails as $m){
			$mail = new MyMail();
			$mail->addTo( $m['email'] );

			$mail->setSubject( $subject );
			$mail->setHtmlBody($text);
			
			$mail->send();
		}
	}



	public static function saveMsg($subject, $text){
		dibi::query("INSERT INTO [newsletter_sended_msg] ", array('subject'=>$subject,'text'=>$text, 'date'=>date('Y-m-d H:i:s')));
	}
}