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
		//pr($Model->brownieCmsConfig);
	}


	function afterFind($Model, $results, $primary) {
		//if ($Model->)
		$results = $this->_addImagePaths($results, $Model);
		$results = $this->_addFilePaths($results, $Model);
		$results = $this->_addUrlView($results, $Model);
		return $results;
	}

	/*
	function beforeSave($Model) {
		pr($Model->data);
		if (!empty($Model->data['BrownieImage'])) {
			$data['BrwImage'] = $Model->data['BrownieImage'];
			$Model->BrwImage->save($data);
		}
	}*/

	function cmsConfigInit($Model) {
		//echo $Model->name;
		//pr($Model->brownieCmsConfig);

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

		//pr($config);
		//pr($this->cmsConfig);
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
		//pr($names);
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
				//echo $key.'<br />';					pr($value);
				$r[$key] = $this->_addBrwImagePaths($value, $Model);
			} else {
				//pr($value); echo '<hr>';
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
				//echo $key.'<br />';					pr($value);
				$r[$key] = $this->_addBrwFilePaths($value, $Model);
			} else {
				//pr($value); echo '<hr>';
				$r[$key] = $this->_addFilePaths($value, $Model);
			}
		}
		return $r;
	}

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
	function _addBrwImagePaths($r, $Model) {
		App::Import('Helper');
		$Helper = new Helper;
		$ret = array();
		foreach ($Model->brownieCmsConfig['images'] as $catCode => $value) {
			$ret[$catCode] = array();
		}
		foreach ($r as $key => $value) {
			$paths = array(
				'path' => $Helper->url('/uploads/' . $value['model'] . '/' . $value['record_id'] . '/' . $value['id'] . $value['extension'])
			);
			if (!empty($Model->brownieCmsConfig['images'][$value['category_code']]['sizes'])) {
				$sizes = $Model->brownieCmsConfig['images'][$value['category_code']]['sizes'];
				foreach($sizes as $size) {
					$paths[$size] =  $this->graphic($value, $size);
				}
				if (!empty($sizes[0])) {
					if ($sizes[0] != end($sizes)) {
						$paths['tag'] = '<a title="'.$value['description'].'" href="'.$paths[end($sizes)].'" rel="brw_image_'.$value['record_id'].'"><img alt="'.$value['description'].'" src="'.$paths[$sizes[0]].'" /></a>';
					} else {
						$paths['tag'] = '<img alt="'.$value['description'].'" src="'.$paths[$sizes[0]].'" />';
					}
				}
				if (empty($value['description'])) {
					$r[$key]['description'] = $value['name'];
				}
			}
			$merged = am($r[$key], $paths);
			if (!empty($Model->brownieCmsConfig['images'][$value['category_code']]['index'])) {
				$ret[$value['category_code']] = $merged;
			} else {
				$ret[$value['category_code']][] = $merged;
			}
		}
		//pr($ret);
		return $ret;

	}

	function graphic($image, $sizes) {
		$filename = $image['id'];
		$id = $image['record_id'];
		$extension = $image['extension'];
		$model = $image['model'];
		$source_file = $filename . $extension;
		$dest_file = $filename . '-' . $sizes . $extension;
		$dir = 'uploads/' . $model . '/' . $id;
		$abs_dir = $dir;
		$abs_source = $abs_dir . '/' . $source_file;
		$abs_dest = $abs_dir . '/' . $dest_file;
		$error_img = '/img/error.gif';

		if (!file_exists($abs_source)) {
			return $error_img;
		}

		if (!file_exists($abs_dest)) {
			if (!copy($abs_source, $abs_dest)) {
				$this->log('Brownie CMS: ' . $abs_source . ' wasn\'t able to be copied to ' . $abs_dest);
				return $error_img;
			}
			if (!chmod($abs_dest, 0777)) {
				$this->log('Brownie CMS: ' . $abs_dest . ' wasn\'t able to chmod');
				return $error_img;
			}

			$r_sizes = explode('x', str_replace('-','x',$sizes));
			if (count($r_sizes) == 2) {
				$resizeMethod = 'resizeCrop';
			} else {
				$r_sizes = explode('_', $sizes);
				if (count($r_sizes) == 2) {
					$resizeMethod = 'resize';
				} else {
					return $error_img;
				}
			}

			if (!ctype_digit($r_sizes[0]) or !ctype_digit($r_sizes[1])) {
				return $error_img;
			}

			App::import('Vendor', 'Brownie.Resizeimage');
			resizeImage($resizeMethod, $dest_file, $dir . '/', false, $r_sizes[0], $r_sizes[1]);
		}

		//return $abs_dest;
		App::Import('Helper');
		$Helper = new Helper;

		return $Helper->url('/' . str_replace('\\', '/', $dir) . '/' . $dest_file);
	}

	function _addBrwFilePaths($r, $Model) {
		App::Import('Helper');
		$Helper = new Helper;
		$ret = array();
		foreach($r as $key => $value) {
			$completePath = $Helper->url('/uploads/' . $Model->name . '/' . $value['record_id'] . '/' . $value['name']);
			$extension = end(explode(".", $value['name']));
			$paths = array(
				'path' => $completePath,
				'tag' => '
					<a title="'.$value['description'].'" href="'.$completePath.'" class="BrwFile '.$extension.'">
						' . ife($value['description'], $value['description'], __d('brownie', 'Download file', true)) . '
					</a>',
			);
			if (empty($value['description'])) {
				$r[$key]['description'] = $value['name'];
			}
			$merged = am($r[$key], $paths);
			if (!empty($Model->brownieCmsConfig['files'][$value['category_code']]['index'])) {
				$ret[$value['category_code']] = $merged;
			} else {
				$ret[$value['category_code']][] = $merged;
			}
		}
		//pr($ret);
		return $ret;

	}


	function _addUrlView($results, $Model) {
		if ($Model->brownieCmsConfig['actions']['url_view'] and !empty($results[0][$Model->name])) {
			App::import('Helper');
			$Helper = new Helper;
			$url = $Model->brownieCmsConfig['actions']['url_view'];
			foreach($results as $i => $record) {
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