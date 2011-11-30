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
					'message' =>  __d('brownie', 'Email already registered'),
				),
				array(
					'rule' => 'email',
					'message' => __d('brownie', 'Email not valid'),
				),
				array(
					'rule' => 'notEmpty',
					'on' => 'create',
					'required' => true,
					'message' =>  __d('brownie', 'Email cannot be empty'),
				),
			),
			'password' => array(
				array(
					'rule' => 'notEmpty',
					'on' => 'create',
					'message' =>  __d('brownie', 'Password cannot be empty'),
				),
			),
			'repeat_password' => array(
				array(
					'rule' => 'notEmpty',
					'on' => 'create',
					'message' => __d('brownie', 'Password cannot be empty'),
				),
				array(
					'rule' => array('checkPasswordMatch'),
					'message' => __d('brownie', 'Passwords do not match'),
				),
			)
		);
		return Set::merge($defaultValidate, (array)$Model->validate);
	}


	function beforeSave($Model) {
		if (!empty($Model->data[$Model->alias]['password'])) {
			$Model->data[$Model->alias]['password'] = AuthComponent::password($Model->data[$Model->alias]['password']);
		}
		if (
			!empty($Model->data[$Model->alias]['id'])
			and isset($Model->data[$Model->alias]['password'])
			and $Model->data[$Model->alias]['password'] == ''
		) {
			unset($Model->data[$Model->alias]['password']);
			if (isset($Model->data[$Model->alias]['repeat_password'])) {
				unset($Model->data[$Model->alias]['repeat_password']);
			}
		}
		return $Model->data;
	}


	function checkPasswordMatch($Model, $data) {
		$password = $Model->data[$Model->name]['password'];
		$repeat_password = $Model->data[$Model->name]['repeat_password'];
		return ($password == $repeat_password);
	}


	function brwBeforeEdit($Model, $data) {
		$data[$Model->alias]['password'] = $data[$Model->alias]['repeat_password'] = '';
		return $data;
	}


	function updateLastLogin($Model, $id) {
		if (!$Model->schema('last_login')) {
			return null;
		}
		$user = $Model->findById($id);
		return $Model->save(array(
			'id' => $id, 'last_login' => date('Y-m-d H:i:s'),
			'modified' => $user[$Model->name]['modified'],
		));
	}


	function checkAndCreate($Model, $email, $password) {
		if (!$Model->find('first')) {
			if ($Model->save(array('id' => null, 'email' => $email, 'password' => $password))) {
				return array_merge(array_shift($Model->findById($Model->id)), array('model' => 'BrwUser'));
			}
		}
		return false;
	}

}