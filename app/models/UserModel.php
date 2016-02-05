<?php
class UserModel extends NObject implements IAuthenticator{
	const DB_KEY = 'kliew09ouijlsdgfisdugfhlndzspg9spoildfg';
    /**
     * Performs an authentication
     *
     * @param  array
     * @return void
     * @throws AuthenticationException
     */
    public function authenticate(array $credentials)
    {
        $username = $credentials[self::USERNAME];
        $password = $credentials[self::PASSWORD];

        $sql = dibi::query('
			SELECT *,
				'. TABLE_USERS .'.id AS id
			FROM
				[' . TABLE_USERS . ']
				LEFT JOIN ['. TABLE_USERS_INFO.'] ON ('.TABLE_USERS.'.id = '.TABLE_USERS_INFO.'.user_id)
			WHERE login=%s;', $username);
		
        $sql->setType('permission', Dibi::BOOL);
        $row = $sql->fetch();

        /*if (count($sql)==0) {
            throw new AuthenticationException('Unknown user', self::IDENTITY_NOT_FOUND);
        }*/

        /*if ($row->password !== md5($password)) {
            throw new AuthenticationException('Password not match', self::INVALID_CREDENTIAL);
        }*/

		/* ak to bolo stratene heslo */
		if(count($sql)==1 AND $row->new_password == self::getHash($password)){
			UserModel::update($row->id, array('password'=>self::getHash($password), 'new_password'=>NULL));
			$row->password = $row->new_password;
		}

        if (count($sql)==0 OR $row->password!==self::getHash($password)) {
            throw new NAuthenticationException('Nespráne heslo alebo meno.', self::INVALID_CREDENTIAL);
        }

        // get roles
        $sql = dibi::query('SELECT r.key_name
                                FROM [' . TABLE_ROLES . '] AS r
                                RIGHT JOIN [' . TABLE_USERS_ROLES . '] AS us ON r.id=us.role_id
                                WHERE us.user_id=%i;', $row->id);
        $roles = $sql->fetchPairs();

        unset($row->password);
        return new NIdentity($username, $roles, $row);
    }

    
    public static function getHash($string){
    	return sha1(self::DB_KEY.$string);
    }
    
	static function get($id_user){
		return dibi::query('
			SELECT 
				*,
				user_id AS id
			FROM 
				[' . TABLE_USERS . ']
				LEFT JOIN ['. TABLE_USERS_INFO.'] ON ('.TABLE_USERS.'.id = '.TABLE_USERS_INFO.'.user_id)
			WHERE 
				'.TABLE_USERS.'.id=%s;', $id_user
		)->fetch();
	}
	
	static function getDatasource(){
		return dibi::dataSource("SELECT * FROM [".TABLE_USERS."]");
	}

	static function getFluent( $onlyActivated = true ){
		return 
		dibi::select(
				TABLE_USERS_INFO.'.*,'
				.TABLE_USERS.'.login,'
				.TABLE_USERS.'.surname,'
				.TABLE_USERS.'.name,'
				.TABLE_USERS.'.lastvisit,'
				.TABLE_USERS.'.email,'
				.TABLE_USERS.'.activate,'
				.TABLE_USERS.'.id AS id_gui_user'
			)
			->from(TABLE_USERS)
			->leftJoin(TABLE_USERS_INFO)
				->on(' ('.TABLE_USERS.'.id = '.TABLE_USERS_INFO.'.user_id) ')
			->leftJoin(TABLE_USERS_COUNTRY)
				->using('(iso)')			
			->where("1=1 %if",$onlyActivated,"AND activate = 1 %end");
			
	}
	
//	static function getRoles($id_auth_user){
//		return dibi::query('
//			SELECT r.key_name
//			FROM
//            	[auth_role] AS r
//                RIGHT JOIN [auth_user_role] AS us ON r.id_auth_role=us.id_auth_role
//            WHERE
//                us.id_auth_user=%i', $id_auth_user);
//	}
	
	static function update($id_user, $values){
		if(isset($values['password'])){
			$values['password'] = self::getHash($values['password']);
		}
		
		dibi::query("
			UPDATE
				".TABLE_USERS."
			LEFT JOIN [".TABLE_USERS_INFO."] ON (".TABLE_USERS.".id = ".TABLE_USERS_INFO.".user_id )
			SET",$values,"
			WHERE ".TABLE_USERS.".id = %i", $id_user);
		
		
	}
	
	static function insert($values){
		
		$user_value['name'] = @$values['name'];
		$user_value['surname'] = @$values['surname'];
	//		$user_value['email'] = $values['email'];
		$user_value['login'] = $values['login'];
		$user_value['activate'] = $values['activate'];
		$user_value['password'] = self::getHash($values['password']);
		$user_value['fbuid'] = @$values['fbuid'];
		$user_value['google_id'] = @$values['google_id'];
		$user_value['newsletter'] = @$values['newsletter'];
		$user_value['discount'] = @$values['discount'];
		
		unset($values['name'],$values['surname'],$values['login'],$values['password'],
				$values['activate'],$values['fbuid'],$values['newsletter'],
				$values['discount']
		);
		dibi::begin();
		dibi::insert( TABLE_USERS , $user_value)->execute();
		
		$values['user_id'] = dibi::insertId();
		 
		dibi::insert( TABLE_USERS_INFO, $values)->execute();
		dibi::commit();
		return $values['user_id'];
	}
	
	
	static function getAllCountry(){
		return dibi::query("SELECT * FROM [".TABLE_USERS_COUNTRY."]")->fetchPairs('iso','country_name');
	}

	static function baseForm( $check_email = true){
		$form = new MyForm();
		$renderer = $form->getRenderer();

		$renderer->wrappers['group']['label'] = 'h3';


		$form->addGroup( _('Informácie o zákazníkovi') )->setOption('container', 'fieldset id=user-info');
		$form->addText('login', _('Používateľské meno / Email'));
		if($check_email)
			$form['login']->addRule(NForm::FILLED, _('Používateľské meno musí byť vyplnené'))
				->addRule(NForm::EMAIL, _('Používateľské meno musí byť email.'));

//				$form->addText('email', _('Email'))
//					->addRule(NForm::FILLED, _('Email musí byť vyplnený'))
//					->addRule(NForm::EMAIL, _('Email nie je v správnom tvare'));

		$form->addPassword('password', _('Heslo'));

		 $form->addPassword('passwordCheck',_('Znova heslo'))
			 ->addConditionOn($form['password'], NForm::FILLED)
				->addRule(NForm::FILLED, _('Zadejte heslo pro kontrolu'))
				->addRule(NForm::EQUAL, _('Hesla sa musia zhodovať'), $form['password']);

		/*
		 * Info o uzivatelovi
		 */
		$form->addGroup('Účtovné informácie');
		$form->addRadioList('type','', array(0=>_('Súkromná osoba'), 1=>_('Podnikateľ - firma') ) )
			->addRule(NForm::FILLED, _('Uveďte či ste súkromná osoba alebo firma'))
			->setDefaultValue(0);


		//$form->addSelect('title', _('Oslovenie'), array( 0=>_('Žiadne'), 1=>_('Pán'),2=>_('Pani'),3=>_('Slečna') ));
		$form->addText('name', _('Meno'))
			->addRule(NForm::FILLED, _('Meno musí byť vyplnené'));

		$form->addText('surname', _('Priezvisko'))
		->addRule(NForm::FILLED, _('Priezvisko musí byť vyplnené'));

		$form->addText('address', _('Adresa'))
		->addRule(NForm::FILLED, _('Adresa musí byť vyplnená'));

		$form->addText('city', _('Mesto'))
		->addRule(NForm::FILLED, _('Mesto musí byť vyplnené'));

		$form->addText('zip', _('PSČ'))
		->addRule(NForm::FILLED, _('Priezvisko musí byť vyplnené'));

		$form->addSelect('iso', _('Štát'), UserModel::getAllCountry())
			->addRule(NForm::FILLED, _('Priezvisko musí byť vyplnené'));

		$form->addText('phone', _('Telefón'))
			->addRule(NForm::FILLED, _('Telefón musí byť vyplnený'));


		//$form->addText('fax', _('Fax'));


		$form->addGroup( _('Firemné informácie') )->setOption('container', 'fieldset id=company-form-container');


		$form->addText('company_name', _('Názov spoločnosti '))
			 ->addConditionOn($form['type'],  NFORM::EQUAL, 1)
				->addRule(NForm::FILLED, _('Názov spoločnosti musí byť vyplnený'));

		$form->addText('ico', _('IČO'))
			 ->addConditionOn($form['type'],  NFORM::EQUAL, 1)
				->addRule(NForm::FILLED, _('IČO spoločnosti musí byť vyplnené'))
				->addRule(NForm::MAX_LENGTH, ('Maximálna dĺžka je 12'), 12);

		$form->addRadioList('paying_vat', _('Platca DPH'), array(0=>'platca', 1=>'neplatca') )
			->setDefaultValue(0)
			 ->addConditionOn($form['type'],  NFORM::EQUAL, 1)
				->addRule(NForm::FILLED, _('IČO spoločnosti musí byť vyplnené'))
			 ;

		$form->addText('dic', _('DIČ'))
			 ->addConditionOn($form['type'],  NFORM::EQUAL, 1)
				->addRule(NForm::FILLED, _('DIČ spoločnosti musí byť vyplnené'));



		$form->addGroup('');

		$form->addRadioList('use_delivery_address',_('Dodacia adresa'), array(0=>_('Prednastavená (rovnaká ako fakturačná adresa)'), 1=>_('Iná') ) )
			->setDefaultValue(0);

		$form->addGroup(_('Dodacia adresa'))->setOption('container', 'fieldset id=delivery-address-container');

		//$form->addSelect('title', _('Oslovenie'), array( 0=>_('Žiadne'), 1=>_('Pán'),2=>_('Pani'),3=>_('Slečna') ));
		$form->addText('delivery_name', _('Meno'))
			->addConditionOn($form['use_delivery_address'],  NFORM::EQUAL, 1)
				->addRule(NForm::FILLED, _('Meno musí byť vyplnené'));

		$form->addText('delivery_surname', _('Priezvisko'))
			->addConditionOn($form['use_delivery_address'],  NFORM::EQUAL, 1)
				->addRule(NForm::FILLED, _('Priezvisko musí byť vyplnené'));

		$form->addText('delivery_address', _('Adresa'))
			->addConditionOn($form['use_delivery_address'],  NFORM::EQUAL, 1)
				->addRule(NForm::FILLED, _('Adresa musí byť vyplnená'));

		$form->addText('delivery_city', _('Mesto'))
			->addConditionOn($form['use_delivery_address'],  NFORM::EQUAL, 1)
				->addRule(NForm::FILLED, _('Mesto musí byť vyplnené'));

		$form->addText('delivery_zip', _('PSČ'))
			->addConditionOn($form['use_delivery_address'],  NFORM::EQUAL, 1)
				->addRule(NForm::FILLED, _('Priezvisko musí byť vyplnené'));

		$form->addSelect('delivery_iso', _('Štát'), UserModel::getAllCountry())
			->addConditionOn($form['use_delivery_address'],  NFORM::EQUAL, 1)
				->addRule(NForm::FILLED, _('Priezvisko musí byť vyplnené'));

		$form->addText('delivery_phone', _('Telefón'));
		return $form;
	}


	static function delete($id){
		dibi::query("DELETE FROM [".TABLE_USERS."] WHERE id = %i",$id);
	}
}
