<?
class MyMail extends NMail
{
	private $result, $template, $htmlText;
	
	public function setTemplate(NTemplate  &$template){
		$this->template = $template;
	}

	public function setHtmlText($htmlText){
		$this->htmlText = $htmlText;
	}

    public function send() {
		
 		$conf = NEnvironment::getContext()->parameters['common.mailer'];
		
 		$this->setFrom($conf['from']);
 		
        //nastavení nSMTPMaileru (z config.ini)
        $mailer = new SmtpMailer(
        	$conf['host'], $conf['port'], $conf['transport'],
        	$conf['username'], $conf['password'],
        	NULL, NULL, NULL
        );

        $this->setMailer($mailer); //důležité!!!
        
//        $undelivered = array();
//        foreach ($recipients as $email) {
//            try {
//        //tady můžeme prohnat zadané adresy nějakým úžasným regexem
//                if (!preg_match('/^\s*$/', $email)) {
//            //můžeme použít i addCC a addBcc
//                    $mail->addTo($email);
//        } else {
//            $undelivered[] = $email;
//        }
//            } catch (InvalidArgumentException $e) {
//                $undelivered[] = $email;
//            }
//        }

		
		if( isset($this->template) ){
			$this->setHtmlBody( (string)$this->template );
		}
        

        
        try {
            parent::send();          
        } catch (InvalidStateException $e) {
            	$this->result = FALSE;
        }
		

    }

    public function getResult()
    {
        return $this->result;
    }
}