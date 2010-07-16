<?php

class BrownieAppController extends AppController {

	var $components = array('Auth');

	function beforeFilter() {
		$this->_authSettings();

		$this->set('menuSections', $this->menuConfig($this->menuConfig));
		$this->set('companyName', $this->companyName);
		$this->pageTitle = __d('brownie', 'Control panel', true);
		parent::beforeFilter();
	}

	function _authSettings() {
		$this->Auth->userModel = 'Brownie.BrwUser';
		$this->Auth->loginAction = array('controller' => 'brw_users', 'action' => 'login', 'plugin' => 'brownie');
		$this->Auth->loginRedirect = array('controller' => 'brownie', 'action' => 'index', 'plugin' => 'brownie');
		$this->set('authUser', $this->Session->read('Auth.BrwUser'));
		$this->set('isUserRoot', true);
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
	}

	function _checkPermissions($model, $action = 'view') {
		//static $user, $User;
		if (!in_array($action, array('index', 'add', 'view', 'delete', 'edit', 'add_images', 'edit_image', 'edit_file', 'import'))) {
			return false;
		}

		$Model = ClassRegistry::init($model);
		$Model->Behaviors->attach('Brownie.Cms');
		if (!empty($this->Content)) {
			$actions = $this->Content->getCmsConfig($Model, 'actions');
			//pr($Model->brownieCmsConfig);
			if (!in_array($action, array('index', 'view')) and !$actions[$action]) {
				return false;
			}
		}

		if ($this->Session->read('BrwUser.root')) {
			return true;
		}


		$User = ClassRegistry::init('BrwUser');
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

?>