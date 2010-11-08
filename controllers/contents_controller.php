<?php
class ContentsController extends BrownieAppController {

	var $name = 'Contents';
	var $helpers = array('Brownie.Fck');
	var $Model;
	var $uses = array('Brownie.Content');


	function beforeFilter() {
		if (!empty($this->params['pass'][0])) {
			$model = $this->params['pass'][0];
		} elseif (!empty($this->data['Content']['model'])) {
			$model = $this->data['Content']['model'];
		}

		if (empty($model) or !$this->Content->modelExists($model)) {
			$this->cakeError('error404');
		}

		if (!$this->_checkPermissions($model, $this->params['action'])) {
			$this->cakeError('error404');
		}

		$this->Model = ClassRegistry::init($model);
		$this->Model->recursive = -1;
		$this->Model->Behaviors->attach('Brownie.Cms');

		$this->Model->brownieCmsConfig['actions'] = array_merge(
			$this->Model->brownieCmsConfig['actions'], $this->arrayPermissions($this->Model->alias)
		);

		parent::beforeFilter();

		$noPermission = (
			$siteModel = Configure::read('multiSitesModel')
			and $this->Model->name != $siteModel
			and $this->Model->name != 'BrwUser'
			and !Configure::read('Auth.BrwUser.root')
			and empty($this->Model->belongsTo[Configure::read('multiSitesModel')])
		);
		if ($noPermission) {
			$this->cakeError('error404');
		}

		if (!Configure::read('Auth.BrwUser.root')) {
			$this->Model->brownieCmsConfig['actions'] = Set::merge(
				$this->Model->brownieCmsConfig['actions'],
				$this->Model->brownieCmsConfig['actions_no_root']
			);
			$this->Model->brownieCmsConfig['fields'] = Set::merge(
				$this->Model->brownieCmsConfig['fields'],
				$this->Model->brownieCmsConfig['fields_no_root']
			);
		}
	}

	function beforeRender() {
		$brwConfig = $this->Model->brownieCmsConfig;
		$schema = $this->Model->_schema;
		$model = $this->Model->alias;
		$this->set(compact('model', 'schema', 'brwConfig'));
		//$this->log($this->Model);
		parent::beforeRender();
	}

	function index() {
		$filterSites = (
			$siteModel = Configure::read('multiSitesModel')
			and !Configure::read('Auth.BrwUser.root')
			and $this->Model->name == $siteModel
		);
		if ($filterSites) {
			$this->Model->brownieCmsConfig['paginate']['conditions'][] = array(
				$this->Model->name . '.brw_user_id' => Configure::read('Auth.BrwUser.id')
			);
			$this->Model->brownieCmsConfig['actions']['add'] = false;
			$this->Model->brownieCmsConfig['actions']['delete'] = false;
		}

		$this->paginate = $this->Model->brownieCmsConfig['paginate'];
		if ($this->Content->isTree($this->Model)) {
			$this->paginate['order'] = 'lft';
		}
		$records = $this->paginate($this->Model);
		$isUniqueRecord = (
			count($records) == 1
			and !$this->Model->brownieCmsConfig['actions']['add']
			and !$this->Model->brownieCmsConfig['actions']['delete']
		);
		if ($isUniqueRecord) {
			$this->redirect(array(
				'controller' => 'contents', 'action' => 'view',
				$this->Model->alias, $records[0][$this->Model->alias]['id']
			));
		}
		$this->set('records', $this->Content->formatForView($records, $this->Model));
		$this->set('foreignKeyValue', '');
		$this->set('permissions', array($this->Model->alias => $this->Model->brownieCmsConfig['actions']));
	}


	function view($model, $id) {
		$restricted = (
			$siteModel = Configure::read('multiSitesModel')
			and !Configure::read('Auth.BrwUser.root')
			and $this->Model->name == $siteModel

		);
		if ($restricted) {
			if($id != Configure::read('currentSite.id')) {
				$this->cakeError('error404');
			} else {
				$this->Model->brownieCmsConfig['actions']['add'] = false;
				$this->Model->brownieCmsConfig['actions']['delete'] = false;
			}
		}

		$contain = array();

		if ($this->Model->brownieCmsConfig['images']) {
			$contain['BrwImage'] = array('order' => 'BrwImage.category_code, BrwImage.modified asc');
		}

		if ($this->Model->brownieCmsConfig['files']) {
			$contain['BrwFile'] = array('order' => 'BrwFile.category_code, BrwFile.modified asc');
		}

		$this->Model->Behaviors->attach('Containable');
		$record = $this->Model->find( 'first', array(
			'conditions' => array($this->Model->name . '.id' => $id),
			'contain' => $contain
		));


		if (empty($record)) {
			$this->redirect(array('action' => 'index', $model));
		}

		$this->set('record', $this->Content->formatForView($record, $this->Model));

		if(!$restricted){
			$neighbors = $this->Model->find('neighbors', array('field' => 'id', 'value' => $id));
		} else {
			$neighbors = array();
		}
		$this->set('neighbors', $neighbors);

		$permissions[$model] = $this->arrayPermissions($model);

		$assocs = array_merge($this->Model->hasMany, $this->Model->hasOne);
		$assoc_models = $pages = $names = array();
		if (!empty($this->Model->hasMany) and $this->Model->brownieCmsConfig['show_children']){
			foreach ($assocs as $key_model => $related_model) {
				if (!in_array($key_model, $this->Model->brownieCmsConfig['hide_children'])) {
					$AssocModel = $this->Model->$key_model;
					$AssocModel->Behaviors->attach('Brownie.Cms');
					//$this->paginate[$AssocModel->name] = Set::merge($related_model, $AssocModel->brownieCmsConfig['paginate']);
					$this->paginate[$AssocModel->name] = $AssocModel->brownieCmsConfig['paginate'];
					if ($this->_checkPermissions($key_model)) {
						$assoc_models[] = array(
							'brwConfig' => $AssocModel->brownieCmsConfig,
							'model' => $key_model,
							'records' => $this->Content->formatForView($this->paginate($AssocModel, array($related_model['foreignKey'] => $id)), $AssocModel),
							'foreignKeyValue' => $related_model['foreignKey'] . ':' . $id,
							'schema' => $this->Model->$key_model->_schema,
						);
						$permissions[$key_model] = $this->arrayPermissions($key_model);
					}
				 }
			}
		}
		$this->set('assoc_models', $assoc_models);
		$this->set('permissions', $permissions);
	}


	function edit($model, $id = null) {

		$restricted = (
			$siteModel = Configure::read('multiSitesModel')
			and !Configure::read('Auth.BrwUser.root')
			and $this->Model->name == $siteModel

		);
		if ($restricted) {
			if($id != Configure::read('currentSite.id')) {
				$this->cakeError('error404');
			}
		}


		if (!empty($id)) {
			if (!$this->Model->read(array('id'), $id)) {
				$this->cakeError('error404');
			}
			$action = 'edit';
		} else {
			$action = 'add';
		}

		if (!$this->_checkPermissions($model, $action)) {
			$this->cakeError('error404');
		}


		if (!empty($this->data)) {

			if (!empty($this->data[$this->Model->alias]['id']) and $this->data[$this->Model->alias]['id'] != $id) {
				$this->cakeError('error404');
			}

			$this->Content->addValidationsRules($this->Model, $id);
			$this->data = $this->Content->brownieBeforeSave($this->data, $this->Model);

			$this->Model->create();
			if ($this->Model->saveAll($this->data, array('validate' => 'first', 'model' => $this->Model->name))) {
				$this->Session->setFlash(__d('brownie', 'The information has been saved', true), 'flash_success');
				if (!empty($this->data['Content']['after_save'])) {
					switch ($this->data['Content']['after_save']) {
						case 'add':
							$this->redirect(array('action' => 'edit', $this->Model->name, 'after_save' => 'add'));
						break;
						case 'view':
							$this->redirect(array('action' => 'view', $this->Model->name, $this->Model->id));
						break;
						case 'home':
							$this->redirect(array('controller' => 'brownie', 'action' => 'index'));
						break;
						case 'index':
							$this->redirect(array('action' => 'index', $this->Model->name));
						break;
						case 'edit':
							$this->redirect(array('action'=>'edit', $this->Model->name, $this->Model->id, 'after_save' => 'edit'));
						break;
					}
				}
			} else {
				$msg = __d('brownie', 'The information could not be saved. Please, check the error messages.', true);
				$this->Session->setFlash($msg, 'flash_error');
			}
		}

		$contain = $related = array();
		if (!empty($this->Model->belongsTo)) {
			foreach($this->Model->belongsTo as $key_model => $related_model){
				$AssocModel = $this->Model->$key_model;
				if(!in_array($AssocModel, array('BrwImage', 'BrwFile'))) {
					if ($this->Content->isTree($AssocModel)){
						$relatedData = $AssocModel->generatetreelist();
					} else {
						$relatedData = $AssocModel->find('list', $related_model);
					}
					$related['belongsTo'][$related_model['foreignKey']] = $relatedData;
				}
			}
			$contain[] = $key_model;
		}

		if (!empty($this->Model->hasAndBelongsToMany)) {
			foreach ($this->Model->hasAndBelongsToMany as $key_model => $related_model) {
				$related['hasAndBelongsToMany'][$key_model] = $this->Model->$key_model->find('list', $related_model);
				$contain[] = $key_model;
			}
		}

		if ($this->Content->isTree($this->Model)) {
			$related['tree']['parent_id'] = $this->Model->generatetreelist();
		}
		$this->set('related', $related);

		if (empty($this->data)) {
			if ($id) {
				$this->Model->Behaviors->attach('Containable');
				$this->data = $this->Model->find('first', array(
					'conditions' => array($this->Model->name . '.id' => $id),
					'contain' => $contain, 'callbacks' => false,
				));
			} else {
				$this->data = $this->Content->defaults($this->Model);
			}
		}

		if (method_exists($this->Model, 'brwBeforeEdit')) {
			$this->data = $this->Model->brwBeforeEdit($this->data);
			$this->set('schema', $this->Model->_schema);
		}

		if ($id) {
			$fields = $this->Content->fieldsEdit($this->Model);
		} else {
			$fields = $this->Content->fieldsAdd($this->Model);
		}
		$this->set('fields', $fields);
		$this->set('fckFields', $this->Content->fckFields($this->Model));
	}


	function delete($model, $id) {
		if (!$this->Model->read(null, $id)) {
			$this->cakeError('error404');
		}

		if ($this->Content->delete($this->Model, $id)) {
			$this->Session->setFlash(__d('brownie', 'Successful delete', true), 'flash_success');
		} else {
			$this->Session->setFlash(__d('brownie', 'Unable to delete', true), 'flash_error');
		}

		if (env('HTTP_REFERER')) {
			$this->redirect(env('HTTP_REFERER'));
		} else {
			$this->redirect(array('action'=>'index'));
		}
	}

	/*
	function pageForPagination($model) {
		$page = 1;
		$sameModel = isset($this->params['named']['model']) && $this->params['named']['model'] == $model;
		$pageInUrl = isset($this->params['named']['page']);
		if ($sameModel && $pageInUrl) {
			$page = $this->params['named']['page'];
			unset($this->params['named']['page']);
		}

		$this->passedArgs['page'] = $page;
		return $page;
	}
	*/

	function edit_image($model = null, $recordId = null, $categoryCode = null, $imageId = null) {
		if (!empty($this->data)) {
			if (!$categoryCode){
				$categoryCode = $this->data['BrwImage']['category_code'];
			}
			if (!$recordId){
				$recordId = $this->data['BrwImage']['record_id'];
			}
			if (!$imageId){
				$imageId = $this->data['BrwImage']['id'];
			}

			if ($this->Model->BrwImage->save($this->data)) {
				if ($imageId){
					$this->Session->setFlash(__d('brownie', 'The image was Successfully edited', true), 'flash_success');
				} else {
					$this->Session->setFlash(__d('brownie', 'The image was Successfully added', true), 'flash_success');
				}
				$this->redirect(array('controller' => 'contents', 'action' => 'view', $this->Model->name, $recordId));
			}
		}

		if ($imageId and empty($this->data)) {
			$this->data = $this->Model->BrwImage->find('first',
				array('conditions' => array('BrwImage.id' => $imageId))
			);
		}

		$this->set(compact('categoryCode', 'recordId', 'imageId'));

	}


	function add_images($model, $recordId, $categoryCode) {
		if (!empty($this->data)) {
			$saved = 0;
			foreach ($this->data['BrwImage'] as $image) {
				if ($this->Model->BrwImage->save($image)) {
					$saved++;
				}
			}
			if ($saved) {
				$this->Session->setFlash(sprintf(__d('brownie', '%d images successfully added', true), $saved), 'flash_success');
				$this->redirect(array('controller' => 'contents', 'action' => 'view', $this->Model->name, $recordId));
			} else {
				$this->Session->setFlash(__d('brownie', 'None images uploaded. Please try again.', true), 'flash_notice');
			}
		}
		$this->set(compact('categoryCode', 'recordId', 'imageId'));
	}


	function edit_file($model = null, $recordId = null, $categoryCode = null, $fileId = null) {

		if (!empty($this->data)) {
			if (!$categoryCode){
				$categoryCode = $this->data['BrwFile']['category_code'];
			}
			if (!$recordId){
				$recordId = $this->data['BrwFile']['record_id'];
			}
			if (!$fileId){
				$fileId = $this->data['BrwFile']['id'];
			}

			if ($this->Model->BrwFile->save($this->data)) {
				if ($fileId){
					$this->Session->setFlash(__d('brownie', 'The file was Successfully edited', true), 'flash_success');
				} else {
					$this->Session->setFlash(__d('brownie', 'The file was Successfully added', true), 'flash_success');
				}
				$this->redirect(array('controller' => 'contents', 'action' => 'view', $this->Model->name, $recordId));
			}
		}

		if ($fileId and empty($this->data)) {
			$this->data = $this->Model->BrwFile->find('first',
				array('conditions' => array('BrwFile.id' => $fileId))
			);
		}

		$this->set(compact('categoryCode', 'recordId', 'fileId'));

	}


	function import($model) {
		if (!$this->Model->brownieCmsConfig['actions']['import']) {
			$this->redirect(array('controller' => 'contents', 'action' => 'index', $model));
		}

		if (!empty($this->data)) {
			$result = $this->Model->brwImport($this->data);
			if (!$result) {
				$this->Session->setFlash(__d('brownie', 'The import could not be done. Please try again', true), 'flash_error');
			} else {
				$this->Session->setFlash($result, 'flash_success');
				$this->redirect(array('controller' => 'contents', 'action' => 'index', $model));
			}
		}

		if (Configure::read('debug') and !method_exists($this->Model, 'brwImport')) {
			$msg = sprintf(__d('brownie', 'Warning: %s::brwImport() must be defined', true), $model);
			$this->Session->setFlash($msg, 'flash_error');
		}
	}

	function js_edit($model) {
		$this->header('Content-type: text/javascript');
		$this->layout = 'ajax';
	}


}