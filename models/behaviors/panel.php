<?php

class PanelBehavior extends ModelBehavior {

	var $brwConfigDefault = array(
		'names' => array(
			'section' => false,
			'plural' => false,
			'singular' => false,
			'gender' => 1 //1 for male, 2 for female, according to http://en.wikipedia.org/wiki/ISO_5218
		),

		'paginate' => array(
			'limit' => 20,
			'fields' => array()
		),

		'index' => array(
			'home' => true,
			'menu' => true
		),

		'fields' => array(
			'no_add' => array(),
			'no_edit' => array(),
			'hide' => array('lft', 'rght'),
			'export' => array(),
			'no_export' => array(),
			'search' => array(),
			'no_search' => array(),
			'no_editor' => array(),
			'virtual' => array(),
			'conditional' => array(),
			'code' => array(),
			'no_sanitize_html' => array(),
			'names' => array(),
		),

		'fields_no_root' => array(),

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

		'actions_no_root' => array(),

		'custom_actions' => array(),

		'images' => array(),

		'files' => array(),

		'default' => array(),

		'parent' => null,

		'show_children' => true,

		'hide_children' => array('BrwImage', 'BrwFile'),

		'site_dependent' => true,

		'sortable' => array('field' => 'sort', 'sort' => 'ASC')

	);

	var $brwConfigDefaultImage = array(
		'name_category' => 'Images',
		'sizes' => array(),
		'index' => false,
		'description' => true,
	);

	var $brwConfigDefaultFile = array(
		'name_category' => 'Files',
		'index' => false,
		'description' => true,
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
		$this->_treeMultiSites($Model);
	}

	function beforeFind($Model, $query) {
		$this->_treeMultiSites($Model);

		if ($site = Configure::read('currentSite') and !in_array($Model->name, array('BrwImage', 'BrwFile'))) {
			if($this->isSiteDependent($Model)) {
				if (empty($query['conditions'])) {
					$query['conditions'] = array();
				}
				if (is_array($query['conditions'])) {
					$foreignKey = $Model->belongsTo[Configure::read('multiSitesModel')]['foreignKey'];
					$query['conditions'][$Model->alias . '.' . $foreignKey] = $site['id'];
				}
			}
		}
		return $query;
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

	function beforeValidate($Model) {
		$this->_treeMultiSites($Model);

		if ($site = Configure::read('currentSite') and $this->isSiteDependent($Model)) {
			$Model->data[$Model->alias]['site_id'] = $site['id'];
		}
		return $Model->data;
	}

	function beforeSave($Model) {
		return $this->beforeValidate($Model);
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
					if($rel = ClassRegistry::getObject($related['className'])) {
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


	function isSiteDependent($Model) {
		if (in_array($Model->name, array('BrwImage', 'BrwFile'))) {
			return false;
		} else {
			return !empty($Model->belongsTo[Configure::read('multiSitesModel')]) and $Model->brwConfig['site_dependent'];
		}
	}


	function brwConfigInit($Model) {
		if (empty($Model->brwConfig)) {
			$Model->brwConfig = array();
		}
		$Model->brwConfig = Set::merge($this->brwConfigDefault, $Model->brwConfig);

		if ($this->isSiteDependent($Model) and Configure::read('multiSitesModel')) {
			$Model->brwConfig['fields']['hide'][] = 'site_id';
		}

		$this->_sortableConfig($Model);
		$this->_paginateConfig($Model);
		$this->_parentConfig($Model);
		$this->_namesConfig($Model);
		$this->_filesAndImagesConfig($Model);
		$this->_conditionalConfig($Model);
		$this->_sanitizeConfig($Model);
		$this->_customActionsConfig($Model);
		$this->_fieldsNames($Model);
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
			$listableTypes = array('integer', 'float', 'string', 'boolean', 'date', 'datetime', 'time', 'timestamp');
			$fields = array();
			$i = 0;
			$schema = (array)$Model->_schema;
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
		if (!empty($Model->order)) {
			$Model->brwConfig['paginate']['order'] = $Model->order;
		}
	}

	function _parentConfig($Model) {
		$siteModel = Configure::read('multiSitesModel');
		if (!isset($Model->brwConfig['parent'])) {
			$belongsTo = $Model->belongsTo;
			if(isset($belongsTo[$siteModel])) {
				unset($belongsTo[$siteModel]);
			}
			$keys = array_keys($belongsTo);
			if (!empty($keys[0])) {
				$Model->brwConfig['parent'] = $keys[0];
			}
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

	function _filesAndImagesConfig($Model) {
		if ($Model->brwConfig['images']) {
			$Model->bindModel(array('hasMany' => array('BrwImage' => array(
				'foreignKey' => 'record_id',
				'conditions' => array('BrwImage.model' => $Model->name)
			))), false);
			foreach($Model->brwConfig['images'] as $key => $value) {
				$Model->brwConfig['images'][$key] = Set::merge($this->brwConfigDefaultImage, $value);
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

		if ($Model->brwConfig['files']) {
			$Model->bindModel(array('hasMany' => array('BrwFile' => array(
				'foreignKey' => 'record_id',
				'conditions' => array('BrwFile.model' => $Model->name)
			))), false);
			foreach($Model->brwConfig['files'] as $key => $value) {
				$Model->brwConfig['files'][$key] = Set::merge($this->brwConfigDefaultFile, $value);
			}
		}
	}

	function _conditionalConfig($Model) {
		if (!empty($Model->brwConfig['fields']['conditional'])) {
			$Model->brwConfig['fields']['conditional'] = $this->_camelize($Model->brwConfig['fields']['conditional']);
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
				if(is_array($value)) {
					$r[$key] = $this->_addFilePaths($value, $Model);
				} else {
					$r[$key] = $value;
				}
			}
		}
		return $r;
	}


	function _addBrwImagePaths($r, $Model) {
		App::import('Brownie.BrwSanitize');
		$ret = array();
		foreach ($Model->brwConfig['images'] as $catCode => $value) {
			$ret[$catCode] = array();
		}
		foreach ($r as $key => $value) {
			if (!isset($Model->brwConfig['images'][$value['category_code']])) {
				continue;
			}
			$relative_path = 'uploads/' . $value['model'] . '/' . $value['record_id'] . '/' . $value['name'];
			$paths = array(
				'path' => Router::url('/' . $relative_path),
				'real_path' => WWW_ROOT . str_replace('/', DS, $relative_path),
			);
			if (!empty($Model->brwConfig['images'][$value['category_code']]['sizes'])) {
				$paths['sizes'] = array();
				$sizes = $Model->brwConfig['images'][$value['category_code']]['sizes'];
				foreach($sizes as $size) {
					$cachedPath = WWW_ROOT . 'uploads' . DS . 'thumbs' . DS . $value['model'] . DS . $size
						. DS . $value['record_id'] . DS . $value['name'];
					if (is_file($cachedPath)) {
						$paths['sizes'][$size] = Router::url('/uploads/thumbs/' . $value['model'] . '/' . $size
							. '/' . $value['record_id'] . '/' . $value['name']);
					} else {
						$paths['sizes'][$size] = Router::url(array('plugin' => 'brownie', 'controller' => 'thumbs',
							'action' => 'view', $value['model'], $value['record_id'], $size, $value['name']));
					}
				}
				$value['description'] = BrwSanitize::html($value['description']);
				$value['alt'] = $value['description'];
				if (empty($value['description'])) {
					$value['alt'] = $value['name'];
					$value['description'] = '';
				}
				$r[$key]['alt'] = $value['alt'];
				$r[$key]['description'] = $value['description'];
				if (!empty($sizes[0])) {
					if ($sizes[0] != end($sizes)) {
						$paths['tag'] = '<a class="brw-image" title="' . htmlspecialchars($value['description']) .
							'" href="' . $paths['sizes'][end($sizes)] . '" rel="brw_image_' . $value['record_id'] .
							'"><img alt="' . htmlspecialchars($value['description']) . '" src="' . $paths['sizes'][$sizes[0]] . '" /></a>';
					} else {
						$paths['tag'] = '<img alt="' . htmlspecialchars($value['description']) .
							'" src="' . $paths['sizes'][$sizes[0]] . '" />';
					}
				}
			}
			$merged = am($r[$key], $paths);
			if (!empty($Model->brwConfig['images'][$value['category_code']]['index'])) {
				$ret[$value['category_code']] = $merged;
			} else {
				$ret[$value['category_code']][] = $merged;
			}
		}
		return $ret;
	}



	function _addBrwFilePaths($r, $Model) {
		App::import('Brownie.BrwSanitize');
		$ret = array();
		foreach ($Model->brwConfig['files'] as $catCode => $value) {
			$ret[$catCode] = array();
		}
		foreach ($r as $key => $value) {
			if (!isset($Model->brwConfig['files'][$value['category_code']])) {
				continue;
			}
			if (empty($value['description'])) {
				$value['description'] = $r[$key]['description'] = $value['name'];
			}
			$value['description'] = BrwSanitize::html($value['description']);

			$relativePath = 'uploads/' . $Model->name . '/' . $value['record_id'] . '/' . $value['name'];
			$completePath = Router::url('/' . $relativePath);
			$extension = end(explode('.', $value['name']));
			$forceDownloadUrl = Router::url(array(
				'plugin' => 'brownie', 'controller' => 'downloads', 'action' => 'get',
				$Model->alias, $value['record_id'], $value['name']
			));
			$paths = array(
				'path' => $completePath,
				'real_path' => WWW_ROOT . str_replace('/', DS, $relativePath),
				'tag' => '
					<a title="' . htmlspecialchars($value['description']) . '" href="' . $completePath .
						'" class="brw-file '.$extension.'">
						' . $value['description'] . '
					</a>',
				'force_download' => $forceDownloadUrl,
				'tag_force_download' =>'
					<a title="' . htmlspecialchars($value['description']) . '" href="' . $forceDownloadUrl .
						'" class="brw-file '.$extension.'">
						' . $value['description'] . '
					</a>',
			);
			$merged = am($r[$key], $paths);
			if (!empty($Model->brwConfig['files'][$value['category_code']]['index'])) {
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


	function _treeMultiSites($Model) {
		if (Configure::read('multiSitesModel') and in_array('tree', array_map('strtolower', $Model->Behaviors->_attached))) {
			if ($site = Configure::read('currentSite')) {
				$Model->Behaviors->attach('Tree', array('scope' => $Model->alias . '.site_id = ' . $site['id']));
			}
		}
	}


	function sanitizeHtml($Model, $results) {
		App::import('Brownie.BrwSanitize');
		foreach ($results as $i => $result) {
			if (!empty($result[$Model->alias])) {
				foreach ($result[$Model->alias] as $key => $value) {
					if (!in_array($key, $Model->brwConfig['fields']['no_sanitize_html'])) {
						$results[$i][$Model->alias][$key] = BrwSanitize::html($results[$i][$Model->alias][$key]);
					}
				}
			}
		}
		return $results;
	}


	function _customActionsConfig($Model) {
		$customActions = array();
		foreach ($Model->brwConfig['custom_actions'] as $action => $config) {
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
		$Model->brwConfig['custom_actions'] = $customActions;
	}


	function _fieldsNames($Model) {
		$defaultNames = array();
		foreach ((array)$Model->_schema as $field => $value) {
			$defaultNames[$field] = Inflector::humanize(str_replace('_id', '', $field));
		}
		//pr($names);
		$Model->brwConfig['fields']['names'] = Set::merge($defaultNames, $Model->brwConfig['fields']['names']);
	}


}