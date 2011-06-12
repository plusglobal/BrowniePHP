<?php

class BrwUserBehavior extends ModelBehavior {

	function setup($Model, $config = array()) {
		$Model->displayField = 'email';
		$Model->validate = $this->_validate($Model);
	}


	function _validate($Model) {
		$defaultValidate = array(
			'email' => array(
				array(
					'rule' => 'isUnique',
					'message' =>  __d('brownie', 'Email already registered', true),
				),
				array(
					'rule' => 'email',
					'message' => __d('brownie', 'Email not valid', true),
				),
				array(
					'rule' => 'notEmpty',
					'on' => 'create',
					'required' => true,
					'message' =>  __d('brownie', 'Email cannot be empty', true),
				),
			),
			'repeat_password' => array(
				array(
					'rule' => 'notEmpty',
					'on' => 'create',
					'message' => __d('brownie', 'Password cannot be empty', true),
				),
				array(
					'rule' => array('checkPasswordMatch'),
					'message' => __d('brownie', 'Passwords do not match', true),
				),
			)
		);
		return Set::merge($defaultValidate, (array)$Model->validate);
	}


	/*function beforeFind($Model, $query) {
		$user = Configure::read('Auth.BrwUser');
		if ($user and !$user['root']) {
			if (!empty($query['conditions']['BrwUser.id']) and $query['conditions']['BrwUser.id'] != $user['id']) {
				$query['conditions']['BrwUser.id'] = 'noexiste';
			} else {
				$query['conditions']['BrwUser.id'] = $user['id'];
			}
		}
		return $query;
	}*/


	function beforeSave($Model) {
		if ($Model->alias == 'BrwUser') {
			if (!empty($Model->data['BrwUser']['id']) and isset($Model->data['BrwUser']['password'])) {
				if (Security::hash('', null, true) == $Model->data['BrwUser']['password']) {
					unset($Model->data['BrwUser']['password']);
					if (isset($Model->data['BrwUser']['repeat_password'])) {
						unset($Model->data['BrwUser']['repeat_password']);
					}
				}
			}
		} else {
			$pass = $Model->data[$Model->alias]['password'];
			$Model->data[$Model->alias]['password'] = Security::hash($pass, null, true);
		}
		return $Model->data;
	}


	function checkPasswordMatch($Model, $data) {
		$password = $Model->data[$Model->name]['password'];
		$repeat_password = $Model->data[$Model->name]['repeat_password'];
		//pr(Configure::read('brwSettings.authModel'));
		if ($Model->alias == 'BrwUser') {
			$repeat_password = Security::hash($repeat_password, null, true);
		}
		return ($password == $repeat_password);
	}

	function brwBeforeEdit($Model, $data) {
		$data['BrwUser']['password'] = $data['BrwUser']['repeat_password'] = '';
		return $data;
	}

}