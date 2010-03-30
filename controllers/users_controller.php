<?php
class UsersController extends BrownieAppController {

	var $name = 'Users';

	function beforeFilter() {
		//$this->ImageModel = ClassRegistry::init('BrwImage');
		parent::beforeFilter();
	}

	function login()
	{
		// if the form was submitted
		if(!empty($this->data)) {
			// find the user in the database
			$dbuser = $this->BrwUser->findByUsername($this->data['BrwUser']['username']);
			// if found and passwords match
			if(!empty($dbuser) && ($dbuser['BrwUser']['password'] == $this->data['BrwUser']['password'])) {
				// write the username to a session
				$this->Session->write('BrwUser', $dbuser['BrwUser']);
				// save the login time
				$saveuser['BrwUser']=array(
					'id'			=> $dbuser['BrwUser']['id'],
					'last_login'	=> date("Y-m-d H:i:s"),
					'modified'		=> $dbuser['BrwUser']['modified']
				);
				//pr($dbuser);
				$this->BrwUser->save($saveuser);
				// redirect the user
				$this->Session->setFlash(__d('brownie', 'Login successful', true));
			} else {
				$this->Session->setFlash(__d('brownie', 'Username and password do not match.', true));
			}
		//if the user is already logged in
		}

		if($this->Session->read('BrwUser')) {
			$this->redirect(array('plugin'=>'brownie', 'controller' => 'brownie', 'action' => 'index'));
		}
	}

	function logout()
	{
		// delete the user session
		$this->Session->delete('BrwUser');
		$this->redirect(array('plugin'=>'brownie', 'controller' => 'users', 'action' => 'login'));
	}


}
?>