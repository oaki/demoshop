<?

class Authentication extends NObject{
  protected $error_msg;
  private $session, $COOKIE_KEY;
  function __construct($show_login = true){

    if(isset($_GET['logoff'])){
    	NEnvironment::getUser()->logout(  );	     
		header("Location: admin.php");
      exit;
    }
    
   
//    var_dump(NEnvironment::getUser()->isLoggedIn());
//	 var_dump(NEnvironment::getUser()->isAllowed('access_to_cms','edit'));
//    print_r(NEnvironment::getUser());
//    NDebug::dump(NEnvironment::getUser()->getIdentity());
    
    if(!NEnvironment::getUser()->isAllowed('cms','edit')){
    	
    	if($show_login){
			header('Location: /sk/admin/');
      		$this->showLogin();
    	}
    }
    
  }

   
  static function getCountOfUnautorizedAccess(){
  	return dibi::fetchSingle("
  	SELECT 
  		COUNT(*) 
  	FROM 
  		[log_user_global] 
  	WHERE 
  		[REMOTE_ADDR] = %s", $_SERVER['REMOTE_ADDR'],"
  		AND [datetime] > NOW() - 600
  		AND type = 1" );
  	
  }

  function showLogin(){
  	
  	$user = NEnvironment::getUser();
  	
  	if(!isset($this->session['time'])){
  		$this->session['time'] = date('d.m.Y H:i:s');
  	};
  	//pouzit iba jeden CSS
	
	MT::addCss('/require_modules/authentication/css/auth.css');
	MT::addScripts('/jscripts/mootools/mootools1.2b2.js','mootools');
	MT::addScripts('/jscripts/mootools/mootools_DWrequest.js','mootools_adds');
	
	
	$form = new NForm('LoginForm');
	$form->getElementPrototype()->id = 'LoginForm';
	$renderer = $form->getRenderer();
	
	$renderer->wrappers['controls']['container'] = 'div';
	
	$renderer->wrappers['pair']['container'] = 'div';
	$renderer->wrappers['label']['container'] = NULL;
	$renderer->wrappers['control']['container'] = NULL;
	
	
	$form->addGroup();
	$form->addText('login', 'Prihlasovacie meno')
		->addRule(NForm::FILLED, 'Prihlasovacie meno musí byť vyplnené.')
			->addRule(NForm::MAX_LENGTH, 'Maximálny počet znakov je %s', 20);
	$form->addPassword('password', 'Heslo')
		->addRule(NForm::FILLED, 'Prihlasovacie meno musí byť vyplnené.')
		->addRule(NForm::MAX_LENGTH, 'Maximálny počet znakov je %s', 30);
	$form->addSubmit('submit_login', 'Log In');
	
	$form->addProtection('Sedenie vypršalo. Proším obnovte prihlasovací formulár a zadajte údaje znovu.', 1800);
	
	$form['login']->getControlPrototype()->class = 'text';
	$form['login']->getLabelPrototype()->id = 'loginLabel';
	$form['password']->getControlPrototype()->class = 'text';
	$form['password']->getLabelPrototype()->id = 'passwordLabel';
	$form['submit_login']->getControlPrototype()->class = 'btnLogin';
	
	if ($form->isSubmitted()) {		    
	    if ($form->isValid()) {
	        $values = $form->getValues();
	        
	        try{
	        	if(self::getCountOfUnautorizedAccess() > 10){
			  		throw new NAuthenticationException('Prihlásenie bolo zablokované na 10 minút.');			  		
			  	}
			  	Log::addGlobalLog( /* type authentification */ 1);
	        	$user->setExpiration('+ 2 days', FALSE);
//	        	$user->setAuthenticationHandler( new Login() );
	        	
	        	$user->login($values['login'], $values['password']);	
	        	
	        	
	        	$form->setValues(array(), TRUE);
	     
	        	header('Location: '.$_SERVER['REQUEST_URI']);exit;
	        }catch(NAuthenticationException $e){
	        	$form->addError($e->getMessage());        	
	        }        	        
	    }	
	} 
		
	
  	
	MT::addTemplate(dirname(__FILE__).'/login.phtml', 'authentication');
	MT::addVar('authentication','error_msg',$this->error_msg);
	MT::addVar('authentication','loginForm',$form);
	
	echo MT::renderHeader();
	echo MT::renderContentHolder();
	
 	exit;
  }
  
  
  function showUsers(){
	$user = NEnvironment::getUser();
	
	
  	if(! $user->isAllowed('manage_user', 'edit'))
  		throw new LogicException('Nemáte dostatočné oprávnenie na túto sekciu');
  	
    try{
       //uprava uzivatela  
      if(isset($_GET['id_auth_user']) AND $_GET['id_auth_user']!=""){
      	
      	  $form = new NForm();
          
	      $form->addText('name', 'Meno');
	      $form->addText('surname', 'Priezvisko');
	      $form->addText('email', 'Email');
	      $form->addText('login', 'Login')
	      	->addRule(NFORM::FILLED,'Login musí byť vyplnený');
	
	      $form->addPassword('password', 'Heslo');
	      		      	
	 		
	      $role = dibi::query('SELECT id_auth_role, key_name FROM auth_role ORDER BY key_name')->fetchPairs('id_auth_role','key_name');
	      
	      $form->addPassword('password2','Znova heslo')
	      	 ->addConditionOn($form['password'], NForm::FILLED)
	      		->addRule(NForm::FILLED, 'Zadejte heslo pro kontrolu')
				->addRule(NForm::EQUAL, 'Hesla se musi shodovat', $form['password']);
	      
	     
	      $form->addSelect('id_auth_role', 'Uživateľská skupina:', $role);
	      
	      $form->addSubmit('addUser', 'Upravit');
	      
	      $form->onSubmit[] = array($this, 'changeUser');
	      
    	  if (!$form->isSubmitted()) {
	        // 	první zobrazení, nastavíme výchozí hodnoty
        	$form->setDefaults(dibi::fetch("SELECT *, id_auth_role FROM auth_user LEFT JOIN [auth_user_role] USING(id_auth_user) WHERE auth_user.id_auth_user=%i",$_GET['id_auth_user']));
		  }

	      $form->fireEvents();
	      
	      MT::addTemplate(APP_DIR.'/require_modules/authentication/editUser.phtml', 'editUser');
     
	      MT::addVar('editUser', 'form', (string)$form);
	      
	      
      }
    }catch(Exception $e){?>
      <div style="border:2px solid red;padding:5px;">
        <?echo $e->getMessage();?>
      </div><?        
    }  
     
    
    if(!isset($_GET['id_auth_user'])){
      if(isset($_GET['id_delete_user'])){
        $this->deleteUser();
      }

         
      $form = new NForm();
      
      $form->getElementPrototype()->id = 'formAddUser';
      
      $form->addText('name', 'Meno');
      $form->addText('surname', 'Priezvisko');
      $form->addText('email', 'Email');
      $form->addText('login', 'Login')
      	->addRule(NFORM::FILLED,'Login musí byť vyplnený');

      $form->addPassword('password', 'Heslo')
      	->addRule(NForm::FILLED, 'Zadejte heslo');;
 		
      $role = dibi::query('SELECT id_auth_role, key_name FROM [auth_role] ORDER BY key_name')->fetchPairs('id_auth_role','key_name');
      
      $form->addPassword('password2','Znova heslo')
      	->addRule(NForm::FILLED, 'Zadejte heslo pro kontrolu')
		->addRule(NForm::EQUAL, 'Hesla se musi shodovat', $form['password']);;
      
     
      $form->addSelect('id_auth_role', 'Uživateľská skupina:', $role);
      
      $form->addSubmit('addUser', 'Pridať použivateľa');
      
      $form->onSubmit[] = array($this, 'addUser');
      
      $form->fireEvents();
      
      
      
      MT::addTemplate(APP_DIR.'/require_modules/authentication/showUsers.phtml', 'showUsers');

      
      $list = dibi::fetchAll ("
      	SELECT 
      		*
      	FROM 
      		auth_user
      	ORDER BY login" );
      
      MT::addVar('showUsers', 'list', $list);
      
      MT::addVar('showUsers', 'form', $form);
      
      
    }
  }
  
  public function addUser($form){
  	
  		if($form->isValid()){
  			$values = $form->getValues();
  			
		  	
		    if(!isset($values['user_role']))$values['user_role']=0;
		    
		    if(dibi::fetchSingle("SELECT 1 FROM auth_user WHERE login=%s",$values['login']) == 1){
		    	throw new Exception("Dané prihlasovacie meno už existuje. Zadajte iné prosím.");
		    }

		   $arr = array(		    	
			    'login'=>$values['login'],
			    'password'=>UserModel::getHash($values['password']),
			    'name'=>$values['name'],
			    'surname'=>$values['surname'],
		    );
		    
		    
		    dibi::query("INSERT INTO auth_user",$arr);
		    $last_id = dibi::insertId();
		    dibi::query("INSERT INTO [auth_user_role]", array('id_auth_user'=>$last_id,'id_auth_role'=>$values['id_auth_role']));
		    Log::addLog($this,'Pridanie noveho uzivatela');
  		}else{
  			throw new Exception('Nespavne vyplneny formular');
  		}
  }
  
  public function changeUser($form){

  	if($form->isValid()){
  			$values = $form->getValues();
  		    
		    if(dibi::fetchSingle("SELECT 1 FROM auth_user WHERE login=%s",$values['login'],"AND login!=%s",$this->session['form_login']) == 1){
		    	throw new Exception("Dané prihlasovacie meno už existuje. Zadajte iné prosím.");
		    }

		   $arr = array(		    	
			    'login'=>$values['login'],			    
			    'name'=>$values['name'],
			    'surname'=>$values['surname'],
		   		'email'=>$values['email'],
		    );
		    
		    if($values['password']!=''){
		    	$arr['password'] = UserModel::getHash($values['password']);
		    }

		    dibi::query("UPDATE auth_user SET",$arr,"WHERE id_auth_user=%i", $_GET['id_auth_user']);
		    dibi::query("UPDATE auth_user_role SET ",array( 'id_auth_role'=>$values['id_auth_role'] ),"WHERE id_auth_user = %i", $_GET['id_auth_user']);
		    Log::addLog($this,'Pridanie noveho uzivatela');
  		}else{
  			throw new Exception('Nespavne vyplneny formular');
  		}
   
    Log::addLog(
    $this,"Zmena uzivatelskych udajov.",
    "Menil:".$this->session["login_form"]);
    return true;
  }
  
  private function deleteUser(){
    dibi::query("DELETE FROM auth_user WHERE id_auth_user=%i",$_GET['id_delete_user']);
   	Log::addLog($this,"Zmazanie uzovatela",$_GET['id_delete_user']);
  }

}