<?php
class Content extends BrownieAppModel{

	var $name = 'Content';
	var $useTable = false;
	var $cmsConfig = array();

	function modelExists($model) {
		return in_array($model, Configure::listObjects('model'));
	}

	function getForeignKeys($Model) {
		$out = array();
		if (!empty($Model->belongsTo)) {
			foreach ($Model->belongsTo as $alias => $assocModel) {
				$out[$assocModel['foreignKey']] = $alias;
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
					'message' => __d('brownie', 'This field is required', true)
				);
			} else {
				$allowEmpty = true;
			}

			if ($value['type'] == 'integer' or $value['type'] == 'float') {
				if ( !($this->isTree($Model) and $key =='parent_id') ) {
					$rules[$key][] = array(
						'rule' => 'numeric',
						'allowEmpty' => $allowEmpty,
						'message' => __d('brownie', 'Please supply  a valid number', true)
					);
				}
			}

			if ($key == 'email' or strstr($key, '_email')) {
				$rules[$key][] = array(
					'rule' => 'email',
					'allowEmpty' => $allowEmpty,
					'message' => __d('brownie', 'Please supply a valid email address', true)
				);
			}

			if ($value['type'] == 'boolean') {
				$rules[$key][] = array(
					'rule' => 'boolean',
					'message' => __d('brownie', 'Incorrect value', true)
				);
			}

			if ($this->fieldUnique($Model, $key)) {
				$rules[$key][] = array(
					'rule' => 'isUnique',
					'message' => sprintf(
						($Model->brownieCmsConfig['names']['gender']==1) ?
							__d('brownie', "This value must be unique and it's already in use by another %s [male]", true):
							__d('brownie', "This value must be unique and it's already in use by another %s [female]", true),
						$Model->brownieCmsConfig['names']['singular']
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



	function isTree($Model) {
		return in_array('tree', array_map('strtolower', $Model->Behaviors->_attached));
	}


	function brownieBeforeSave($data, $Model) {
		foreach ($Model->_schema as $field => $value) {
			if (
				$value['null'] and empty($data[$Model->name][$field])
				and !in_array($field, $Model->brownieCmsConfig['fields']['hide'])
			) {
				$data[$Model->name][$field] = null;
			}
		}
		if ($this->isTree($Model)) {
			$data = $this->treeBeforeSave($data, $Model);
		}
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


	function fckFields($Model) {
		$out = array();
		foreach ($Model->_schema as $field => $metadata) {
			if ($metadata['type'] == 'text' and !in_array($field, $Model->brownieCmsConfig['fields']['no_editor'])) {
				$out[] = $field;
			}
		}
		return $out;
	}


	function defaults($Model) {
		$data = array();
		foreach ($Model->_schema as $field => $value) {
			if (array_key_exists($field, $Model->brownieCmsConfig['default'])) {
				$data[$field] = $Model->brownieCmsConfig['default'][$field];
			} elseif (!empty($value['default'])) {
				 $data[$field] = $value['default'];
			}
		}
		return array($Model->alias => $data);
	}


	function delete($Model, $id) {
		if ($this->isTree($Model)) {
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
		if ($this->isTree($Model)) {
			return ($direction == 'down') ? $Model->moveDown($id, 1) : $Model->moveUp($id, 1);
		}

		$sortField = $Model->brownieCmsConfig['sortable']['field'];
		$record = $Model->findById($id);
		$params = array('field' => $sortField, 'value' => $record[$Model->alias][$sortField]);
		if ($parent = $Model->brownieCmsConfig['parent']) {
			$foreignKey = $Model->belongsTo[$parent]['foreignKey'];
			$params['conditions'] = array($Model->alias . '.' . $foreignKey => $record[$Model->alias][$foreignKey]);
		}
		$neighbors = $Model->find('neighbors', $params);
		if ($Model->brownieCmsConfig['sortable']['direction'] == 'desc') {
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
		return ($saved1 and $saved2 and $saved3);
	}


	function schemaForView($Model) {
		$schema = $Model->_schema;
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
		foreach ($Model->belongsTo as $belongsTo) {
			if ($belongsTo['foreignKey'] == $field) {
				return true;
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
			'add' => __d('brownie', 'Add', true),
			'view' => __d('brownie', 'View', true),
			'edit' => __d('brownie', 'Edit', true),
			'delete' => __d('brownie', 'Delete', true),
		));
		foreach ($actionsTitles as $action => $title) {
			if (!empty($permissions[$action]) or in_array($action, array('up', 'down'))) {
				$url = array('controller' => 'contents', 'action' => $action, $Model->alias);
				$options = array('title' => $title);
				if($action == 'add') {
					$url['action'] = 'edit';
				} else {
					$url[] = $record[$Model->alias]['id'];
				}
				$actions[$action] = Set::merge($defaultAction, array(
					'title' => $title,
					'url' => $url,
					'options' => $options,
					'confirmMessage' => ($action == 'delete') ?
						sprintf(
							($Model->brownieCmsConfig['names']['gender'] == 1) ?
								__d('brownie', 'Are you sure you want to delete this %s?[male]', true):
								__d('brownie', 'Are you sure you want to delete this %s?[female]', true)
							,
							$Model->brownieCmsConfig['names']['singular']
						):
						false,
					'class' => $action,
				));
			}
		}
		foreach ($Model->brownieCmsConfig['custom_actions'] as $action => $custom) {
			if (Set::matches($custom['conditions'], $record)) {
				$custom['url'][] = $record[$Model->alias]['id'];
				$actions[$action] = Set::merge($defaultAction, $custom);
			}
		}
		return $actions;
	}



}