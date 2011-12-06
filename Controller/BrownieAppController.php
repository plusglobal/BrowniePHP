<?php

class BrownieAppController extends AppController {

	public $components = array(
		'Session',
		'Auth' => array(
			'authenticate' => array(
				'Brownie.Brw' => array(
                	'fields' => array('username' => 'email'),
				),
			),
			'loginAction' => array('controller' => 'brownie', 'action' => 'login', 'plugin' => 'brownie', 'brw' => false),
			'loginRedirect' => array('controller' => 'brownie', 'action' => 'index', 'plugin' => 'brownie', 'brw' => false),
		),
	);
	public $helpers = array('Html', 'Session', 'Js');
	public $uses = array('BrwUser');
	public $layout = 'brownie_default';
	static $currentUser;


	function __construct($request, $response) {
		$this->components['Auth']['authError'] = __d('brownie', 'Please provide a valid username and password');
		parent::__construct($request, $response);
	}


	function _brwCheckPermissions($model, $action = 'read', $id = null) {
		$Model = ClassRegistry::getObject($model);
		if (!$Model) {
			return false;
		}
		//really bad patch, fix with proper permissions
		if ($action == 'read') {
			return true;
		}
		if ($action == 'js_edit') {
			return true;
		}
		if (in_array($action, array('reorder', 'edit_upload', 'delete_upload'))) {
			$action = 'edit';
		}
		if ($action == 'filter') {
			$action = 'index';
		}
		if ($action == 'delete_multiple') {
			$action = 'delete';
		}
		if (!in_array($action, array('index', 'add', 'view', 'delete', 'edit', 'import', 'export'))) {
			return false;
		}
		$Model->Behaviors->attach('Brownie.BrwPanel');
		if (!empty($this->Content)) {
			$actions = $Model->brwConfig['actions'];
			if (!$actions[$action]) {
				return false;
			}
		}
		return true;
	}


	function arrayPermissions($model) {
		$ret = array(
			'view' => false,
			'add' => false,
			'view' => false,
			'edit' => false,
			'delete' => false,
			'import' => false,
			'index' => false,
		);
		foreach ($ret as $action => $value) {
			$ret[$action] = $this->_brwCheckPermissions($model, $action);
		}

		return $ret;
	}


}
