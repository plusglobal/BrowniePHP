<?php

class CmsBehavior extends ModelBehavior {

	var $cmsConfigDefault = array(
		'names' => array(
			'section' => false,
			'plural' => false,
			'singular' => false,
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
		),

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

		'fields_rename' => array(),

		'images' => array(),

		'files' => array(),

		'default' => array(),

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
	}


	function afterFind($Model, $results, $primary) {
		$results = $this->_addImagePaths($results, $Model);
		$results = $this->_addFilePaths($results, $Model);
		$results = $this->_addUrlView($results, $Model);
		return $results;
	}


	function cmsConfigInit($Model) {

		if (empty($Model->brownieCmsConfig['paginate']['fields'])) {
			$Model->brownieCmsConfig['paginate']['fields'] = $this->listableFields($Model);
		}

		if (empty($Model->brownieCmsConfig['paginate']['order']) and !empty($Model->order)) {
			$Model->brownieCmsConfig['paginate']['order'] = $Model->order;
		}

		if (!empty($Model->brownieCmsConfig)) {
			$config = Set::merge($this->cmsConfigDefault, $Model->brownieCmsConfig);
		} else {
			$config = $this->cmsConfig;
		}
		$config['paginate']['order'] = str_replace('{model}', $Model->alias, $config['paginate']['order']);

		if (empty($Model->order)) {
			$Model->order = $config['paginate']['order'];
		}

		$config['names'] = $this->cmsConfigNames($config['names'], $Model);

		if ($config['images']) {
			$Model->bindModel(array('hasMany' => array('BrwImage' => array(
				'foreignKey' => 'record_id',
				'conditions' => array('BrwImage.model' => $Model->name)
			))));
			foreach($config['images'] as $key => $value) {
				$config['images'][$key] = Set::merge($this->cmsConfigDefaultImage, $value);
			}
		}

		if ($config['files']) {
			$Model->bindModel(array('hasMany' => array('BrwFile' => array(
				'foreignKey' => 'record_id',
				'conditions' => array('BrwFile.model' => $Model->name)
			))));
			foreach($config['files'] as $key => $value) {
				$config['files'][$key] = Set::merge($this->cmsConfigDefaultFile, $value);
			}
		}

		$Model->brownieCmsConfig = $config;
	}


	function listableFields($Model) {
		$fields = array();
		$i = 0;
		$schema = (array)$Model->_schema;
		foreach($schema as $key => $values) {
			if ($this->isListable($values['type'])) {
				$fields[] = $key;
			}
			if ($i++ > 5) {
				return $fields;
			}
		}
		return $fields;
	}


	function isListable($fieldType) {
		$listableTypes = array('integer', 'float', 'string', 'boolean', 'date', 'datetime', 'time', 'timestamp');
		return in_array($fieldType, $listableTypes);
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
		$fieldsConfig = $this->getCmsConfig($Model, 'fields');
		$fieldsNotUsed = array_merge(array('created', 'modified'), $fieldsConfig['no_' . $action], $fieldsConfig['hide']);
		foreach($fieldsNotUsed as $field) {
			if (!empty($schema[$field])) {
				unset($schema[$field]);
			}
		}
		return $schema;
	}


	function _addImagePaths($r, $Model) {
		if (!is_array($r)) {
			return $r;
		}
		foreach ($r as $key => $value) {
			if ($key === 'BrwImage') {
				$thisModel = $Model;
				if(!empty($r[$key][0]['model']) and $r[$key][0]['model'] != $thisModel->alias) {
					$thisModel = ClassRegistry::getObject($r[$key][0]['model']);
				}
				$r[$key] = $this->_addBrwImagePaths($value, $thisModel);
			} else {
				$r[$key] = $this->_addImagePaths($value, $Model);
			}
		}
		return $r;
	}


	function _addFilePaths($r, $Model) {
		if (!is_array($r)) {
			return $r;
		}
		foreach($r as $key => $value) {
			if ($key === 'BrwFile') {
				$thisModel = $Model;
				if(!empty($r[$key][0]['model']) and $r[$key][0]['model'] != $thisModel->alias) {
					$thisModel = ClassRegistry::getObject($r[$key][0]['model']);
				}
				$r[$key] = $this->_addBrwFilePaths($value, $thisModel);
			} else {
				$r[$key] = $this->_addFilePaths($value, $Model);
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
					//$paths['sizes'][] = $paths['sizes'][$size];
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
						$paths['tag'] = '<a title="'.$value['description'].'" href="'.$paths['sizes'][end($sizes)].'" rel="brw_image_'.$value['record_id'].'"><img alt="'.$value['description'].'" src="'.$paths['sizes'][$sizes[0]].'" /></a>';
					} else {
						$paths['tag'] = '<img alt="'.$value['description'].'" src="'.$paths['sizes'][$sizes[0]].'" />';
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
					<a title="'.$value['description'].'" href="'.$completePath.'" class="BrwFile '.$extension.'">
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

}