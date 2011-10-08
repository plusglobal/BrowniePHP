<?php

class BrownieController extends BrownieAppController {

	var $name = 'Brownie';


	function index() {
		$customHome = Configure::read('brwSettings.customHome');
		if ($customHome) {
			if (!empty($customHome['plugin']) and $customHome['plugin'] == 'brownie') {
				$this->redirect($customHome);
			} else {
				$this->render('custom_home');
			}
		} elseif (!empty($this->brwMenu)) {
			$url = array_shift(array_shift($this->brwMenu));
			if (!is_array($url)) {
				 $url = array(
				 	'controller' => 'contents', 'action' => 'index', 'plugin' => 'brownie',
				 	'brw' => false, $url
				);
			}
			$this->redirect($url);
		}
	}


	function beforeFilter() {
		if (!empty($this->data['BrwUser']) and !$this->BrwUser->find('first')) {
			$this->BrwUser->create();
			$this->BrwUser->save(array(
				'email' => $this->data['BrwUser']['email'],
				'password' => $this->Auth->password($this->data['BrwUser']['password']),
			));
		}
		parent::beforeFilter();
	}


    function login() {
    	$userId = $this->Session->read('Auth.BrwUser.id');
    	if ($userId) {
    		if (!empty($this->data['BrwUser']['password'])) {
    			$authModel = Configure::read('brwSettings.authModel');
    			$this->{$authModel}->updateLastLogin($userId);
    		}
			$this->redirect($this->Auth->redirect());
		}
    }


    function logout() {
    	$this->Session->delete('BrwSite');
        $this->redirect($this->Auth->logout());
    }


	function translations() {
		$models = Configure::listObjects('model');
		$translations = array();
		$out = "<?php\n__('January');__('February');__('March');__('April');__('May');__('June');
		__('July');__('August');__('September');__('October');__('November');__('December');
		__('This field cannot be left blank');";
		foreach ($models as $model) {
			$Model = ClassRegistry::init($model);
			$translations[Inflector::humanize(Inflector::underscore($Model->name))] = true;
			$schema = (array)$Model->_schema;
			foreach ($schema as $key => $value) {
				$translations[Inflector::humanize(str_replace('_id', '', $key))] = true;
			}
			foreach ($Model->brwConfig['custom_actions'] as $action => $config) {
				$translations[$config['title']] = true;
				if ($config['confirmMessage']) {
					$translations[$config['confirmMessage']] = true;
				}
			}
		}

		$translations = array_keys($translations);
		foreach ($translations as $translation) {
			$out .= "__('" . $translation . "');\n";
		}

		$forTranslate = ROOT . DS . APP_DIR . DS . 'views' . DS . 'elements' . DS . '4translate.php';
		fwrite(fopen($forTranslate, 'w'), $out);
	}


}