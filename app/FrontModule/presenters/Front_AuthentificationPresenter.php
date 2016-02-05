<?php

/**
 * Description of Front_FacebookPresenter.php
 *
 * @author oaki
 */
class Front_AuthentificationPresenter extends Front_BasePresenter {

	
	
	/** @persistent */
	public $backurl;
	
	public $default_back_url = ':Front:Homepage:default';
	
	
	
	
	
	/*
	 * FACEBOOK Authentification
	 */
	public function actionFacebookRedirectToLogin(){
		
		$facebook = new Facebook( $this->context->parameters['FACEBOOK'] );
		
		header('Location: '.$facebook->getLoginUrl( array('scope'=>'email','redirect_uri' => $this->getPresenter()->link('//:Front:Authentification:facebookLogIn'))));
		
		$this->terminate();
	}
	
	public function actionFacebookLogIn(){
		
		$facebook = new Facebook( $this->context->parameters['FACEBOOK'] );
		$fbuser = $facebook->getUser();
		
		if ($fbuser) {
			try {
				// Proceed knowing you have a logged in user who's authenticated.
				$me = $facebook->api('/me');
//				print_r($me);exit;
				$user = $this->context->user;
				$user->setAuthenticator(new FacebookAuthenticator);
				$user->login( $me );
				if($this->backurl!=''){
					$this->redirectUri($this->backurl);
				}
				$this->redirect( $this->default_back_url );
				
			} catch (FacebookApiException $e) {
				$this->flashMessage($e->getMessage());
				$user = null;
				$this->redirect( $this->default_back_url );
			}
		}
		
		throw new NAuthenticationException('Login failed.');
	}
	
	
	
	
	/*
	 * GOOGLE Authentification
	 */
	private function getGoogleClient( $config ){
		
		require_once LIBS_DIR.'/google-api-php-client/src/apiClient.php';
		require_once LIBS_DIR.'/google-api-php-client/src/contrib/apiOauth2Service.php';
		
		$client = new apiClient();
		$client->setApplicationName( $config['application_name'] );
		$client->setClientId( $config['client_id'] );
		$client->setClientSecret( $config['client_secret'] );
		$client->setRedirectUri( $config['redirect_url'] );
	
		
		$client->setScopes(array('https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email'));
		return $client;
	}
	
	public function actionGoogleRedirectToLogin(){
		
		$client = $this->getGoogleClient( $this->context->parameters['GOOGLE'] );
		
		$authUrl = $client->createAuthUrl();
							
		header('Location: '.$authUrl);
		
		$this->terminate();
	}
	
	public function actionGoogleLogIn(){
		
		$client = $this->getGoogleClient( $this->context->parameters['GOOGLE'] );
		
		$session = $this->context->session->getSection('google');
		
		$oauth2 = new apiOauth2Service($client);
		
		if (isset($_GET['code'])) {
			$client->authenticate();
			
			$session['access_token'] = $client->getAccessToken();
//			header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
		}
		
		if (isset($session['access_token'])) {
			$client->setAccessToken($session['access_token']);
		}
		
		
		if ($client->getAccessToken()) {
			
			$google_user = $oauth2->userinfo->get();
			
			$user = $this->context->user;
			
			$user->setAuthenticator(new GoogleAccountAuthenticator);
//			dde($google_user);
			$user->login( $google_user );

			$session['access_token'] = $client->getAccessToken();
			
			if($this->backurl!=''){
				$this->redirectUri($this->backurl);
			}
			
			$this->redirect( $this->default_back_url  );

			
		}
		
		if(isset($_GET['error'])){
			if($_GET['error'] == 'access_denied'){
				$_GET['error'] = 'Prístup bol zamietnutý';
			}
			$this->flashMessage($_GET['error']);
			$this->redirect( $this->default_back_url );
		}
		
		throw new NAuthenticationException('Login failed.');
	}
	
}