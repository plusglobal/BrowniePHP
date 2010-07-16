<?php

class BrwUserBehavior extends ModelBehavior {

	function setup($Model, $config = array()) {
		$Model->brownieCmsConfig = $this-> _brwConfig($Model);
		$Model->validate = $this->_validate($Model);
	}

	function _brwConfig($Model) {
		$defaultBrwConfig = array(
			'fields' => array(
				'no_edit' => array('last_login'),
				'no_add' => array('last_login'),
				'virtual' => array('repeat_password' => array('after' => 'password'))
			),
			'names' => array(
				'section' => 'Usuarios',
				'plural' => 'Usuarios',
				'singular' => 'Usuario',
			),
			'paginate' => array(
				'fields' => array('id', 'email', 'last_login'),
			),
			'legends' => array(
				'password' => 'Leave blank for no change',
			),
		);
		if(empty($Model->brownieCmsConfig)) {
			$Model->brownieCmsConfig = array();
		}
		return Set::merge($defaultBrwConfig, $Model->brownieCmsConfig);
	}

	function _validate($Model) {
		$defaultValidate = array(
			'email' => array(
				array(
					'rule' => 'email'
				),
				array(
					'rule' => 'notEmpty',
					'on' => 'create',
					'required' => true,
				)
			),
			'password' => array(
				'rule' => 'notEmpty',
				'on' => 'create',
				'required' => true,
			),
		);
		return Set::merge($defaultValidate, $Model->validate);
	}

	function brwBeforeEdit($data) {
		$data['BrwUser']['password'] = $data['BrwUser']['repeat_password'] = '';
		return $data;
	}

}