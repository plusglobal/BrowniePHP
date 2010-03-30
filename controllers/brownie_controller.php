<?php
class BrownieController extends BrownieAppController {

	var $name = 'Brownie';

	function index() {

	}

	function translations() {
		$models = Configure::listObjects('model');
		$out = '<?php ';
		foreach($models as $model){
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

	function models_2_db() {
		$BrwModel = ClassRegistry::init('BrwModel');
		$models = Configure::listObjects('model');
		foreach($models as $model){
			$Model = ClassRegistry::init($model);
			if($this->_isAdministrable($Model)) {
				$data = array();
				$data['BrwModel']['model'] = $Model->name;

				if(!empty($Model->brownieCmsConfig['names']['plural'])){
					$data['BrwModel']['seccion'] = $Model->brownieCmsConfig['names']['plural'];
				} else {
					$data['BrwModel']['seccion'] = Inflector::humanize(Inflector::pluralize($Model->name));
				}

				if($modelData = $BrwModel->findByModel($Model->name)) {
					$data['BrwModel']['id'] = $modelData['BrwModel']['id'];
				} else {
					$data['BrwModel']['id'] = '';
				}
				//pr($data);
				$BrwModel->save($data);
			}
		}
	}

	function _isAdministrable($Model) {
		if(empty($Model->brownieCmsConfig) or !$Model->brownieCmsConfig){
			return false;
		} elseif(isset($Model->brownieCmsConfig['admin']['hide'])) {
			return !$Model->brownieCmsConfig['admin']['hide'];
		} else {
			return true;
		}
	}
}
?>