<?php

class CmsBehavior extends ModelBehavior {

	var $cmsConfigDefault = array(
		'names' => array(
			'section' => false,
			'plural' => false,
			'singular' => false,
			'gender' => 1 //1 for male, 2 for female, according to http://en.wikipedia.org/wiki/ISO_5218
		),

		'paginate' => array(
			'limit' => 20,
			'order' => '{model}.id desc',
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
		),

		'fields_no_root' => array(),

		'actions' => array(
			'add' => true,
			'edit' => true,
			'add_images' => true,
			'edit_image' => true,
			'edit_file' => true,
			'delete' => true,
			'export' => false,
			'import' => false,
			'search' => false,
			'print' => false,
			'empty' => false,
			'url_view' => array(),
		),

		'actions_no_root' => array(),

		'images' => array(),

		'files' => array(),

		'default' => array(),

		'parent' => false,

		'show_children' => true,

		'hide_children' => array('BrwImage', 'BrwFile'),

		'site_dependent' => true,

	);

	var $cmsConfigDefaultImage = array(
		'name_category' => 'Images',
		'sizes' => array(),
		'index' => false,
		'description' => true,
	);

	var $cmsConfigDefaultFile = array(
		'name_category' => 'Files',
		'index' => false,
		'description' => true,
	);


	function setup($Model, $config = array()) {
		$this->cmsConfigInit($Model);
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
		if (!empty($Model->brownieCmsConfig['images'])) {
			$results = $this->_addImagePaths($results, $Model);
		}
		if (!empty($Model->brownieCmsConfig['files'])) {
			$results = $this->_addFilePaths($results, $Model);
		}
		if (!empty($Model->brownieCmsConfig['actions']['url_view'])) {
			$results = $this->_addUrlView($results, $Model);
		}
		return $results;
	}

	function beforeSave($Model) {
		$this->_treeMultiSites($Model);

		if ($site = Configure::read('currentSite') and $this->isSiteDependent($Model)) {
			$Model->data[$Model->alias]['site_id'] = $site['id'];
		}
		return $Model->data;
	}

	function beforeDelete($Model) {
		$toNullModels = array();
		$assoc = array_merge($Model->hasMany, $Model->hasOne);
		foreach($assoc as $related) {
			if ($related['className'] != 'BrwImage' and  $related['className'] != 'BrwFile') {
				if (!$related['dependent']) {
					$rel = ClassRegistry::getObject($related['className']);
					if ($rel->_schema[$related['foreignKey']]['null']) {
						$toNullModels[] = array('model' => $rel, 'foreignKey' => $related['foreignKey']);
					} else {
						$hasAny = $rel->find('first', array(
							'conditions' => array($rel->alias . '.' . $related['foreignKey'] => $Model->id),
							'fields' => array('id'),
						));
						if ($hasAny) {
							return false;
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
		return !empty($Model->belongsTo[Configure::read('multiSitesModel')]) and $Model->brownieCmsConfig['site_dependent'];
	}


	function cmsConfigInit($Model) {

		if (empty($Model->brownieCmsConfig['paginate']['fields'])) {
			$Model->brownieCmsConfig['paginate']['fields'] = $this->listableFields($Model);
		}

		if (empty($Model->brownieCmsConfig['paginate']['order']) and !empty($Model->order)) {
			$Model->brownieCmsConfig['paginate']['order'] = $Model->order;
		}

		if (!isset($Model->brownieCmsConfig['parent'])) {
			$keys = array_keys($Model->belongsTo);
			if (!empty($keys[0])) {
				$Model->brownieCmsConfig['parent'] = $keys[0];
			}
		}

		if (!empty($Model->brownieCmsConfig)) {
			$Model->brownieCmsConfig = Set::merge($this->cmsConfigDefault, $Model->brownieCmsConfig);
		} else {
			$Model->brownieCmsConfig = $this->cmsConfig;
		}

		$Model->brownieCmsConfig['paginate']['order'] = str_replace(
			'{model}', $Model->alias, $Model->brownieCmsConfig['paginate']['order']
		);

		if (empty($Model->order)) {
			$Model->order = $Model->brownieCmsConfig['paginate']['order'];
		}

		$Model->brownieCmsConfig['names'] = $this->cmsConfigNames($Model->brownieCmsConfig['names'], $Model);

		if ($Model->brownieCmsConfig['images']) {
			$Model->bindModel(array('hasMany' => array('BrwImage' => array(
				'foreignKey' => 'record_id',
				'conditions' => array('BrwImage.model' => $Model->name)
			))), false);
			foreach($Model->brownieCmsConfig['images'] as $key => $value) {
				$Model->brownieCmsConfig['images'][$key] = Set::merge($this->cmsConfigDefaultImage, $value);
			}
		}

		if ($Model->brownieCmsConfig['files']) {
			$Model->bindModel(array('hasMany' => array('BrwFile' => array(
				'foreignKey' => 'record_id',
				'conditions' => array('BrwFile.model' => $Model->name)
			))), false);
			foreach($Model->brownieCmsConfig['files'] as $key => $value) {
				$Model->brownieCmsConfig['files'][$key] = Set::merge($this->cmsConfigDefaultFile, $value);
			}
		}

		if ($this->isSiteDependent($Model) and Configure::read('multiSitesModel')) {
			$Model->brownieCmsConfig['fields']['hide'][] = 'site_id';
		}

		if (!empty($Model->brownieCmsConfig['fields']['conditional'])) {
			$Model->brownieCmsConfig['fields']['conditional'] = $this->_camelize($Model->brownieCmsConfig['fields']['conditional']);
		}
		$Model->brownieCmsConfig = $Model->brownieCmsConfig;
	}


	function listableFields($Model) {
		$listableTypes = array('integer', 'float', 'string', 'boolean', 'date', 'datetime', 'time', 'timestamp');
		$fields = array();
		$i = 0;
		$schema = (array)$Model->_schema;
		foreach($schema as $key => $values) {
			if (in_array($values['type'], $listableTypes)) {
				$fields[] = $key;
			}
			if ($i++ > 5) {
				return $fields;
			}
		}
		return $fields;
	}


	function cmsConfigNames($names, $Model) {
		$modelName = Inflector::underscore($Model->name);
		if (empty($names['singular']) or !$names['singular']) {
			$names['singular'] = Inflector::humanize($modelName);
		}
		if (empty($names['plural']) or !$names['plural']) {
			$names['plural'] = Inflector::humanize(Inflector::pluralize($modelName));
		}
		if (empty($names['section']) or !$names['section']) {
			$names['section'] = $names['plural'];
		}
		return $names;
	}


	function fieldsAdd($Model) {
		return $this->fieldsForForm($Model, 'add');
	}


	function fieldsEdit($Model) {
		return $this->fieldsForForm($Model, 'edit');
	}

	function fieldsForForm($Model, $action) {
		$schema = $Model->_schema;
		$fieldsConfig = $Model->brownieCmsConfig['fields'];
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
	/* this funcion expects an array like
	[BrwImage] => Array(
	    [0] => Array(
	    	[id] => 4a6a23a8-837c-4485-834f-0fa816fac25f
			etc...
	    )
	    [1] => Array(
			[id] => 4a6a23a8-cd5c-4ae9-b69b-0fa816fac25f
			etc...
	    )
	)*/

		$ret = array();
		foreach ($Model->brownieCmsConfig['images'] as $catCode => $value) {
			$ret[$catCode] = array();
		}
		foreach ($r as $key => $value) {
			if (!isset($Model->brownieCmsConfig['images'][$value['category_code']])) {
				continue;
			}
			$relative_path = 'uploads/' . $value['model'] . '/' . $value['record_id'] . '/' . $value['name'];
			$paths = array(
				'path' => Router::url('/' . $relative_path),
				'real_path' => WWW_ROOT . str_replace('/', DS, $relative_path),
			);
			if (!empty($Model->brownieCmsConfig['images'][$value['category_code']]['sizes'])) {
				$paths['sizes'] = array();
				$sizes = $Model->brownieCmsConfig['images'][$value['category_code']]['sizes'];
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
				$value['alt'] = $value['description'];
				if (empty($value['description'])) {
					$value['alt'] = $value['name'];
					$value['description'] = '';
				}
				$r[$key]['alt'] = $value['alt'];
				$r[$key]['description'] = $value['description'];
				if (!empty($sizes[0])) {
					if ($sizes[0] != end($sizes)) {
						$paths['tag'] = '<a title="' . htmlspecialchars($value['description']) .
							'" href="' . $paths['sizes'][end($sizes)] . '" rel="brw_image_' . $value['record_id'] .
							'"><img alt="' . htmlspecialchars($value['description']) . '" src="' . $paths['sizes'][$sizes[0]] . '" /></a>';
					} else {
						$paths['tag'] = '<img alt="' . htmlspecialchars($value['description']) .
							'" src="' . $paths['sizes'][$sizes[0]] . '" />';
					}
				}
			}
			$merged = am($r[$key], $paths);
			if (!empty($Model->brownieCmsConfig['images'][$value['category_code']]['index'])) {
				$ret[$value['category_code']] = $merged;
			} else {
				$ret[$value['category_code']][] = $merged;
			}
		}
		return $ret;
	}



	function _addBrwFilePaths($r, $Model) {
		$ret = array();
		foreach ($Model->brownieCmsConfig['files'] as $catCode => $value) {
			$ret[$catCode] = array();
		}
		foreach ($r as $key => $value) {
			if (!isset($Model->brownieCmsConfig['files'][$value['category_code']])) {
				continue;
			}
			if (empty($value['description'])) {
				$value['description'] = $r[$key]['description'] = $value['name'];
			}

			$relativePath = 'uploads/' . $Model->name . '/' . $value['record_id'] . '/' . $value['name'];
			$completePath = Router::url('/' . $relativePath);
			$extension = end(explode(".", $value['name']));
			$forceDownloadUrl = Router::url(array(
				'plugin' => 'brownie', 'controller' => 'downloads', 'action' => 'get',
				$Model->alias, $value['record_id'], $value['name']
			));
			$paths = array(
				'path' => $completePath,
				'real_path' => WWW_ROOT . str_replace('/', DS, $relativePath),
				'tag' => '
					<a title="' . htmlspecialchars($value['description']) . '" href="' . $completePath .
						'" class="BrwFile '.$extension.'">
						' . $value['description'] . '
					</a>',
				'force_download' => $forceDownloadUrl,
			);
			$merged = am($r[$key], $paths);
			if (!empty($Model->brownieCmsConfig['files'][$value['category_code']]['index'])) {
				$ret[$value['category_code']] = $merged;
			} else {
				$ret[$value['category_code']][] = $merged;
			}
		}
		return $ret;
	}


	function _addUrlView($results, $Model) {
		if ($Model->brownieCmsConfig['actions']['url_view'] and !empty($results[0][$Model->name])) {
			$url = $Model->brownieCmsConfig['actions']['url_view'];
			foreach ($results as $i => $record) {
				if (!empty($results[$i][$Model->name]['id'])) {
					if (is_array($url)) {
						$url[0] = $results[$i][$Model->name]['id'];
						$url['plugin'] = null;
					} else {
						$url .= '/' . $results[$i][$Model->name]['id'];
					}
					$results[$i][$Model->name]['brw_url_view'] = $url;
				}
			}
		}
		return $results;
	}

	function _camelize($array) {
		foreach($array as $key => $value) {
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
		if (!empty($Model->brownieCmsConfig['images'])) {
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
		if (!empty($Model->brownieCmsConfig['files'])) {
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

}