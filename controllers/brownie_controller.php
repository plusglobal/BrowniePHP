<?php
class BrownieController extends BrownieAppController {

	var $name = 'Brownie';

	function index() {
		$this->Session->delete('modelsHash');
	}

	function beforeFilter() {
		if (!empty($this->data['BrwUser']) and !$this->BrwUser->find('first')) {
			$this->BrwGroup->create();
			$this->BrwGroup->save(array('name' => 'root'));
			$this->BrwUser->create();
			$this->BrwUser->save(array(
				'email' => $this->data['BrwUser']['email'],
				'password' => $this->Auth->password($this->data['BrwUser']['password']),
				'brw_group_id' => $this->BrwGroup->id,
			));
		}
		parent::beforeFilter();
	}


    function login() {
    	if($this->Session->check('Auth.BrwUser')){
			$this->redirect(array('action' => 'index'));
		}
    }

    function logout() {
        $this->redirect($this->Auth->logout());
    }


	function translations() {
		$models = Configure::listObjects('model');
		$out = '<?php ';
		foreach($models as $model) {
			$Model = ClassRegistry::init($model);
			$out .= ' __("'.Inflector::humanize(Inflector::underscore($Model->name)).'"); ';
			$schema = (array)$Model->_schema;
			foreach($schema as $key => $value){
				if(strstr($value['type'], 'enum(')) {
					$options = enum2array($value['type']);
					foreach ($options as $option) {
						$out .= '__("'.$option.'");';
					}
				}
				$out .= ' __("'.Inflector::humanize(str_replace('_id', '', $key)).'"); ';
			}
		}
		$forTranslate = ROOT . DS . APP_DIR . DS . 'views' . DS . 'elements' . DS . '4translate.php';
		fwrite(fopen($forTranslate, 'w'), $out);
	}

	function test() {
		/*$group = array('BrwGroup' => array('id' => 1011));
		$user = array('BrwUser' => array('id' => 4));
		$aco =  array('BrwModel' => array('id' => 1));
		$this->Acl->deny($group, $aco);

		$site = array('BrwModel' => array('id' => 38));
		$this->Acl->allow($group, $site);

		$news = array('BrwModel' => array('id' => 16));

		$perm = $this->Acl->check($user, $news);
		var_dump($perm);*/

		$news = array('BrwModel' => array('id' => 16));
		$aNews = array('News' => array('id' => 1));
		$aco = $this->Acl->Aco->find('first', array('conditions' => array('model' => 'BrwModel', 'foreign_key' => 16)));
		var_dump($aco);

	}

}