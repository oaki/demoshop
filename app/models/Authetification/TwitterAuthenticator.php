<?php

class TwitterAuthenticator extends NObject implements IAuthenticator
{

	
	public function __construct()
	{
		
	}

	/**
	 * @param array $fbUser
	 * @return \Nette\Security\Identity
	 */
	
	public function authenticate(array $credentials)
	{
		dump($credentials);exit;
		$fbUser = $credentials[0];
		
		if(!isset($fbUser['email']))
			throw new NAuthenticationException('Access is Denied');
//		dump($fbUser);exit;
		/*
		 *   [id] => 1008855346
    [name] => Bincura Bincurata
    [first_name] => Bincura
    [last_name] => Bincurata
    [link] => http://www.facebook.com/bincik
    [username] => bincik
		 */
		$sql = dibi::query('
			SELECT *,
				'. TABLE_USERS .'.id AS id
			FROM
				[' . TABLE_USERS . ']
				LEFT JOIN ['. TABLE_USERS_INFO.'] ON ('.TABLE_USERS.'.id = '.TABLE_USERS_INFO.'.user_id)
			WHERE login=%s;', $fbUser['email']);
		
        $sql->setType('permission', Dibi::BOOL);
        $row = $sql->fetch();

		if ($row) {
			$this->updateMissingData($row, $fbUser);
		} else {
			$row = $this->register($fbUser);
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
        return new NIdentity($fbUser['email'], $roles, $row);
	}

	public function register(array $me)
	{
		exit;
		UserModel::insert(array(
			'login' => $me['email'],
			'fbuid' => $me['id'],
			'name' => $me['first_name'],
			'surname' => $me['last_name'],
			'activate'=>1,
			'password'=>  Tools::random(12),
			'newsletter'=>0,
			'iso'=>'SVK',
		));
		
		return UserModel::get( dibi::insertId() );
	}

	public function updateMissingData($user, array $me)
	{
		exit;
		$updateData = array();

		if (empty($user['name'])) {
			$updateData['name'] = $me['first_name'];
		}

		if (empty($user['fbuid'])) {
			$updateData['fbuid'] = $me['id'];
		}

		if (!empty($updateData)) {
			UserModel::update($user['id'], $updateData);
		}
	}
	

}
