<?php

class BrwUserBehavior extends ModelBehavior {

	function setup($Model, $config = array()) {
		$Model->brownieCmsConfig = $this-> _brwConfig($Model);
		$Model->validate = $this->_validate($Model);
		/*
		$Model->bindModel(array('belongsTo' => array('BrwGroup')));
		if ($multiSitesModel = Configure::read('multiSitesModel')) {
			$Model->bindModel(array('hasAndBelongsToMany' => array($multiSitesModel)));
		}*/
	}

	function _brwConfig($Model) {
		$defaultBrwConfig = array(
			'fields' => array(
				'no_edit' => array('last_login'),
				'no_add' => array('last_login'),
				'no_view' => array('password'),
				'virtual' => array('repeat_password' => array('after' => 'password')),
				'hide' => array('brw_group_id', 'last_login'),
			),
			'names' => array(
				'section' => 'Usuarios',
				'plural' => 'Usuarios',
				'singular' => 'Usuario',
			),
			'paginate' => array(
				'fields' => array('id', 'email', 'root'),
			),
			'legends' => array(
				'password' => 'Leave blank for no change',
			),
		);
		if (!Configure::read('multiSitesModel')) {
			$defaultBrwConfig['fields']['hide'][] = 'root';
		}
		if(empty($Model->brownieCmsConfig)) {
			$Model->brownieCmsConfig = array();
		}
		return Set::merge($defaultBrwConfig, $Model->brownieCmsConfig);
	}

	function _validate($Model) {
		$defaultValidate = array(
			'email' => array(
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
				array(
					'rule' => 'isUnique',
					'message' =>  __d('brownie', 'Email already registered', true),
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
		return Set::merge($defaultValidate, $Model->validate);
	}


	function beforeFind($Model, $query) {
		$user = Configure::read('Auth.BrwUser');
		if ($user and !$user['root']) {
			if (!empty($query['conditions']['BrwUser.id']) and $query['conditions']['BrwUser.id'] != $user['id']) {
				$query['conditions']['BrwUser.id'] = 'noexiste';
			} else {
				$query['conditions']['BrwUser.id'] = $user['id'];
			}
		}
		return $query;
	}


	function beforeSave($Model) {
		if (!empty($Model->data['BrwUser']['id']) and !empty($Model->data['BrwUser']['password'])) {
			if (Security::hash('', null, true) == $Model->data['BrwUser']['password']) {
				unset($Model->data['BrwUser']['password']);
				if (isset($Model->data['BrwUser']['repeat_password'])) {
					unset($Model->data['BrwUser']['repeat_password']);
				}
			}
		}
		return $Model->data;
	}


	function sites($Model, $user) {
		$siteModel = Configure::read('multiSitesModel');
		$params = array();
		if (!$user['root']) {
			$params['conditions'] = array($siteModel . '.brw_user_id' => $user['id']);
		}
		return $Model->{$siteModel}->find('list', $params);
	}

	function checkPasswordMatch($Model, $data) {
		$password = $Model->data[$Model->name]['password'];
		$repeat_password = Security::hash($Model->data[$Model->name]['repeat_password'], null, true);
		return ($password == $repeat_password);
	}

}