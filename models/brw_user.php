<?php

class BrwUser extends AppModel {

	var $name = 'BrwUser';
	var $brownieCmsConfig = array(
		'fields' => array(
			'no_edit' => array('last_login'),
			'no_add' => array('last_login'),
			'virtual' => array('repeat_password' => array('after' => 'password'))
		),
		'names' => array(
			'plural' => 'Usuarios',
			'singular' => 'Usuario'
		),
		'paginate' => array(
			'fields' => array('id', 'username', 'last_login'),
		),
		'legends' => array(
			'password' => 'Leave blank for no change',
		),
	);
	var $belongsTo = array('BrwGroup');
	//var $virtualFields = array('repeat_password' => 'concat(BrwUser.password)');


	function brwBeforeEdit($data) {
		$data['BrwUser']['password'] = $data['BrwUser']['repeat_password'] = '';
		return $data;
	}

}