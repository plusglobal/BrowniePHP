<?php

class BrownieAppController extends AppController {

	var $components = array('Brownie.MyAuth', 'Session');
	var $helpers = array('Html', 'Session', 'Javascript');
	var $uses = array('BrwUser');
	var $layout = 'brownie_default';
	static $currentUser;


	function constructClasses() {
		parent::constructClasses();
		$this->Auth = $this->MyAuth;
	}


	function beforeFilter() {
		$this->_authSettings();
		$this->_menuConfig();
		$this->pageTitle = __d('brownie', 'Control panel', true);

	    Configure::write('brwSettings.authModel', $this->Session->read('authModel'));
	    Configure::write('authUser', $this->Session->read('Auth.BrwUser'));
		parent::beforeFilter();
	}

	function beforeRender() {
		$this->_companyName();
		parent::beforeRender();
	}

	function _authSettings() {
		$this->Auth->userModel = 'BrwUser';
		$this->Auth->fields = array('username'  => 'email', 'password'  => 'password');
		$this->Auth->loginAction = array('controller' => 'brownie', 'action' => 'login', 'plugin' => 'brownie');
		$this->Auth->loginRedirect = array('controller' => 'brownie', 'action' => 'index', 'plugin' => 'brownie');
		$this->Auth->loginError = __d('brownie', 'Login failed. Invalid username or password.', true);
		$this->Auth->authError = __d('brownie', 'Please login.', true);
		Configure::write('Auth.BrwUser', $this->Session->read('Auth.BrwUser'));
		$this->set('authUser', $this->Session->read('Auth.BrwUser'));
		$this->set('BrwUser', $this->Session->read('Auth.BrwUser'));
		self::$currentUser = $this->Session->read('Auth.BrwUser');
	}


	/*
	function _modelsToDb() {
		if (Configure::read('debug')) {
			$modelsHash = $this->_modelsHash();
			if (!$this->Session->check('modelsHash') or $this->Session->read('modelsHash') != $modelsHash) {
				$this->Session->write('modelsHash', $modelsHash);
				$this->BrwModel->toDb();
			}
		}
	}

	function _modelsHash() {
		$handle = opendir(MODELS);
		$toHash = date('h');
		while ($file = readdir($handle)) {
			if ($file != '.' and $file != '..') {
				$toHash .= $file . filesize(MODELS . DS . $file);
			}
		}
		return Security::hash($toHash);
	}*/

	function _menuConfig() {
		if (!empty($this->brwMenu)) {
			$menu = $this->brwMenu;
		} else {
			$menu = array();
			$models = App::objects('model');
			foreach($models as $model) {
				$button = Inflector::humanize(Inflector::underscore(Inflector::pluralize($model)));
				$menu[$button] = $model;
			}
			$menu = array(__d('brownie', 'Menu', true) => $menu);
		}
		$this->set('brwMenu', $menu);
	}


	function _companyName() {
		if ($this->Session->check('BrwSite')) {
			$this->companyName = $this->Session->read('BrwSite.Site.name');
		} elseif (empty($this->companyName)) {
			$this->companyName = '';
		}
		$this->set('companyName', $this->companyName);
	}


	function _brwCheckPermissions($model, $action = 'read', $id = null) {
		$Model = ClassRegistry::getObject($model);
		if (!$Model) {
			return false;
		}
		//really bad patch, solucionar con permisos reales
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
		$Model->Behaviors->attach('Brownie.Panel');
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


function enum2array($string) {
	return explode("','", substr($string, 6, -2));
}
