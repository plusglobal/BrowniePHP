<?php

class PanelBehavior extends ModelBehavior {

	var $brwConfigDefault = array(
		'names' => array(
			'section' => false,
			'plural' => false,
			'singular' => false,
			'gender' => 1 //1 for male, 2 for female http://en.wikipedia.org/wiki/ISO_5218
		),
		'paginate' => array(
			'limit' => 20,
			'fields' => array(),
			'images' => array(),
		),
		'index' => array(
			'home' => true,
			'menu' => true
		),
		'fields' => array(
			'no_add' => array(),
			'no_edit' => array(),
			'no_view' => array(),
			'hide' => array('lft', 'rght'),
			'export' => array(),
			'no_export' => array('lft', 'rght'),
			'search' => array(),
			'no_search' => array(),
			'no_editor' => array(),
			'virtual' => array(),
			'conditional' => array(),
			'code' => array(),
			'no_sanitize_html' => array(),
			'names' => array(),
			'filter' => array(),
			'filter_advanced' => array(),
			'date_ranges' => array(),
		),
		'actions' => array(
			'index' => true,
			'view' => true,
			'add' => true,
			'edit' => true,
			'edit_image' => true,
			'edit_file' => true,
			'delete' => true,
			'export' => false,
			'import' => false,
			'search' => false,
			'print' => false,
			'empty' => false,
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
		'export' => array('type' => 'csv', 'replace_foreign_keys' => true),
	);


	var $brwConfigDefaultCustomActions = array(
		'title' => '',
		'url' => array('plugin' => false),
		'options' => array('target' => '_self'),
		'confirmMessage' => false,
		'conditions' => array(),
		'class' => 'custom_action',
	);


	function setup($Model, $config = array()) {
		$this->brwConfigInit($Model);
		$this->_attachUploads($Model);
	}

	function beforeValidate($Model) {
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
	}

	function afterFind($Model, $results, $primary) {
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


	function afterSave($Model, $created) {
		if (
			$Model->brwConfig['sortable']
			and $created
			and !in_array('tree', array_map('strtolower', $Model->Behaviors->_attached))
		) {
			$data = $Model->data;
			$Model->saveField($Model->brwConfig['sortable']['field'], $Model->id);
			$Model->data = $data;
		}
	}


	function beforeDelete($Model) {
		$toNullModels = array();
		$assoc = array_merge($Model->hasMany, $Model->hasOne);
		foreach($assoc as $related) {
			if (!in_array($related['className'], array('BrwImage', 'BrwFile', 'I18nModel'))) {
				if (!$related['dependent']) {
					$rel = ClassRegistry::init($related['className']);
					if ($rel) {
						if ($rel->_schema[$related['foreignKey']]['null']) {
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


	function brwConfigInit($Model) {
		$defaults = $this->brwConfigDefault;
		if (empty($Model->brwConfig)) {
			$Model->brwConfig = array();
		}
		$userModels = Configure::read('brwSettings.userModels');
		if (is_array($userModels) and in_array($Model->alias, $userModels)) {
			$defaults = $this->_brwConfigUserDefault($Model, $defaults);
		}
		$Model->brwConfig = Set::merge($defaults, $Model->brwConfig);
		$this->_sortableConfig($Model);
		$this->_paginateConfig($Model);
		$this->_namesConfig($Model);
		$this->_uploadsConfig($Model);
		$this->_conditionalConfig($Model);
		$this->_sanitizeConfig($Model);
		$this->_customActionsConfig($Model);
		$this->_fieldsNames($Model);
		$this->_fieldsFilters($Model);
		$this->_removeDuplicates($Model);
		$this->_setDefaultDateRanges($Model);
		if ($Model->brwConfig['actions']['export']) {
			$this->_exportFields($Model);
		}
		$authModel = Configure::read('brwSettings.authModel');
		if ($authModel and $authModel != 'BrwUser') {
			if (empty($Model->brwConfigPerAuthUser[$authModel]['type'])) {
				$Model->brwConfigPerAuthUser[$authModel]['type'] = 'none';
			}
			if ($Model->brwConfigPerAuthUser[$authModel]['type'] == 'none') {
				$Model->brwConfig['actions'] = array(
					'add' => false, 'edit' => false, 'index' => false,
					'view' => false, 'export' => false, 'import' => false,
				);
			} else {
				$Model->brwConfig = Set::merge($Model->brwConfig, $Model->brwConfigPerAuthUser[$authModel]['brwConfig']);
			}
		}
	}


	function _sortableConfig($Model) {
		if ($Model->brwConfig['sortable']) {
			$sortField = $Model->brwConfig['sortable']['field'];
			if (empty($Model->_schema[$sortField])) {
				$Model->brwConfig['sortable'] = false;
			} else {
				if ($Model->_schema[$sortField]['type'] == 'integer' and $Model->_schema['id']['type'] == 'integer') {
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


	function _paginateConfig($Model) {
		if (empty($Model->brwConfig['paginate']['fields'])) {
			$listableTypes = array(
				'integer', 'float', 'string', 'boolean',
				'date', 'datetime', 'time', 'timestamp',
			);
			$fields = array(); $i = 0; $schema = (array)$Model->_schema;
			$blacklist = array_merge(
				array('lft', 'rght', 'parent_id', 'created', 'modified'),
				$Model->brwConfig['fields']['hide']
			);
			foreach ($schema as $key => $values) {
				if (in_array($values['type'], $listableTypes) and !in_array($key, $blacklist)) {
					$fields[] = $key;
					if ($i++ > 4) {
						break;
					}
				}
			}
			$Model->brwConfig['paginate']['fields'] = $fields;
		}
		if (empty($Model->brwConfig['paginate']['order']) and !empty($Model->order)) {
			$Model->brwConfig['paginate']['order'] = $Model->order;
		}
	}


	function _namesConfig($Model) {
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


	function _uploadsConfig($Model) {
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


	function _conditionalConfig($Model) {
		if (!empty($Model->brwConfig['fields']['conditional'])) {
			$Model->brwConfig['fields']['conditional_camelized'] = $this->_camelize($Model->brwConfig['fields']['conditional']);
		}
	}


	function _sanitizeConfig($Model) {
		$no_sanitize = array();
		if ($Model->_schema) {
			foreach ($Model->_schema as $field => $type) {
				if ($type['type'] == 'text' and !in_array($field, $Model->brwConfig['fields']['no_sanitize_html'])) {
					$Model->brwConfig['fields']['no_sanitize_html'][] = $field;
				}
			}
		}
		return true;
	}


	function fieldsAdd($Model) {
		return $this->fieldsForForm($Model, 'add');
	}


	function fieldsEdit($Model) {
		return $this->fieldsForForm($Model, 'edit');
	}


	function fieldsForForm($Model, $action) {
		$schema = $Model->_schema;
		$fieldsConfig = $Model->brwConfig['fields'];
		$fieldsNotUsed = array_merge(array('created', 'modified'), $fieldsConfig['no_' . $action], $fieldsConfig['hide']);
		foreach($fieldsNotUsed as $field) {
			if (!empty($schema[$field])) {
				unset($schema[$field]);
			}
		}
		return $schema;
	}


	function _addImagePaths($r, $Model) {
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


	function _addFilePaths($r, $Model) {
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


	function _addBrwFilePaths($r, $Model) {
		return $this->_addBrwUploadsPaths($r, $Model, 'files');
	}


	function _addBrwImagePaths($r, $Model) {
		return $this->_addBrwUploadsPaths($r, $Model, 'images');
	}


	function _addBrwUploadsPaths($r, $Model, $fileType) {

		App::import('Brownie.BrwSanitize');
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
				$Model->alias, $value['record_id'], $value['category_code'], $value['name']
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
					$Model->alias, $value['record_id'], $value['category_code'], $value['name']
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
					. '" class="brw-file ' . end(explode('.', $value['name'])) . '">' . $value['title']
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
						foreach (Configure::read('Routing.prefixes') as $prefix) {
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



	function _camelize($array) {
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

	function _attachUploads($Model) {
		if (!empty($Model->brwConfig['images'])) {
			$Model->bindModel(array('hasMany' => array('BrwImage' => array(
				'foreignKey' => 'record_id',
				'conditions' => array('BrwImage.model' => $Model->alias),
				'dependent' => true,
			))), false);
			$Model->BrwImage->bindModel(array('belongsTo' => array($Model->alias => array(
				'foreignKey' => 'record_id',
				'conditions' => array('BrwImage.model' => $Model->alias),
				'dependent' => true,
			))), false);
		}
		if (!empty($Model->brwConfig['files'])) {
			$Model->bindModel(array('hasMany' => array('BrwFile' => array(
				'foreignKey' => 'record_id',
				'conditions' => array('BrwFile.model' => $Model->alias),
				'dependent' => true,
			))), false);
			$Model->BrwFile->bindModel(array('belongsTo' => array($Model->alias => array(
				'foreignKey' => 'record_id',
				'conditions' => array('BrwFile.model' => $Model->alias),
				'dependent' => true,
			))), false);
		}
	}


	function sanitizeHtml($Model, $results) {
		App::import('Brownie.BrwSanitize');
		foreach ($results as $i => $result) {
			if (!empty($result[$Model->alias])) {
				foreach ($result[$Model->alias] as $key => $value) {
					if (
						!empty($Model->brwConfig['fields']['no_sanitize_html']) and
						!in_array($key, $Model->brwConfig['fields']['no_sanitize_html'])
					) {
						$results[$i][$Model->alias][$key] = BrwSanitize::html($results[$i][$Model->alias][$key]);
					}
				}
			}
		}
		return $results;
	}


	function _customActionsConfig($Model) {
		$customActionsTypes = array('custom_actions', 'global_custom_actions');
		foreach ($customActionsTypes as $customActionType) {
			$customActions = array();
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
			$Model->brwConfig[$customActionType] = $customActions;
		}
	}


	function _fieldsNames($Model) {
		$defaultNames = array();
		foreach ((array)$Model->_schema as $field => $value) {
			$defaultNames[$field] = Inflector::humanize(str_replace('_id', '', $field));
		}
		foreach ($Model->brwConfig['fields']['virtual'] as $field => $value) {
			$defaultNames[$field] = Inflector::humanize(str_replace('_id', '', $field));
		}
		$Model->brwConfig['fields']['names'] = Set::merge($defaultNames, $Model->brwConfig['fields']['names']);
	}


	function _fieldsFilters($Model) {
		$Model->brwConfig['fields']['filter'] = Set::normalize($Model->brwConfig['fields']['filter']);
		$Model->brwConfig['fields']['filter_advanced'] = Set::normalize($Model->brwConfig['fields']['filter_advanced']);

		$Model->brwConfig['fields']['filter'] = array_merge(
			$Model->brwConfig['fields']['filter'],
			$Model->brwConfig['fields']['filter_advanced']
		);
	}


	function _brwConfigUserDefault($Model, $defaults) {
		$Model->Behaviors->attach('Brownie.BrwUser');
		$brwUserDefaults = array(
			'fields' => array(
				'no_edit' => array('last_login'),
				'no_add' => array('last_login'),
				'no_view' => array('password'),
				'virtual' => array('repeat_password' => array('after' => 'password')),
				'hide' => array('last_login'),
			),
			'paginate' => array(
				'fields' => array('id', 'email'),
			),
			'legends' => array(
				'password' => __d('brownie', 'Leave blank for no change', true),
			),
		);
		if ($Model->alias == 'BrwUser') {
			$brwUserDefaults['names'] = array(
				'section' => __d('brownie', 'User', true),
				'singular' => __d('brownie', 'User', true),
				'plural' => __d('brownie', 'Users', true),
			);
		}
		$defaults = Set::merge($defaults, $brwUserDefaults);
		return $defaults;
	}


	function _removeDuplicates($Model) {
		$brwConfig = $Model->brwConfig;

		$brwConfig['paginate']['fields'] = array_keys(array_flip($brwConfig['paginate']['fields']));
		$brwConfig['fields']['no_add'] = array_keys(array_flip($brwConfig['fields']['no_add']));
		$brwConfig['fields']['no_edit'] = array_keys(array_flip($brwConfig['fields']['no_edit']));
		$brwConfig['fields']['hide'] = array_keys(array_flip($brwConfig['fields']['hide']));
		$brwConfig['fields']['no_view'] = array_keys(array_flip($brwConfig['fields']['no_view']));
		$brwConfig['fields']['no_sanitize_html'] = array_keys(array_flip($brwConfig['fields']['no_sanitize_html']));
		$brwConfig['hide_children'] = array_keys(array_flip($brwConfig['hide_children']));

		$Model->brwConfig = $brwConfig;
	}


	function _setDefaultDateRanges($Model) {
		foreach ((array)$Model->_schema as $field => $config) {
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
						$Model->brwConfig['fields']['date_ranges'][$field]['dateFormat'] = 'MDY';
					}
					if (!isset($Model->brwConfig['fields']['date_ranges'][$field]['monthNames'])) {
						$Model->brwConfig['fields']['date_ranges'][$field]['monthNames'] = true;
					}
				}
			}
		}
	}


	function _exportFields($Model) {
		if (empty($Model->brwConfig['fields']['export'])) {
			foreach ($Model->_schema as $field => $config) {
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
	}


}