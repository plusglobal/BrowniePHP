<?php
/*
this should be placed inside a plugin bootstrap, but we  have to wait until CakePHP 2.0
*/
$defaultSettings = array(
	'css' => array(
		'/brownie/css/brownie',
		'/brownie/css/fancybox/jquery.fancybox-1.3.1',
	),
	'js' => array(
		'/brownie/js/jquery-1.3.2.min',
		'/brownie/js/jquery.fancybox-1.3.1.pack',
		'/brownie/js/jquery.selso',
		'/brownie/js/jquery.comboselect',
		'/brownie/js/jquery.jDoubleSelect',
		'/brownie/js/brownie',
	),
	'customHome' => false,
	'userModels' => array('BrwUser'),
	'uploadsPath' => './uploads',
	'dateFormat' => 'Y-m-d',
	'datetimeFormat' => 'Y-m-d h:i:s',
	'defaultExportType' => 'csv',
);
if (file_exists(WWW_ROOT . 'css' . DS . 'brownie.css')) {
	$defaultSettings['css'][] = 'brownie';
}
if (file_exists(WWW_ROOT . 'js' . DS . 'brownie.js')) {
	$defaultSettings['js'][] = 'brownie';
}
if (file_exists(WWW_ROOT . 'js' . DS . 'tiny_mce' . DS . 'jquery.tinymce.js')) {
	$defaultSettings['js'][] = 'tiny_mce/jquery.tinymce';
} elseif (file_exists(WWW_ROOT . 'js' . DS . 'fckeditor' . DS . 'fckeditor.js')) {
	$defaultSettings['js'][] = 'fckeditor/fckeditor';
} elseif (file_exists(WWW_ROOT . 'js' . DS . 'ckeditor' . DS . 'ckeditor.js')) {
	$defaultSettings['js'][] = 'ckeditor/ckeditor';
}

Configure::write('brwSettings', Set::merge($defaultSettings, (array)Configure::read('brwSettings')));


class PanelComponent extends Component{

	var $controller;


	function initialize($Controller, $settings = array()) {

		$this->controller = $Controller;

		ClassRegistry::init('BrwUser')->Behaviors->attach('Brownie.BrwUser');
		ClassRegistry::init('BrwImage')->Behaviors->attach('Brownie.BrwUpload');
		ClassRegistry::init('BrwFile')->Behaviors->attach('Brownie.BrwUpload');

		$BrwUser = $Controller->Session->read('Auth.BrwUser');
		if ($BrwUser) {
			unset($BrwUser['BrwUser']['password']);
			$Controller->set('BrwUser', $BrwUser);
			Configure::write('brwSettings.authUser', $BrwUser);
		}

		if (!empty($Controller->params['brw'])) {
			//cambiar esto por una validacion más en serio
			if (!$Controller->Session->check('Auth.BrwUser')) {
				$Controller->cakeError('error404');
			}
			App::build(array('views' => ROOT . DS . 'plugins' . DS . 'brownie' . DS . 'views' . DS));
			$Controller->helpers[] = 'javascript';
			$Controller->layout = 'brownie_default';
		}

		if (!empty($this->controller->params['brw']) or $this->controller->params['plugin'] == 'brownie') {
			$this->_authSettings();
		}

		if (Configure::read('Config.languages')) {
			$langs3chars = array();
			$l10n = new L10n();
			foreach ((array)Configure::read('Config.languages') as $lang) {
				$langs3chars[$lang] = $l10n->__l10nCatalog[$lang]['localeFallback'];
			}
			Configure::write('Config.langs', $langs3chars);
		}

	}


	function beforeRender() {
		if (!empty($this->controller->params['brw']) or $this->controller->params['plugin'] == 'brownie') {
			$this->_menuConfig();
			$this->controller->set('companyName', $this->controller->companyName);
		}
		$this->controller->set('brwSettings', Configure::read('brwSettings'));
	}


	function _authSettings() {
		var_dump($this->controller->Auth);
		//$this->controller->Auth->userModel = 'BrwUser';
		//$this->controller->Auth->fields = array('username'  => 'email', 'password'  => 'password');
		$this->controller->Auth->loginAction = array('controller' => 'brownie', 'action' => 'login', 'plugin' => 'brownie');
		$this->controller->Auth->loginRedirect = array('controller' => 'brownie', 'action' => 'index', 'plugin' => 'brownie');
		$this->controller->Auth->autoRedirect = false;
		$this->controller->Auth->loginError = __d('brownie', 'Login failed. Invalid username or password.', true);
		$this->controller->Auth->authError = __d('brownie', 'Please login.', true);
		Configure::write('Auth.BrwUser', $this->controller->Session->read('Auth.BrwUser'));
		$this->controller->set('brwAuthUser', $this->controller->Session->read('Auth.BrwUser'));
		$this->controller->set('BrwUser', $this->controller->Session->read('Auth.BrwUser'));
		$this->controller->currentUser = $this->controller->Session->read('Auth.BrwUser');
	}


	function _menuConfig() {
		if ($this->controller->currentUser) {
			$authModel = $this->controller->Session->read('authModel');
			if ($authModel != 'BrwUser') {
				$menu = $this->controller->brwMenuPerAuthUser[$authModel];
			} elseif (!empty($this->controller->brwMenu)) {
				$menu = $this->controller->brwMenu;
			} else {
				$menu = array();
				$models = App::objects('model');
				foreach($models as $model) {
					if (!in_array($model, array('BrwUser', 'BrwImage', 'BrwFile'))) {
						$button = Inflector::humanize(Inflector::underscore(Inflector::pluralize($model)));
						$menu[$button] = $model;
					}
				}
				$menu = array(__d('brownie', 'Menu', true) => $menu);
			}
			$this->controller->set('brwMenu', $menu);
		}
	}


}