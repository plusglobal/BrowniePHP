<?php

class BrwPanelComponent extends Component{

	public $controller;
	public $isBrwPanel;

	public function initialize(Controller $Controller, $settings = array()) {
		$this->controller = $Controller;

		$this->isBrwPanel = (
			(!empty($Controller->request->params['prefix']) and $Controller->request->params['prefix'] == 'brw')
			or
			$Controller->params['plugin'] == 'brownie'
  		);

		ClassRegistry::init('BrwUser')->Behaviors->attach('Brownie.BrwUser');
		ClassRegistry::init('BrwImage')->Behaviors->attach('Brownie.BrwUpload');
		ClassRegistry::init('BrwFile')->Behaviors->attach('Brownie.BrwUpload');
		if (!empty($Controller->request->params['prefix']) and $Controller->request->params['prefix'] == 'brw') {
			if (!class_exists('AuthComponent')) {
				$Controller->Components->load('Auth', Configure::read('brwAuthConfig'));
			} else {
				foreach (Configure::read('brwAuthConfig') as $key => $value) {
					$Controller->Auth->{$key} = $value;
				}
			}
			App::build(array('views' => ROOT . DS . APP_DIR . DS . 'Plugin' . DS . 'Brownie' . DS . 'View' . DS));
			$Controller->helpers[] = 'Js';
			$Controller->layout = 'brownie_default';
			if (!empty($Controller->modelClass)) {
				$Controller->{$Controller->modelClass}->attachBackend();
			}
		}

		if ($this->isBrwPanel) {
			AuthComponent::$sessionKey = 'Auth.BrwUserLogged';
			$this->_menuConfig();
		}

		if (Configure::read('Config.languages')) {
			$langs3chars = array();
			$l10n = new L10n();
			foreach ((array)Configure::read('Config.languages') as $lang) {
				$catalog = $l10n->catalog($lang);
				$langs3chars[$lang] = $catalog['localeFallback'] ;
			}
			Configure::write('Config.langs', $langs3chars);
		}
	}


	public function beforeRender(Controller $controller) {
		if ($this->isBrwPanel) {
			$controller->set(array(
				'companyName' => Configure::read('brwSettings.companyName'),
				'brwHideMenu' => $controller->Session->read('brw.hideMenu')
			));
		}
		$this->controller->set('brwSettings', Configure::read('brwSettings'));
	}


	public function _menuConfig() {
		if (AuthComponent::user('id')) {
			$authModel = AuthComponent::user('model');
			if ($authModel != 'BrwUser') {
				$menu = $this->controller->brwMenuPerAuthUser[$authModel];
			} elseif (!empty($this->controller->brwMenu)) {
				$menu = $this->controller->brwMenu;
			} else {
				$menu = array();
				$models = App::objects('model');
				foreach($models as $model) {
					if (!in_array($model, array('BrwUser', 'BrwImage', 'BrwFile', 'AppModel'))) {
						$button = Inflector::humanize(Inflector::underscore(Inflector::pluralize($model)));
						$menu[$button] = $model;
					}
				}
				$menu = array(__d('brownie', 'Menu') => $menu);
			}
			$this->controller->brwMenu = $menu;
			$this->controller->set('brwMenu', $menu);
		}
	}


}