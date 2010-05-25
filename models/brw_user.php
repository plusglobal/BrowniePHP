<?php
class BrwUser extends AppModel {

	var $name = 'BrwUser';
	var $brownieCmsConfig = array(
		'fields' => array(
			'no_edit' => array('last_login'),
			'no_add' => array('last_login'),
		),
		'names' => array(
			'plural' => 'Usuarios',
			'singular' => 'Usuario'
		),
		'admin' => array(
			'hide' => true
		),
	);

	var $belongsTo = array('BrwGroup');

}
?>