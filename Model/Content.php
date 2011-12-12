<?php

class Content extends BrownieAppModel {

	var $useTable = false;
	var $brwConfig = array();


	function modelExists($model) {
		if ($model == 'BrwUser') {
			return true;
		}
		return in_array($model, App::objects('model'));
	}


	function getForeignKeys($Model) {
		$out = array();
		if (!empty($Model->belongsTo)) {
			foreach ($Model->belongsTo as $alias => $assocModel) {
				$out[$assocModel['foreignKey']] = array(
					'alias' => $alias,
					'className' => $assocModel['className'],
				);
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
		$schema = $Model->schema();
		$fieldsConfig = $Model->brwConfig['fields'];
		$fieldsNotUsed = array_merge(array('created', 'modified'), $fieldsConfig['no_' . $action], $fieldsConfig['hide']);
		foreach ($fieldsNotUsed as $field) {
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
		foreach ($virtuals as $virtualField => $options) {
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
		if ($edit) {
			$fields = $this->fieldsEdit($Model);
		} else {
			$fields = $this->fieldsAdd($Model);
			if (isset($fields['id'])) {
				unset($fields['id']);
			}
		}
		$rules = $fields;
		foreach ($fields as $key => $value) {
			$rules[$key] = array();

			if (!$value['null']) {
				$allowEmpty = false;
				$rules[$key][] = array(
					'rule' => array('minLength', '1'),
					'allowEmpty' => $allowEmpty,
					'message' => __d('brownie', 'This field is required')
				);
			} else {
				$allowEmpty = true;
			}

			if ($value['type'] == 'integer' or $value['type'] == 'float') {
				if ( !($Model->Behaviors->attached('Tree') and $key =='parent_id') ) {
					$rules[$key][] = array(
						'rule' => 'numeric',
						'allowEmpty' => $allowEmpty,
						'message' => __d('brownie', 'Please supply  a valid number')
					);
				}
			}

			if ($key == 'email' or strstr($key, '_email')) {
				$rules[$key][] = array(
					'rule' => 'email',
					'allowEmpty' => $allowEmpty,
					'message' => __d('brownie', 'Please supply a valid email address')
				);
			}

			if ($value['type'] == 'boolean') {
				$rules[$key][] = array(
					'rule' => 'boolean',
					'message' => __d('brownie', 'Incorrect value')
				);
			}

			if ($this->fieldUnique($Model, $key)) {
				$rules[$key][] = array(
					'rule' => 'isUnique',
					'message' => sprintf(
						($Model->brwConfig['names']['gender'] == 1) ?
							__d('brownie', "This value must be unique and it's already in use by another %s [male]"):
							__d('brownie', "This value must be unique and it's already in use by another %s [female]"),
						$Model->brwConfig['names']['singular']
					),
					'allowEmpty' => true,
				);
			}

		}

		if (!empty($Model->validate)) {
			$Model->validate = array_merge($rules, $Model->validate);
		} else {
			$Model->validate = $rules;
		}
	}


	function brownieBeforeSave($data, $Model, $Session) {
		$data['Content']['fieldList'] = array();
		foreach ($Model->schema() as $field => $value) {
			if (
				$value['null']
				and !empty($data[$Model->name][$field])
				and $data[$Model->name][$field] === ''
				and !in_array($field, $Model->brwConfig['fields']['hide'])
			) {
				$data[$Model->name][$field] = null;
			}
		}
		if ($Model->Behaviors->attached('Tree')) {
			$data = $this->treeBeforeSave($data, $Model);
		}
		if ($Model->Behaviors->attached('Translate')) {
			$data = $this->translateBeforeSave($data, $Model);
		}
		$data = $this->ownedBeforeSave($data, $Model, AuthComponent::user('id'));
		$data = $this->convertUploadsArray($data);
		return $data;
	}


	function treeBeforeSave($data, $Model) {
		if (!empty($data[$Model->name]['parent_id_NULL']) and $data[$Model->name]['parent_id_NULL']) {
			$data[$Model->name]['parent_id'] = NULL;
		}
		if (array_key_exists('lft', $data[$Model->name])) {
			unset($data[$Model->name]['lft']);
		}
		if (array_key_exists('rght', $data[$Model->name])) {
			unset($data[$Model->name]['rght']);
		}
		return $data;
	}


	function translateBeforeSave($data, $Model) {
		/*
		foreach (Configure::read('Config.languages') as $lang) {
			if (empty($data['Content']['enabled_' . $lang])) {
				$translatableFields = array_keys($Model->Behaviors->Translate->settings[$Model->alias]);
				foreach ($translatableFields as $field) {
					if (isset($data[$Model->alias][$field][$lang])) {
						unset($data[$Model->alias][$field][$lang]);
					}
				}
			}
		}
		pr($data);
		*/
		return $data;
	}


	function ownedBeforeSave($data, $Model, $authUserId) {
		$authModel = AuthComponent::user('model');
		if (
			$authModel
			and $authModel != 'BrwUser'
			and $Model->name != $authModel
			and $Model->brwConfigPerAuthUser[$authModel]['type'] == 'owned'
		) {
			$data['Content']['fieldList'][] = $fk = $Model->belongsTo[$authModel]['foreignKey'];
			$data[$Model->name][$fk] = $authUserId;
		}
		return $data;
	}


	function fckFields($Model) {
		$out = array();
		foreach ($Model->schema() as $field => $metadata) {
			if ($metadata['type'] == 'text' and !in_array($field, $Model->brwConfig['fields']['no_editor'])) {
				$out[] = $field;
			}
		}
		return $out;
	}


	function defaults($Model) {
		$data = array();
		foreach ($Model->schema() as $field => $value) {
			if (array_key_exists($field, $Model->brwConfig['default'])) {
				$data[$field] = $Model->brwConfig['default'][$field];
			} elseif (!empty($value['default'])) {
				 $data[$field] = $value['default'];
			}
		}
		return array($Model->alias => $data);
	}


	function delete($Model, $id) {
		if ($Model->Behaviors->attached('Tree')) {
			$deleted = $Model->removeFromTree($id, true);
		} else {
			$deleted = $Model->delete($id);
		}
		return $deleted;
	}


	function fieldUnique($Model, $field) {
		$indexes = $Model->getDataSource()->index($Model->table);
		foreach ($indexes as $index) {
			if ($index['column'] == $field and !empty($index['unique'])) {
				return true;
			}
		}
		return false;
	}


	function reorder($Model, $direction, $id) {
		if ($Model->Behaviors->attached('Tree')) {
			return ($direction == 'down') ? $Model->moveDown($id, 1) : $Model->moveUp($id, 1);
		}

		$isTanslatable = $Model->Behaviors->enabled('Translate');
		if ($isTanslatable) {
			$Model->Behaviors->disable('Translate');
		}

		$sortField = $Model->brwConfig['sortable']['field'];
		$record = $Model->findById($id);
		$params = array('field' => $sortField, 'value' => $record[$Model->alias][$sortField]);
		if ($parent = $Model->brwConfig['parent']) {
			$foreignKey = $Model->belongsTo[$parent]['foreignKey'];
			$params['conditions'] = array($Model->alias . '.' . $foreignKey => $record[$Model->alias][$foreignKey]);
		}
		$neighbors = $Model->find('neighbors', $params);
		if ($Model->brwConfig['sortable']['direction'] == 'desc') {
			$prev = 'prev'; $next = 'next';
		} else {
			$prev = 'next'; $next = 'prev';
		}
		$cual = ($direction == 'down')? $prev:$next;
		if (empty($neighbors[$cual])) {
			return false;
		}
		$swap = $neighbors[$cual];
		$saved1 = $Model->save(array('id' => $record[$Model->alias]['id'], $sortField => null));
		$saved2 = $Model->save(array('id' => $swap[$Model->alias]['id'], $sortField => $record[$Model->alias][$sortField]));
		$saved3 = $Model->save(array('id' => $record[$Model->alias]['id'], $sortField => $swap[$Model->alias][$sortField]));

		if ($isTanslatable) {
			$Model->Behaviors->enable('Translate');
		}

		return ($saved1 and $saved2 and $saved3);
	}


	function schemaForView($Model) {
		$schema = $Model->schema();
		foreach ($schema as $field => $extra) {
			switch ($extra['type']) {
				case 'float':
					$class = 'number';
				break;
				case 'integer':
					$class = ($this->isForeignKey($Model, $field)) ? 'string' : 'number';
				break;
				case 'date': case 'datetime':
					$class = 'date';
				break;
				default:
					$class = $extra['type'];
				break;
			}
			$schema[$field]['class'] = $class;
		}
		return $schema;
	}


	function isForeignKey($Model, $field) {
		foreach ($Model->belongsTo as $model => $belongsTo) {
			if ($belongsTo['foreignKey'] == $field) {
				return $model;
			}
		}
		return false;
	}


	function actions($Model, $record, $permissions) {
		$actions = $actionsTitles = array();
		$defaultAction = array(
			'title' => false,
			'url' => array(),
			'target' => '_self',
			'options' => array(),
			'confirmMessage' => false,
		);
		$actionsTitles = array_merge($actionsTitles, array(
			'add' => __d('brownie', 'Add'),
			'view' => __d('brownie', 'View'),
			'edit' => __d('brownie', 'Edit'),
			'delete' => __d('brownie', 'Delete'),
			'index' => __d('brownie', 'List all'),
		));
		foreach ($actionsTitles as $action => $title) {
			if (!empty($permissions[$action]) or in_array($action, array('up', 'down'))) {
				$url = array(
					'controller' => 'contents',
					'action' => ($action == 'add') ? 'edit' : $action,
					$Model->alias
				);
				$options = array('title' => $title);
				if (!in_array($action, array('index', 'add'))) {
					$url[] = $record[$Model->alias]['id'];
				}
				$actions[$action] = Set::merge($defaultAction, array(
					'title' => $title,
					'url' => $url,
					'options' => $options,
					'confirmMessage' => ($action == 'delete') ?
						sprintf(
							($Model->brwConfig['names']['gender'] == 1) ?
								__d('brownie', 'Are you sure you want to delete this %s?[male]'):
								__d('brownie', 'Are you sure you want to delete this %s?[female]')
							,
							$Model->brwConfig['names']['singular']
						):
						false,
					'class' => $action,
				));
			}
		}
		foreach ($Model->brwConfig['custom_actions'] as $action => $custom) {
			$matchCondition = true;
			if (!empty($custom['conditions'])) {
				$matchCondition = call_user_func(array($Model, $custom['conditions']), $record);
			}
			if ($matchCondition) {
				$custom['url'][] = $record[$Model->alias]['id'];
				$actions[$action] = Set::merge($defaultAction, $custom);
			}
		}
		return $actions;
	}


	function convertUploadsArray($data) {
		foreach (array('BrwImage', 'BrwFile') as $upload) {
			if (!empty($data[$upload]['model'])) {
				$retData = array();
				$fields = array('model', 'category_code', 'description', 'file');
				$count = count($data[$upload]['model']);
				foreach ($fields as $field) {
					for ($i = 0; $i < $count; $i++) {
						$retData[$upload][$i][$field] = $data[$upload][$field][$i];
					}
				}
				$data[$upload] = $retData[$upload];
				foreach ($data[$upload] as $key => $value) {
					if (empty($value['file']) or (!empty($value['file']['error']))) {
						unset($data[$upload][$key]);
					}
				}
				if (empty($data[$upload])) {
					unset($data[$upload]);
				}
			}
		}
		return $data;
	}


	function findList($Model, $relationData) {
		$parent = $Model->brwConfig['parent'];
		$displayField = $Model->displayField;
		if (!$parent) {
			return $Model->find('list', $relationData);
		} else {
			$parentDisplayField = $Model->{$parent}->displayField;
			$ret = array();
			$data = $Model->{$parent}->find('all', array('contain' => $Model->name));
			foreach ($data as $parentModel => $entry) {
				foreach ($entry[$Model->name] as $value) {
					$ret[$entry[$parent][$parentDisplayField]][$value['id']] = $value[$displayField];
				}
			}
			return $ret;
		}
	}


	function neighborsForView($Model, $record, $restricted, $named = array()) {
		$neighbors = array();
		$isTanslatable = $Model->Behaviors->enabled('Translate');
		if ($isTanslatable) {
			$Model->Behaviors->disable('Translate');
		}
		if (!$restricted) {
			if (!empty($named['sort']) and in_array($named['sort'], array_keys($Model->schema()))) {
				$sort_field = $named['sort'];
				$direction = 'asc';
				if (!empty($named['direction']) and in_array($named['direction'], array('desc', 'asc'))) {
					$direction = $named['direction'];
				}
			} elseif (is_array($Model->order)) {
				list($sort_field, $direction) = each($Model->order);
			} else {
				$sort_field = 'id';
				$direction = 'asc';
			}
			$sort_field = str_replace($Model->alias . '.', '', $sort_field);
			$schema = $Model->schema();
			if (
				!empty($schema[$sort_field]['key']) and
				in_array($schema[$sort_field]['key'], array('unique', 'primary'))
			) {
				$neighbors = $Model->find('neighbors', array(
					'field' => $sort_field,
					'value' => $record[$Model->alias][$sort_field],
					'conditions' => $this->filterConditions($Model, $named)
				));
			} else {
				//ToDo: custom neighborgs function for non-unique fields
			}
			if ($neighbors and $direction == 'desc') {
				$tmp = $neighbors['prev'];
				$neighbors['prev'] = $neighbors['next'];
				$neighbors['next'] = $tmp;
			}
		}
		if ($isTanslatable) {
			$Model->Behaviors->enable('Translate');
		}
		return $neighbors;
	}


	function i18nInit($Model) {
		if ($Model->Behaviors->attached('Translate')) {
			$i18nSettings = $Model->Behaviors->Translate->settings[$Model->alias];
			$settings = array();
			foreach ($i18nSettings as $key => $value) {
				$field = is_string($key)? $key : $value;
				$settings[$field] = 'BrwI18n_' . $field;
			}
			$Model->Behaviors->detach('Translate');
			$Model->Behaviors->attach('Translate', $settings);
		}
	}


	function addI18nValues($record, $Model) {
		if ($Model->Behaviors->attached('Translate')) {
			$translated = $Model->find('first', array(
				'conditions' => array($Model->alias . '.id' => $record[$Model->alias]['id']),
				'contain' => array_values($Model->Behaviors->Translate->settings[$Model->alias]),
			));
			unset($translated[$Model->alias]);
			$record = array_merge($record, $translated);
		}
		return $record;
	}


	function i18nForEdit($data, $Model) {
		if ($Model->Behaviors->attached('Translate')) {
			$dataWithTranslations = $this->addI18nValues($data, $Model);
			$settings = $Model->Behaviors->Translate->settings[$Model->alias];
			foreach ($settings as $field => $i18nModelName) {
				$dataWithTranslations[$Model->alias][$field] = array();
				foreach($dataWithTranslations[$i18nModelName] as $value) {
					$dataWithTranslations[$Model->alias][$field][$value['locale']] = $value['content'];
				}
				unset($dataWithTranslations[$i18nModelName]);
			}
			$data = array_merge($data, $dataWithTranslations);
		}
		return $data;
	}


	function relatedModelsForView($Model) {
		$ret = array_keys(array_merge($Model->hasAndBelongsToMany, $Model->belongsTo));
		if ($Model->brwConfig['images']) {
			$ret['BrwImage'] = array('fields' => '*', 'order' => 'BrwImage.id desc');
		}
		if ($Model->brwConfig['files']) {
			$ret['BrwFile'] = array('fields' => '*', 'order' => 'BrwFile.id desc');
		}
		return $ret;
	}


	function relatedModelsForIndex($Model, $paginate) {
		$containedForIndex = array();
		foreach ($paginate['fields'] as $field) {
			$relModel = $this->isForeignKey($Model, $field);
			if ($relModel) {
				$containedForIndex[] = $relModel;
			}
		}
		if (!empty($paginate['images'])) {
			$containedForIndex[] = 'BrwImage';
		}
		$containedModels = Set::normalize($this->relatedModelsForView($Model));
		foreach ($containedModels as $containedModel => $fields) {
			if (
				!in_array($containedModel, $containedForIndex)
				and $containedModel != AuthComponent::user('model')
			) {
				unset($containedModels[$containedModel]);
			} else {
				if (in_array($containedModel, array('BrwImage', 'BrwFile'))) {
					$containedModels[$containedModel]['fields'] = '*';
				} else {
					$containedModels[$containedModel]['fields'] = array('id', $Model->{$containedModel}->displayField);
				}
			}
		}
		return $containedModels;
	}


	function formatHABTMforView($record, $Model) {
		$record['HABTM'] = array();
		$i = 0;
		foreach ($Model->hasAndBelongsToMany as $relModel => $settings) {
			$record['HABTM'][$i] = array(
				'model' => $relModel,
				'name' => $Model->{$relModel}->brwConfig['names']['plural'],
				'data' => array(),
			);
			foreach ($record[$relModel] as $value) {
				$record['HABTM'][$i]['data'][$value['id']] = $value[$Model->{$relModel}->displayField];
			}
			unset($record[$relModel]);
			$i++;
		}
		return $record;
	}


	function getForExport($Model, $named) {
		if (!empty($named['direction']) and !empty($named['sort'])) {
			$order = array($Model->alias . '.' . $named['sort'] => $named['direction']);
		} else {
			$order = $Model->order;
		}
		$params = array(
			'conditions' => $this->filterConditions($Model, $named),
			'order' => $order,
			'contain' => array_keys($Model->belongsTo),
		);
		$records = $Model->find('all', $params);
		if ($Model->brwConfig['export']['replace_foreign_keys']) {
			foreach ($records as $i => $record) {
				foreach ($record[$Model->alias] as $field => $value) {
					$relModel = $this->isForeignKey($Model, $field);
					if ($relModel) {
						$displayField = $Model->{$relModel}->displayField;
						$records[$i][$Model->alias][$field] = $records[$i][$relModel][$displayField];
					}
				}
			}
		}
		return $records;
	}


	function filterConditions($Model, $named, $forData = false) {
		$filter = array();
		foreach ($Model->schema() as $field => $value) {
			if ($field == 'id') continue;
			$keyNamed = $Model->alias . '.' . $field;
			$isRange = (!$forData and (
				in_array($value['type'], array('datetime', 'date', 'float'))
				or ($value['type'] == 'integer' and !$this->isForeignKey($Model, $field))
			));
			if ($isRange) {
				if (array_key_exists($keyNamed . '_from', $named)) {
					$filter[$keyNamed . ' >= '] = $named[$keyNamed . '_from'];
				}
				if (array_key_exists($keyNamed . '_to', $named)) {
					$filter[$keyNamed . ' <= '] = $named[$keyNamed . '_to'];
				}
			} else {
				if (array_key_exists($keyNamed, $named)) {
					if (in_array($value['type'], array('integer', 'boolean', 'date', 'datetime'))) {
						if (strstr($named[$keyNamed], '.')) {
							$named[$keyNamed] = explode('.', $named[$keyNamed]);
						}
						if ($forData) {
							$filter[$Model->alias][$field] = $named[$keyNamed];
						} else {
							$filter[$keyNamed] = $named[$keyNamed];
						}
					} else {
						if ($forData) {
							$filter[$Model->alias][$field] =  $named[$keyNamed];
						} else {
							$filter[$keyNamed . ' like'] = '%' . $named[$keyNamed] . '%';
						}
					}
				}
			}
		}
		foreach ($Model->hasAndBelongsToMany as $related) {
			if (!empty($named[$related['className']])) {
				$values = explode('.', $named[$related['className']]);
				foreach ($values as $value) {
					$filter[$related['className']][]['id'] = $value;
				}
			}
		}
		return $filter;
	}


	function getRelatedBrwConfig($Model) {
		$brwConfigs = array();
		$models = array_merge($Model->hasAndBelongsToMany, $Model->belongsTo, $Model->hasMany, $Model->hasOne);
		foreach ($models as $model => $config) {
			$brwConfigs[$model] = $Model->{$model}->brwConfig;
		}
		return $brwConfigs;
	}


	function attachBackend($Model) {
		$Model->Behaviors->attach('Brownie.BrwBackend');
		$models = array_merge(
			array_keys($Model->belongsTo),
			array_keys($Model->hasAndBelongsToMany),
			array_keys($Model->hasOne),
			array_keys($Model->hasMany)
		);
		foreach ($models as $model) {
			$Model->{$model}->Behaviors->attach('Brownie.BrwBackend');
		}
	}


	function dateComplete($data, $fromOrTo, $type) {
		if (!in_array($fromOrTo, array('_from', '_to')) or empty($data['year'])) {
			return false;
		}
		if ($fromOrTo == '_from') {
			if (empty($data['month'])) {
				$data['month'] = '01';
			}
			if (empty($data['day'])) {
				$data['day'] = '01';
			}
			if ($type == 'datetime') {
				if (empty($data['hour'])) {
					$data['hour'] = '00';
				}
				if (empty($data['min'])) {
					$data['min'] = '00';
				}
				$data['sec'] = '00';
			}
		} elseif ($fromOrTo == '_to') {
			if (empty($data['month'])) {
				$data['month'] = '12';
			}
			if (empty($data['day'])) {
				$data['day'] = '31';
			}
			if ($type == 'datetime') {
				if (empty($data['hour'])) {
					$data['hour'] = '23';
				}
				if (empty($data['min'])) {
					$data['min'] = '59';
				}
				$data['sec'] = '59';
			}
		}
		return $data;
	}


	function relatedData($Model) {
		$related = array();
		if (!empty($Model->belongsTo)) {
			foreach ($Model->belongsTo as $key_model => $related_model) {
				$AssocModel = $Model->$key_model;
				if (!in_array($AssocModel, array('BrwImage', 'BrwFile'))) {
					if ($AssocModel->Behaviors->enabled('Tree')) {
						$relatedData = $AssocModel->generatetreelist();
					} else {
						$relatedData = $this->findList($AssocModel, $related_model);
					}
					$related['belongsTo'][$related_model['foreignKey']] = $relatedData;
				}
			}
		}

		if (!empty($Model->hasAndBelongsToMany)) {
			foreach ($Model->hasAndBelongsToMany as $key_model => $related_model) {
				$related['hasAndBelongsToMany'][$key_model] = $Model->$key_model->find('list', $related_model);
			}
		}

		if ($Model->Behaviors->enabled('Tree')) {
			$related['tree']['parent_id'] = $Model->generatetreelist();
		}

		return $related;
	}

}