<?php

class GoogleAccountAuthenticator extends NObject implements IAuthenticator
{

	
	public function __construct()
	{
		
	}

	/**
	 * @param array $fbUser
	 * @return \Nette\Security\Identity
	 * 
array(1) [
   0 => array(8) {
      "id" => "109382857318298460284" (21)
      "email" => "servis@vizion.sk" (16)
      "verified_email" => TRUE
      "name" => "Vizion s.r.o." (13)
      "given_name" => "Vizion" (6)
      "family_name" => "s.r.o." (6)
      "link" => "https://plus.google.com/109382857318298460284" (45)
      "locale" => "sk" (2)
   }
]
	 */
	
	public function authenticate(array $credentials)
	{
		
		if(!isset($credentials[0]['email']))
			throw new NAuthenticationException('Access is Denied');
		
		
		
		$google_user = $credentials[0];
		
		//ak nieje verifikovany email
		if($google_user['verified_email']!==true)
			throw new NAuthenticationException('Váš email nie je verifikovaný.');
			
		$sql = dibi::query('
			SELECT *,
				'. TABLE_USERS .'.id AS id
			FROM
				[' . TABLE_USERS . ']
				LEFT JOIN ['. TABLE_USERS_INFO.'] ON ('.TABLE_USERS.'.id = '.TABLE_USERS_INFO.'.user_id)
			WHERE login=%s;', $google_user['email']);
		
        $sql->setType('permission', Dibi::BOOL);
        $row = $sql->fetch();

		if ($row) {
			$this->updateMissingData($row, $google_user);
		} else {
			$row = $this->register($google_user);
		}
		
        // get roles
        $sql = dibi::query('SELECT r.key_name
                                FROM [' . TABLE_ROLES . '] AS r
                                RIGHT JOIN [' . TABLE_USERS_ROLES . '] AS us ON r.id=us.role_id
                                WHERE us.user_id=%i;', $row['id']);
        $roles = $sql->fetchPairs();

        unset($row->password);
		
//		$user = NEnvironment::getUser();
//		$user->getIdentity()->setIdentity( new NIdentity($fbUser['email'], $roles, $row));
//		$user->setAuthenticated(TRUE);
//		$user->onLoggedIn($this);
//		
//		NEnvironment::getUser()->login(new NIdentity($fbUser['email'], $roles, $row));exit;
        return new NIdentity($row['id'], $roles, $row);
	}

	public function register(array $me)
	{
		
		$last_id = UserModel::insert(array(
			'login' => $me['email'],
			'google_id' => $me['id'],
			'name' => $me['given_name'],
			'surname' => $me['family_name'],
			'activate'=>1,
			'password'=>  Tools::random(12),
			'newsletter'=>0,
			'iso'=>'SVK',
		));
		
		return UserModel::get( $last_id );
	}

	public function updateMissingData($user, array $google_user)
	{
		
		$updateData = array();

		if (empty($user['name'])) {
			$updateData['name'] = $google_user['name'];
		}
		
		if (empty($user['surname']) OR $user['surname']=='') {
			$updateData['surname'] = $google_user['family_name'];
		}

		if (empty($user['google_id'])) {
			$updateData['google_id'] = $google_user['id'];
		}

		if (!empty($updateData)) {
			UserModel::update($user['id'], $updateData);
		}
	}
	

}
