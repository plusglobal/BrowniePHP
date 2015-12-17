<?php

class BrwPanelBehavior extends ModelBehavior {

	public $brwConfigDefault = array(
		'names' => array(
			'section' => false,
			'plural' => false,
			'singular' => false,
			'gender' => 1 //1 for male, 2 for female http://en.wikipedia.org/wiki/ISO_5218
		),
		'paginate' => array(
			'limit' => 50,
			'maxLimit' => 500,
			'fields' => array(),
			'images' => array(),
			'conditions' => array(),
			'default_cols_count' => 5,
		),
		'index' => array(
			'home' => true,
			'menu' => true,
		),
		'fields' => array(
			'no_add' => array(),
			'no_edit' => array(),
			'no_view' => array(),
			'hide' => array('lft', 'rght'),
			'export' => array(),
			'no_export' => array('lft', 'rght'),
			'no_editor' => array(),
			'virtual' => array(),
			'conditional' => array(),
			'code' => array(),
			'sanitize_html' => array(),
			'names' => array(),
			'legends' => array(),
			'filter' => array('brwHABTM' => array()),
			'filter_advanced' => array('brwHABTM' => array()),
			'date_ranges' => array(
				'created' => array('maxYear' => 'today'),
				'modified' => array('maxYear' => 'today'),
			),
			'import' => array(),
			'email' => array(),
		),
		'actions' => array(
			'index' => true,
			'view' => true,
			'add' => true,
			'edit' => true,
			'delete' => true,
			'export' => false,
			'import' => false,
		),
		'action_labels' => array(
			'index' => 'List all',
			'view' => 'View',
			'add' => 'Add',
			'edit' => 'Edit',
			'delete' => 'Delete',
			'export' => 'Export',
			'import' => 'Import',
		),
		'custom_actions' => array(),
		'global_custom_actions' => array(),
		'images' => array(),
		'files' => array(),
		'default' => array(),
		'parent' => null,
		'show_children' => true,
		'hide_children' => array('BrwImage', 'BrwFile'),
		'hide_related' => array(
			'hasMany' => array('BrwImage', 'BrwFile'),
			'hasAndBelongsToMany' => array(),
			'hasOne' => array(),
		),
		'sortable' => array('field' => 'sort', 'sort' => 'ASC'),
		'export' => array('type' => null, 'replace_foreign_keys' => true),
	);


	public $brwConfigDefaultCustomActions = array(
		'title' => '',
		'url' => array('plugin' => false),
		'options' => array('target' => '_self'),
		'confirmMessage' => false,
		'conditions' => array(),
		'class' => 'custom_action',
	);


	public $brwDefaultVirtualField = array(
		'after' => null,
		'type' => 'string',
        'null' => null,
        'default' => '',
        'length' => 255,
        'isVirtual' => true,
	);


	public function setup(Model $Model, $config = array()) {
		if (empty($Model->brwInitiated)) {
			$this->brwConfigInit($Model);
			$this->_attachUploads($Model);
			$Model->brwInitiated = true;
		}
	}


	public function beforeValidate(Model $Model, $options = array()) {
		if ($Model->brwConfig['fields']['conditional']) {
			foreach ($Model->brwConfig['fields']['conditional'] as $field => $rules) {
				if (isset($Model->data[$Model->alias][$field])) {
					$id = $Model->data[$Model->alias][$field];
					if (!empty($rules['show_conditions'][$id])) {
						$fieldsNoValidate = array_diff($rules['hide'], $rules['show_conditions'][$id]);
						foreach ($fieldsNoValidate as $fieldNoValidate) {
							if (is_array($fieldNoValidate)) {
								foreach ($fieldNoValidate as $relModel) {
									if (isset($Model->data[$relModel][$relModel])) {
										$Model->data[$relModel][$relModel] = array();
									}
								}
							} else {
								if (isset($Model->validate[$fieldNoValidate])) {
									unset($Model->validate[$fieldNoValidate]);
								}
								$Model->data[$Model->alias][$fieldNoValidate] = null;
							}
						}
					}
				}
			}
		}
		return true;
	}


	public function afterFind(Model $Model, $results, $primary = false) {
		if ($Model->name != 'BrwImage') {
			$results = $this->_addImagePaths($results, $Model);
		}
		if ($Model->name != 'BrwFile') {
			$results = $this->_addFilePaths($results, $Model);
		}
		if (!in_array($Model->alias, array('BrwFile', 'BrwImage'))) {
			$results = $this->sanitizeHtml($Model, $results);
		}
		return $results;
	}


	public function afterSave(Model $Model, $created, $options = array()) {
		$isTree = in_array('Tree', array_map('strtolower', $Model->Behaviors->attached()));
		if (
			$Model->brwConfig['sortable']
			and $created
			and !$isTree
		) {
			$data = $Model->data;
			$Model->saveField($Model->brwConfig['sortable']['field'], $Model->id);
			$Model->data = $data;
		}
		if ($isTree) {
			$Model->recover();
		}
	}


	public function beforeDelete(Model $Model, $cascade = true) {
		$toNullModels = array();
		$assoc = array_merge($Model->hasMany, $Model->hasOne);
		foreach($assoc as $related) {
			if (!in_array($related['className'], array('BrwImage', 'BrwFile', 'I18nModel'))) {
				if (!$related['dependent']) {
					$rel = ClassRegistry::init($related['className']);
					if ($rel) {
						$schema = $rel->schema($related['foreignKey']);
						if ($schema['null']) {
							$toNullModels[] = array('model' => $rel, 'foreignKey' => $related['foreignKey']);
						} else {
							$hasAny = $rel->find('first', array(
								'conditions' => array_merge(
									array($rel->alias . '.' . $related['foreignKey'] => $Model->id),
									(array)$related['conditions']
								),
								'fields' => array('id'),
							));
							if ($hasAny) {
								return false;
							}
						}
					}
				}
			}
		}
		foreach($toNullModels as $toNullModel) {
			$toNullModel['model']->updateAll(
				array($toNullModel['model']->alias . '.' . $toNullModel['foreignKey'] => null),
				array($toNullModel['model']->alias . '.' . $toNullModel['foreignKey'] => $Model->id)
			);
		}

		return true;

	}


	public function brwConfigInit($Model) {
		if (!$Model->Behaviors->attached('Containable')) {
			$Model->Behaviors->attach('Containable');
		}
		$defaults = $this->brwConfigDefault;
		if (empty($Model->brwConfig)) {
			$Model->brwConfig = array();
		}
		$userModels = Configure::read('brwSettings.userModels');
		if (is_array($userModels) and in_array($Model->alias, $userModels)) {
			$defaults = $this->_brwConfigUserDefault($Model, $defaults);
		}
		if (class_exists('AuthComponent')) {
			if (in_array($Model->name, array('BrwImage', 'BrwFile', 'Content'))) {
				$Model->brwConfigPerAuthUser = array(AuthComponent::user('model') => array('type' => 'all'));
			}
			if (AuthComponent::user('model') != 'BrwUser' and $Model->name == 'BrwUser') {
				$Model->brwConfigPerAuthUser = array(AuthComponent::user('model') => array('type' => 'none'));
			}
		}

		$Model->brwConfig = Set::merge($defaults, $Model->brwConfig);
		$this->_virtualFieldsConfig($Model);
		$this->_configPerAuthUser($Model);
		$this->_sortableConfig($Model);
		$this->_paginateConfig($Model);
		$this->_namesConfig($Model);
		$this->_uploadsConfig($Model);
		$this->_conditionalConfig($Model);
		$this->_sanitizeConfig($Model);
		$this->_customActionsConfig($Model);
		$this->_fieldsNames($Model);
		$this->_fieldsEmail($Model);
		$this->_fieldsFilters($Model);
		$this->_removeDuplicates($Model);
		$this->_setDefaultDateRanges($Model);
		if ($Model->brwConfig['actions']['export']) {
			$this->_exportConfig($Model);
		}
	}


	public function _virtualFieldsConfig($Model) {
		$Model->brwConfig['fields']['virtual'] = Set::normalize($Model->brwConfig['fields']['virtual']);
		foreach ($Model->brwConfig['fields']['virtual'] as $field => $config) {
			$Model->brwConfig['fields']['virtual'][$field] = Set::merge(
				$this->brwDefaultVirtualField,
				$Model->brwConfig['fields']['virtual'][$field]
			);
		}
	}


	public function _sortableConfig($Model) {
		if ($Model->brwConfig['sortable']) {
			$sortField = $Model->brwConfig['sortable']['field'];
			if (!$Model->schema($sortField)) {
				$Model->brwConfig['sortable'] = false;
			} else {
				$schema = $Model->schema();
				if ($schema[$sortField]['type'] == 'integer' and $schema['id']['type'] == 'integer') {
					if (empty($Model->brwConfig['sortable']['direction'])) {
						$Model->brwConfig['sortable']['direction'] = 'asc';
					}
					$Model->brwConfig['sortable']['direction'] = strtolower($Model->brwConfig['sortable']['direction']);
					$Model->order = array($Model->alias . '.' . $sortField => $Model->brwConfig['sortable']['direction']);
					$Model->brwConfig['fields']['hide'][] = $Model->brwConfig['sortable']['field'];
				}
			}
		}
	}


	public function _paginateConfig($Model) {
		if (empty($Model->brwConfig['paginate']['fields'])) {
			$listableTypes = array(
				'integer', 'float', 'string', 'boolean',
				'date', 'datetime', 'time', 'timestamp',
			);
			$fields = array(); $i = 0; $schema = (array)$Model->schema();
			$blacklist = array_merge(
				array('lft', 'rght', 'parent_id'),
				$Model->brwConfig['fields']['hide']
			);
			foreach ($schema as $key => $values) {
				if (in_array($values['type'], $listableTypes) and !in_array($key, $blacklist)) {
					$fields[] = $key;
					$i++;
					if ($i > $Model->brwConfig['paginate']['default_cols_count']) {
						break;
					}
				}
			}
			$Model->brwConfig['paginate']['fields'] = $fields;
		} else {
			$fields = array();
			foreach ($Model->brwConfig['paginate']['fields'] as $field) {
				if (!in_array($field, $Model->brwConfig['fields']['hide'])) {
					$fields[] = $field;
				}
			}
			$Model->brwConfig['paginate']['fields'] = $fields;
		}
		if (empty($Model->brwConfig['paginate']['order']) and !empty($Model->order)) {
			$Model->brwConfig['paginate']['order'] = $Model->order;
		}
	}


	public function _namesConfig($Model) {
		$modelName = Inflector::underscore($Model->alias);
		if (empty($Model->brwConfig['names']['singular'])) {
			$Model->brwConfig['names']['singular'] = Inflector::humanize($modelName);
		}
		if (empty($Model->brwConfig['names']['plural'])) {
			$Model->brwConfig['names']['plural'] = Inflector::humanize(Inflector::pluralize($modelName));
		}
		if (empty($Model->brwConfig['names']['section'])) {
			$Model->brwConfig['names']['section'] = $Model->brwConfig['names']['plural'];
		}
	}


	public function _uploadsConfig($Model) {
		foreach (array('BrwFile' => 'files', 'BrwImage' => 'images') as $uploadModel => $uploadType) {
			if ($Model->brwConfig[$uploadType]) {
				$Model->bindModel(array('hasMany' => array($uploadModel => array(
					'foreignKey' => 'record_id',
					'conditions' => array($uploadModel . '.model' => $Model->name)
				))), false);
				$brwConfigDefaultUpload = array(
					'index' => false,
					'description' => true,
					'path' => Configure::read('brwSettings.uploadsPath'),
				);
				foreach ($Model->brwConfig[$uploadType] as $key => $value) {
					if (empty($value['name_category'])) {
						$value['name_category'] = $key;
					}
					$Model->brwConfig[$uploadType][$key] = Set::merge($brwConfigDefaultUpload, $value);
					$Model->brwConfig[$uploadType][$key]['realpath'] = realpath($Model->brwConfig[$uploadType][$key]['path']);
					if (!is_dir($Model->brwConfig[$uploadType][$key]['realpath'])) {
						mkdir($Model->brwConfig[$uploadType][$key]['path'], 0777, true);
					}
					if ($uploadModel == 'BrwImage') {
						foreach ($Model->brwConfig['images'][$key]['sizes'] as $i => $sizes) {
							if (strstr($sizes, 'x')) {
								list($w, $h) = explode('x', $sizes);
							} else {
								list($w, $h) = explode('_', $sizes);
							}
							$Model->brwConfig['images'][$key]['array_sizes'][$i] = array('w' => $w, 'h' => $h);
						}
					}
				}
			}
		}
	}


	public function _conditionalConfig($Model) {
		if (!empty($Model->brwConfig['fields']['conditional'])) {
			$Model->brwConfig['fields']['conditional_camelized'] = $this->_camelize($Model->brwConfig['fields']['conditional']);
		}
	}


	public function _sanitizeConfig($Model) {
		$defaults = array();
		foreach ((array)$Model->schema() as $field => $type) {
			$defaults[$field] = ($type['type'] == 'text') ? false : true;
		}
		$Model->brwConfig['fields']['sanitize_html'] = Set::merge($defaults, $Model->brwConfig['fields']['sanitize_html']);
	}


	public function fieldsAdd($Model) {
		return $this->fieldsForForm($Model, 'add');
	}


	public function fieldsEdit($Model) {
		return $this->fieldsForForm($Model, 'edit');
	}


	public function fieldsForForm($Model, $action) {
		$schema = $Model->schema();
		$fieldsConfig = $Model->brwConfig['fields'];
		$fieldsNotUsed = array_merge(array('created', 'modified'), $fieldsConfig['no_' . $action], $fieldsConfig['hide']);
		foreach($fieldsNotUsed as $field) {
			if (!empty($schema[$field])) {
				unset($schema[$field]);
			}
		}
		return $schema;
	}


	public function _addImagePaths($r, $Model) {
		foreach ($r as $key => $value) {
			if ($key === 'BrwImage') {
				$thisModel = $Model;
				if(!empty($r[$key][0]['model']) and $r[$key][0]['model'] != $thisModel->alias) {
					$thisModel = ClassRegistry::getObject($r[$key][0]['model']);
				}
				$r[$key] = $this->_addBrwImagePaths($value, $thisModel);
			} else {
				if(is_array($value)) {
					$r[$key] = $this->_addImagePaths($value, $Model);
				} else {
					$r[$key] = $value;
				}
			}
		}
		return $r;
	}


	public function _addFilePaths($r, $Model) {
		foreach($r as $key => $value) {
			if ($key === 'BrwFile') {
				$thisModel = $Model;
				if(!empty($r[$key][0]['model']) and $r[$key][0]['model'] != $thisModel->alias) {
					$thisModel = ClassRegistry::getObject($r[$key][0]['model']);
				}
				$r[$key] = $this->_addBrwFilePaths($value, $thisModel);
			} else {
				if (is_array($value)) {
					$r[$key] = $this->_addFilePaths($value, $Model);
				} else {
					$r[$key] = $value;
				}
			}
		}
		return $r;
	}


	public function _addBrwFilePaths($r, $Model) {
		return $this->_addBrwUploadsPaths($r, $Model, 'files');
	}


	public function _addBrwImagePaths($r, $Model) {
		return $this->_addBrwUploadsPaths($r, $Model, 'images');
	}


	public function _addBrwUploadsPaths($r, $Model, $fileType) {

		App::import('Lib', 'Brownie.BrwSanitize');
		$ret = array();
		foreach ($Model->brwConfig[$fileType] as $catCode => $value) {
			$ret[$catCode] = array();
		}
		foreach ($r as $key => $value) {
			if (!isset($Model->brwConfig[$fileType][$value['category_code']])) {
				continue;
			}
			$file = $Model->brwConfig[$fileType][$value['category_code']]['path']
				. '/' . $value['model'] . '/' . $value['record_id'] . '/' . $value['name'];
			$realPathFile = $Model->brwConfig[$fileType][$value['category_code']]['realpath']
				. DS . $value['model'] . DS . $value['record_id'] . DS . $value['name'];
			$forceDownloadUrl = Router::url(array(
				'plugin' => 'brownie', 'controller' => 'downloads', 'action' => 'get',
				$Model->alias, $fileType, $value['record_id'], $value['category_code'], $value['name']
			));
			if ($Model->brwConfig[$fileType][$value['category_code']]['description']) {
				$value['description'] = BrwSanitize::html($value['description']);
				$value['title'] = $value['description'];
			}
			if (empty($value['title'])) {
				$value['title'] = $value['name'];
			}
			$isPublic = (substr($realPathFile, 0, strlen(WWW_ROOT)) === WWW_ROOT);
			if ($isPublic) {
				$url = Router::url('/' . str_replace(DS, '/', substr($realPathFile, strlen(WWW_ROOT))));
			} else {
				$url = Router::url(array(
					'plugin' => 'brownie', 'controller' => 'downloads', 'action' => 'view',
					$Model->alias, $fileType, $value['record_id'], $value['category_code'], $value['name']
				));
			}
			$paths = array(
				'public' => $isPublic,
				'url' => $url,
				'path' => $file,
				'realpath' => $realPathFile,
				'description' => $value['description'],
				'title' => $value['title'],
				'force_download' => $forceDownloadUrl,
				'tag_force_download' => '
					<a title="' . $value['title'] . '" href="' . $forceDownloadUrl
					. '" class="brw-file ' . pathinfo($value['name'], PATHINFO_EXTENSION) . '">' . $value['title']
					. '</a>
				',
			);
			if (!empty($Model->brwConfig['images'][$value['category_code']]['sizes'])) {
				$paths['sizes'] = array();
				$sizes = $Model->brwConfig['images'][$value['category_code']]['sizes'];
				foreach($sizes as $size) {
					$cachedPath = $Model->brwConfig[$fileType][$value['category_code']]['realpath']
						. DS . 'thumbs' . DS . $value['model'] . DS . $size . DS . $value['record_id'] . DS . $value['name'];
					if (is_file($cachedPath) and $isPublic) {
						$paths['sizes'][$size] = Router::url(str_replace(DS, '/', substr($cachedPath, strlen(WWW_ROOT) - 1)));
					} else {
						$url = array(
							'plugin' => 'brownie', 'controller' => 'thumbs', 'action' => 'view',
							$value['model'], $value['record_id'], $size, $value['category_code'], $value['name']
						);
						foreach ((array)Configure::read('Routing.prefixes') as $prefix) {
							$url[$prefix] = false;
						}
						$paths['sizes'][$size] = Router::url($url);
					}
					if (is_file($cachedPath)) {
						$paths['sizes_real_paths'][$size] = $cachedPath;
					}
				}
				if (!empty($sizes[0])) {
					$paths['tag'] = '<img alt="' . $value['title'] . '" src="' . $paths['sizes'][$sizes[0]] . '" />';
					if ($sizes[0] != end($sizes)) {
						$paths['tag'] = '<a class="brw-image" title="' . $value['title'] . '" href="'
							. $paths['sizes'][end($sizes)] . '" rel="brw_image_' . $value['record_id']
							. '">' . $paths['tag'] . '</a>';
					}
				}
			}
			$merged = array_merge($r[$key], $paths);
			if ($Model->brwConfig[$fileType][$value['category_code']]['index']) {
				$ret[$value['category_code']] = $merged;
			} else {
				$ret[$value['category_code']][] = $merged;
			}
		}
		return $ret;
	}


	public function _camelize($array) {
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$array[Inflector::camelize($key)] = $this->_camelize($value);
			} else {
				$array[Inflector::camelize($key)] = Inflector::camelize($value);
			}
			if($key != Inflector::camelize($key)) {
				unset($array[$key]);
			}
		}
		return $array;
	}


	public function _attachUploads($Model) {
		if (!empty($Model->brwConfig['images'])) {
			$Model->bindModel(array('hasMany' => array('BrwImage' => array(
				'foreignKey' => 'record_id',
				'conditions' => array('BrwImage.model' => $Model->alias),
				'dependent' => true,
			))), false);
			/*
			commented because of an error in testing, revisar despues
			$Model->BrwImage->bindModel(array('belongsTo' => array($Model->name => array(
				'foreignKey' => 'record_id',
				'conditions' => array('BrwImage.model' => $Model->name),
				'dependent' => true,
			))), false);*/
		}
		if (!empty($Model->brwConfig['files'])) {
			$Model->bindModel(array('hasMany' => array('BrwFile' => array(
				'foreignKey' => 'record_id',
				'conditions' => array('BrwFile.model' => $Model->alias),
				'dependent' => true,
			))), false);
			/*
			commented because of an error in testing, revisar despues
			$Model->BrwFile->bindModel(array('belongsTo' => array($Model->alias => array(
				'foreignKey' => 'record_id',
				'conditions' => array('BrwFile.model' => $Model->alias),
				'dependent' => true,
			))), false);
			*/
		}
	}


	public function sanitizeHtml($Model, $results) {
		App::import('Lib', 'Brownie.BrwSanitize');
		foreach ($results as $i => $result) {
			if (!empty($result[$Model->alias])) {
				foreach ($result[$Model->alias] as $key => $value) {
					if (!empty($Model->brwConfig['fields']['sanitize_html'][$key])) {
						$results[$i][$Model->alias][$key] = BrwSanitize::html($results[$i][$Model->alias][$key]);
					}
				}
			}
		}
		return $results;
	}


	public function _customActionsConfig($Model) {
		$customActionsTypes = array('custom_actions', 'global_custom_actions');
		foreach ($customActionsTypes as $customActionType) {
			$customActions = array();
			if (!empty($Model->brwConfig[$customActionType])) {
				foreach ($Model->brwConfig[$customActionType] as $action => $config) {
					$customActions[$action] = Set::merge($this->brwConfigDefaultCustomActions, $config);
					$title = Inflector::humanize($action);
					if (empty($customActions[$action]['title'])) {
						$customActions[$action]['title'] = $title;
					}
					if (empty($customActions[$action]['options']['class'])) {
						$customActions[$action]['options']['class'] = $action;
						$customActions[$action]['options']['title'] = $title;
					}
				}
			}
			$Model->brwConfig[$customActionType] = $customActions;
		}
	}


	public function _fieldsNames($Model) {
		$defaultNames = array();
		foreach ((array)$Model->schema() as $field => $value) {
			$defaultNames[$field] = Inflector::humanize(str_replace('_id', '', $field));
		}
		foreach ($Model->brwConfig['fields']['virtual'] as $field => $value) {
			$defaultNames[$field] = Inflector::humanize(str_replace('_id', '', $field));
		}
		$Model->brwConfig['fields']['names'] = Set::merge($defaultNames, $Model->brwConfig['fields']['names']);
	}


	public function _fieldsEmail($Model) {
		$fieldsEmail = array();
		foreach ((array)$Model->schema() as $field => $value) {
			if ($value['type'] == 'string' and strstr('email', $field)) {
				$fieldsEmail[] = $field;
			}
		}
		$Model->brwConfig['fields']['email'] = Set::merge($fieldsEmail, $Model->brwConfig['fields']['email']);
	}

	public function _fieldsFilters($Model) {
		$filter = Set::normalize($Model->brwConfig['fields']['filter']);
		$filterAdvanced = Set::normalize($Model->brwConfig['fields']['filter_advanced']);
		$filter = Set::merge($filter, $filterAdvanced);
		foreach ($filter as $field => $cnf) {
			if (in_array($field, $Model->brwConfig['fields']['hide'])) {
				unset($filter[$field]);
			}
		}
		$Model->brwConfig['fields']['filter_advanced'] = $filterAdvanced;
		$Model->brwConfig['fields']['filter'] = $filter;
	}


	public function _brwConfigUserDefault($Model, $defaults) {
		$Model->Behaviors->attach('Brownie.BrwUser');
		$brwUserDefaults = array(
			'fields' => array(
				'no_edit' => array('last_login'),
				'no_add' => array('last_login'),
				'no_view' => array('password'),
				'virtual' => array('repeat_password' => array('after' => 'password')),
				'legends' => array(
					'password' => __d('brownie', 'Leave blank for no change'),
				),
			),
			'paginate' => array(
				'fields' => array('id', 'email', 'last_login'),
			),
		);
		if ($Model->alias == 'BrwUser') {
			$brwUserDefaults['names'] = array(
				'section' => __d('brownie', 'User'),
				'singular' => __d('brownie', 'User'),
				'plural' => __d('brownie', 'Users'),
			);
		}
		$defaults = Set::merge($defaults, $brwUserDefaults);
		return $defaults;
	}


	public function _removeDuplicates($Model) {
		$brwConfig = $Model->brwConfig;

		$brwConfig['paginate']['fields'] = array_keys(array_flip($brwConfig['paginate']['fields']));
		$brwConfig['fields']['no_add'] = array_keys(array_flip($brwConfig['fields']['no_add']));
		$brwConfig['fields']['no_edit'] = array_keys(array_flip($brwConfig['fields']['no_edit']));
		$brwConfig['fields']['hide'] = array_keys(array_flip($brwConfig['fields']['hide']));
		$brwConfig['fields']['no_view'] = array_keys(array_flip($brwConfig['fields']['no_view']));
		$brwConfig['hide_children'] = array_keys(array_flip($brwConfig['hide_children']));

		$Model->brwConfig = $brwConfig;
	}


	public function _setDefaultDateRanges($Model) {
		foreach ((array)$Model->schema() as $field => $config) {
			if (in_array($config['type'], array('date', 'datetime'))) {
				foreach (array('minYear', 'maxYear') as $yearType) {
					if (empty($Model->brwConfig['fields']['date_ranges'][$field][$yearType])) {
						$Model->brwConfig['fields']['date_ranges'][$field][$yearType] =
							date('Y') + (($yearType == 'maxYear')? 20: -150);
					} else {
						$min = $Model->brwConfig['fields']['date_ranges'][$field][$yearType];
						if (!ctype_digit($min) or !strlen($min) == 4) {
							$Model->brwConfig['fields']['date_ranges'][$field][$yearType] = date('Y', strtotime($min));
						}
					}
					if (empty($Model->brwConfig['fields']['date_ranges'][$field]['dateFormat'])) {
						$Model->brwConfig['fields']['date_ranges'][$field]['dateFormat']
							= Configure::read('brwSettings.formDateFormat');
					}
					if (!isset($Model->brwConfig['fields']['date_ranges'][$field]['monthNames'])) {
						$Model->brwConfig['fields']['date_ranges'][$field]['monthNames']
							= Configure::read('brwSettings.monthNames');
					}
				}
			}
		}
	}


	public function _exportConfig($Model) {
		if (empty($Model->brwConfig['fields']['export'])) {
			foreach ($Model->schema() as $field => $config) {
				if ($config['type'] != 'text') {
					$Model->brwConfig['fields']['export'][] = $field;
				}
			}
		}
		$whitelisted = array();
		foreach ($Model->brwConfig['fields']['export'] as $field) {
			if (!in_array($field, $Model->brwConfig['fields']['no_export'])) {
				$whitelisted[] = $field;
			}
		}
		$Model->brwConfig['fields']['export'] = $whitelisted;
		if (empty($Model->brwConfig['export']['type'])) {
			$Model->brwConfig['export']['type'] = Configure::read('brwSettings.defaultExportType');
		}
	}


	public function _configPerAuthUser($Model) {
		if (class_exists('AuthComponent')) {
			$authModel = AuthComponent::user('model');
			if ($authModel and $authModel != 'BrwUser') {
				if (!isset($Model->brwConfigPerAuthUser)) {
					$Model->brwConfigPerAuthUser = array();
				}
				if (empty($Model->brwConfigPerAuthUser[$authModel]['type'])) {
					$Model->brwConfigPerAuthUser[$authModel]['type'] = Configure::read('brwSettings.defaultPermissionPerAuthUser');
				}
				$type = $Model->brwConfigPerAuthUser[$authModel]['type'];
				if ($type == 'none') {
					$Model->brwConfig['actions'] = array(
						'add' => false, 'edit' => false, 'index' => false, 'delete' => false,
						'view' => false, 'export' => false, 'import' => false,
					);
				} else {
					$brwConfig = !empty($Model->brwConfigPerAuthUser[$authModel]['brwConfig']) ?
						$Model->brwConfigPerAuthUser[$authModel]['brwConfig'] : array();
					if ($Model->name != $authModel and $type == 'owned') {
						if (empty($Model->belongsTo[$authModel])) {
							$errorMsg = __d('brownie', 'type = owned is valid only for models that belongsTo authModel (model: %s)', $Model->name);
							throw new Exception($errorMsg);
						} else  {
							$fk = $Model->belongsTo[$authModel]['foreignKey'];
							$brwConfig['fields']['hide'][] = $fk;
						}
					}
					if (array_key_exists('fields', $brwConfig) and array_key_exists('filter', $brwConfig['fields'])) {
						$Model->brwConfig['fields']['filter'] = $brwConfig['fields']['filter'];
					}
					$Model->brwConfig = Set::merge($Model->brwConfig, $brwConfig);
				}
				if ($Model->name == $authModel) {
					$Model->brwConfig['actions']['add'] = false;
					$Model->brwConfig['actions']['delete'] = false;
					$Model->brwConfig['actions']['index'] = false;
					$Model->brwConfig['show_children'] = false;
				}
			}
		}
	}


	public function attachBackend($Model) {
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


	public function brwSchema($Model) {
		$schema = $Model->schema();
		$retSchema = array();
		$virtuals = $Model->brwConfig['fields']['virtual'];
		foreach ($schema as $field => $type) {
			$retSchema[$field] = Set::merge($type, array('isVirtual' => false));
			foreach ($virtuals as $virtualField => $options) {
				if ($options['after'] == $field) {
					$retSchema[$virtualField] = $options;
				}
			}
		}
		foreach ($virtuals as $virtualField => $options) {
			if (!$options['after']) {
				$retSchema[$virtualField] = $options;
			}
		}
		return $retSchema;
	}

}
