<?php

class BrwUserBehavior extends ModelBehavior {

	function setup($Model, $config = array()) {
		$Model->brownieCmsConfig = $this-> _brwConfig($Model);
		$Model->validate = $this->_validate($Model);
		//$Model->bindModel(array('belongsTo' => array('BrwGroup')));
		if ($multiSitesModel = Configure::read('multiSitesModel')) {
			$Model->bindModel(array('hasAndBelongsToMany' => array($multiSitesModel)));
		}
	}

	function _brwConfig($Model) {
		$defaultBrwConfig = array(
			'fields' => array(
				'no_edit' => array('last_login'),
				'no_add' => array('last_login'),
				'virtual' => array('repeat_password' => array('after' => 'password')),
				'hide' => array('brw_group_id'),
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

	function sites($Model, $user) {
		$conditions = array();
		if (!$user['root']) {
			//filtro los sitios por los que puede ver el usuario
		}
		$sites = $Model->{Configure::read('multiSitesModel')}->find('list', $conditions);
		if (count($sites) <= 1) {
			$sites = array();
		}
		return $sites;
	}

}