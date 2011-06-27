<?php

class BrwUserBehavior extends ModelBehavior {

	function setup($Model) {
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
			'password' => array(
				array(
					'rule' => 'notEmpty',
					'on' => 'create',
					'message' =>  __d('brownie', 'Password cannot be empty', true),
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
		if ($Model->alias != 'BrwUser') {
			$pass = $Model->data[$Model->alias]['password'];
			$Model->data[$Model->alias]['password'] = Security::hash($pass, null, true);
		}
		if (!empty($Model->data[$Model->alias]['id']) and isset($Model->data[$Model->alias]['password'])) {
			if (Security::hash('', null, true) == $Model->data[$Model->alias]['password']) {
				unset($Model->data[$Model->alias]['password']);
				if (isset($Model->data[$Model->alias]['repeat_password'])) {
					unset($Model->data[$Model->alias]['repeat_password']);
				}
			}
		}
		return $Model->data;
	}


	function checkPasswordMatch($Model, $data) {
		$password = $Model->data[$Model->name]['password'];
		$repeat_password = $Model->data[$Model->name]['repeat_password'];
		if ($Model->alias == 'BrwUser') {
			$repeat_password = Security::hash($repeat_password, null, true);
		}
		return ($password == $repeat_password);
	}

	function brwBeforeEdit($Model, $data) {
		$data[$Model->alias]['password'] = $data[$Model->alias]['repeat_password'] = '';
		return $data;
	}

}