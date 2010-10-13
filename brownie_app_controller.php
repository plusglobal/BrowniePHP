<?php

class BrownieAppController extends AppController {

	var $components = array('Auth', 'Session');
	var $helpers = array('Html', 'Session', 'Javascript');
	var $uses = array('BrwUser');
	static $currentUser;

	function beforeFilter() {
		$this->_authSettings();
		$this->_multiSiteSettings();
		//$this->_modelsToDb();
		$this->_menuConfig();
		$this->pageTitle = __d('brownie', 'Control panel', true);
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
		Configure::write('Auth.BrwUser', $this->Session->read('Auth.BrwUser'));
		$this->set('authUser', $this->Session->read('Auth.BrwUser'));
		$this->set('BrwUser', $this->Session->read('Auth.BrwUser'));
		$this->set('isUserRoot', $this->Session->read('Auth.BrwUser.root'));
		self::$currentUser= $this->Session->read('Auth.BrwUser');
	}

	function _multiSiteSettings() {
		if ($multiSitesModel = Configure::read('multiSitesModel')) {
			Controller::loadModel($multiSitesModel);
			$sitesOptions = $this->BrwUser->sites($this->Session->read('Auth.BrwUser'));
			if ($this->Session->read('Auth.BrwUser.root')) {
				Configure::write('currentSite', $this->Session->read('BrwSite.Site'));
			} else {
				if (count($sitesOptions) == 1) {
					$siteId = array_shift(array_keys($sitesOptions));
					Configure::write('currentSite', array_shift($this->{$multiSitesModel}->read(null, $siteId)));
				} else {
					if (in_array($this->Session->read('BrwSite.Site.id'), array_keys($sitesOptions))) {
						Configure::write('currentSite', $this->Session->read('BrwSite.Site'));
					} else {
						Configure::write('currentSite', null);
					}
				}
			}
			if (!$this->Session->check('BrwSite.Site')) {
				$this->Session->write('BrwSite.Site', Configure::read('currentSite'));
			}
			$this->set('sitesOptions', $sitesOptions);
		}
	}

	function _currentSite() {
		return Configure::read('currentSite');
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
		$menu = array();
		if	($this->_currentSite() and !empty($this->brwSiteMenu)) {
			$menu = $this->brwSiteMenu;
		} elseif($this->Session->read('Auth.BrwUser.root')) {
			if (!empty($this->brwMenu)) {
				$menu = $this->brwMenu;
			} else {
				$menu = array();
				$models = App::objects('model');
				foreach($models as $model) {
					$menu[$model] = $model;
				}
				$menu = array(__d('brownie', 'Menu', true) => $menu);
			}
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

	/*
	function checkAdminSession() {
		// if the admin session hasn't been set
		if (!$this->Session->check('BrwUser')) {
			// set flash message and redirect
			//$this->Session->setFlash(__d('brownie', 'You need to be logged in to access this area', true));
			$this->redirect(array(
				'controller' => 'users',
				'action' => 'login',
				'plugin' => 'brownie'
			));
			exit();
		} else {
			$this->user = $this->Session->read('BrwUser');
		}
	}*/

	/*
	function menuConfig($menu) {
		if ($this->Session->read('BrwUser.root')) {
			return $menu;
		}
		foreach ($menu as $keyGroup => $menuGroup) {
			foreach ($menuGroup as $key => $model) {
				if (!$this->_checkPermissions($model, 'view')) {
					unset($menu[$keyGroup][$key]);
				}
			}
			if (empty($menu[$keyGroup])) {
				unset($menu[$keyGroup]);
			}
		}
		return $menu;
	}*/

	function _checkPermissions($model, $action = 'view', $id = null) {
		//static $user, $User;
		if ($action == 'js_edit') {
			return true;
		}

		if (!in_array($action, array('index', 'add', 'view', 'delete', 'edit', 'add_images', 'edit_image', 'edit_file', 'import'))) {
			return false;
		}
		$Model = ClassRegistry::init($model);
		$Model->Behaviors->attach('Brownie.Cms');
		if (!empty($this->Content)) {
			$actions = $Model->brownieCmsConfig['actions'];
			if (!in_array($action, array('index', 'view')) and !$actions[$action]) {
				return false;
			}
		}

		if ($this->Session->read('Auth.BrwUser.root')) {

			return true;
		} else {
			if ($model == 'BrwUser') {
				switch ($action) {
					case 'add': case 'delete':
						return false;
					break;
					case 'index': case 'edit': case 'view':
						return true;
					break;
				}
			}
		}

		return true;

		/*$User = ClassRegistry::init('BrwUser');
		$User->Behaviors->attach('Containable');
		$user = $User->find('first', array(
			'conditions' => array('BrwUser.id' => $this->Session->read('BrwUser.id')),
			'contain' => array('BrwGroup' => array('BrwPermission' => array('BrwModel')))
		));
		//pr($user);
		$mapActions = array(
			'index' => 'view',
			'view' => 'view',
			'delete' => 'delete',
			'add_images' => 'edit',
			'edit' => 'edit',
		);

		if (empty($user['BrwGroup']['BrwPermission'])) {
			return false;
		} else {
			foreach ($user['BrwGroup']['BrwPermission'] as $permission) {
				if ($model == $permission['BrwModel']['model']) {
					return $permission[$mapActions[$action]];
				}
			}
		}
		*/
		return false;

	}

	function arrayPermissions($model) {
		$ret = array(
			'add' => false,
			'view' => false,
			'edit' => false,
			'delete' => false,
			'import' => false
		);
		foreach ($ret as $action => $value) {
			$ret[$action] = $this->_checkPermissions($model, $action);
		}

		return $ret;
	}


}


function enum2array($string) {
	return explode("','", substr($string, 6, -2));
}
