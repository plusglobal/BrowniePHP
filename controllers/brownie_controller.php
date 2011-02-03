<?php
class BrownieController extends BrownieAppController {

	var $name = 'Brownie';

	function index() {
		//$this->Session->delete('modelsHash');
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
    	if ($this->Session->check('Auth.BrwUser')) {
			$this->redirect(array('action' => 'index'));
		}
    }

    function logout() {
    	$this->Session->delete('BrwSite');
        $this->redirect($this->Auth->logout());
    }


	function translations() {
		$models = Configure::listObjects('model');
		$translations = array();
		$out = "<?php\n";
		foreach ($models as $model) {
			$Model = ClassRegistry::init($model);
			$translations[Inflector::humanize(Inflector::underscore($Model->name))] = true;
			$schema = (array)$Model->_schema;
			foreach ($schema as $key => $value) {
				if (strstr($value['type'], 'enum(')) {
					$options = enum2array($value['type']);
					foreach ($options as $option) {
						$translations[$option] = true;
					}
				}
				$translations[Inflector::humanize(str_replace('_id', '', $key))] = true;
			}
			foreach ($Model->brownieCmsConfig['custom_actions'] as $action => $config) {
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