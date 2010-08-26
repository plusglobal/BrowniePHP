<?php
class Content extends BrownieAppModel{

	var $name = 'Content';
	var $useTable = false;

	var $cmsConfig = array();

	function modelExists($model) {
		return in_array($model, Configure::listObjects('model'));
	}

	function getCmsConfig($Model, $key = null) {
		$config = $Model->brownieCmsConfig;
		//pr($config);
		if($key){
			if(!empty($config[$key])){
				return $config[$key];
			} else {
				return array();
			}
		} else {
			return $config;
		}
	}

	function formatForView($data, $Model) {
		$out = array();
		if(!empty($data[$Model->name])){
			$out = $this->formatSingleForView($data, $Model);
		} else {
			foreach($data as $dataset){
				$out[] = $this->formatSingleForView($dataset, $Model);
			}
		}
		return $out;
	}

	function formatSingleForView($data, $Model) {
		$fieldsConfig = $this->getCmsConfig($Model, 'fields');
		$fieldsHide = $fieldsConfig['hide'];
		$foreignKeys = $this->getForeignKeys($Model);
		foreach($data[$Model->name] as $key => $value) {
			if(in_array($key, $fieldsHide)){
				unset($data[$Model->name][$key]);
			} elseif(in_array($key, $fieldsConfig['code'])) {
				$data[$Model->name][$key] = '<pre>' . htmlspecialchars($data[$Model->name][$key]) . '</pre>';
			} elseif(isset($foreignKeys[$key])) {
				$read = $Model->{$foreignKeys[$key]}->read(null, $data[$Model->name][$key]);
				$data[$Model->name][$key] = $read[$foreignKeys[$key]][$Model->{$foreignKeys[$key]}->displayField];
			} elseif(!empty($Model->_schema[$key]['type'])) {
				switch($Model->_schema[$key]['type']){
					case 'boolean':
						$data[$Model->name][$key] = ife($data[$Model->name][$key], __d('brownie', 'Yes', true), __d('brownie', 'No', true));
					break;
					case 'datetime':
						$data[$Model->name][$key] = $this->formatDateTime($data[$Model->name][$key]);
					break;
					case 'date':
						$data[$Model->name][$key] = $this->formatDate($data[$Model->name][$key]);
					break;
				}
			}
		}
		return $data;
	}


	function getForeignKeys($Model) {
		$out = array();
		if(!empty($Model->belongsTo)) {
			foreach($Model->belongsTo as $assocModel){
				$out[$assocModel['foreignKey']] = $assocModel['className'];
			}
		}
		return $out;
	}

	function fieldsAdd($Model) {
		return $this->_fieldsForForm($Model, 'add');
	}


	function fieldsEdit($Model) {
		return $this->_fieldsForForm($Model, 'edit');
	}

	function _fieldsForForm($Model, $action) {
		$schema = $Model->_schema;
		$fieldsConfig = $Model->brownieCmsConfig['fields'];
		$fieldsNotUsed = array_merge(array('created', 'modified'), $fieldsConfig['no_' . $action], $fieldsConfig['hide']);
		foreach($fieldsNotUsed as $field){
			if (isset($schema[$field])) {
				unset($schema[$field]);
			}
		}
		$schema = $this->_addVirtualFields($schema, $fieldsConfig['virtual']);
		return $schema;
	}


	function _addVirtualFields($schema, $virtuals) {
		$virtuals = Set::normalize($virtuals);
		$default = array(
			'type' => array('type' => 'string', 'null' => true, 'length' => 255),
			'after' => null,
		);
		foreach($virtuals as $virtualField => $options) {
			if (empty($options)) {
				$options = array();
			}
			$options = array_merge($default, $options);
			if (empty($options['after'])) {
				$schema[$virtualField] = $options['type'];
			} else {
				$ret = array();
				foreach ($schema as $field => $type) {
					$ret[$field] = $type;
					if ($field == $options['after']) {
						$ret[$virtualField] = $options['type'];
					}
				}
				$schema = $ret;
			}
		}
		return $schema;
	}

	function addValidationsRules($Model, $edit) {
		if($edit){
			$fields = $this->fieldsEdit($Model);
		} else {
			$fields = $this->fieldsAdd($Model);
			if(isset($fields['id'])){
				unset($fields['id']);
			}
		}
		$rules = $fields;
		foreach($fields as $key => $value) {
			$rules[$key] = array();

			if(!$value['null']) {
				$allowEmpty = false;
				$rules[$key][] = array(
					'rule' => array('minLength', '1'),
					'allowEmpty' => $allowEmpty,
					'message' => __d('brownie', 'This field is required', true)
				);
			} else {
				$allowEmpty = true;
			}

			if($value['type'] == 'integer' or $value['type'] == 'float') {
				if( !($this->isTree($Model) and $key =='parent_id') ){
					$rules[$key][] = array(
						'rule' => 'numeric',
						'allowEmpty' => $allowEmpty,
						'message' => __d('brownie', 'Please supply  a valid number', true)
					);
				}
			}

			if($key == 'email' or strstr($key, '_email')) {
				$rules[$key][] = array(
					'rule' => 'email',
					'allowEmpty' => $allowEmpty,
					'message' => __d('brownie', 'Please supply a valid email address', true)
				);
			}

			if($value['type'] == 'boolean') {
				$rules[$key][] = array(
					'rule' => 'boolean',
					'message' => __d('brownie', 'Incorrect value', true)
				);
			}

		}

		if (!empty($Model->validate)) {
			$Model->validate = array_merge($rules, $Model->validate);
		} else {
			$Model->validate = $rules;
		}
	}


	function formatDate($date) {
		if (empty($date) or $date == '0000-00-00') {
			return __d('brownie', 'Date not set', true);
		} else {
			App::Import('Helper', 'Time');
			$time = new TimeHelper();
			return $time->format('d/m/Y', $date, __d('brownie', 'Invalid date', true));
		}
	}

	function formatDateTime($datetime) {
		if (empty($datetime) or $datetime == '0000-00-00 00:00:00') {
			return __d('brownie', 'Datetime not set', true);
		} else {
			App::Import('Helper', 'Time');
			$time = new TimeHelper();
			return $time->format('d/m/Y H:i:s', $datetime, __d('brownie', 'Invalid datetime', true));
		}
	}


	function isTree($Model) {
		return in_array('tree', array_map('strtolower', $Model->Behaviors->_attached));
	}


	function brownieBeforeSave($data, $Model) {
		if($this->isTree($Model)){
			$data = $this->treeBeforeSave($data, $Model);
		}
		return $data;
	}


	function treeBeforeSave($data, $Model) {
		if(!empty($data[$Model->name]['parent_id_NULL']) and $data[$Model->name]['parent_id_NULL']){
			$data[$Model->name]['parent_id'] = NULL;
		}
		return $data;
	}


	function fckFields($Model) {
		$out = array();
		foreach($Model->_schema as $field => $metadata){
			if($metadata['type'] == 'text' and !in_array($field, $Model->brownieCmsConfig['fields']['no_editor'])) {
				$out[] = $field;
			}
		}
		return $out;
	}

}
?>