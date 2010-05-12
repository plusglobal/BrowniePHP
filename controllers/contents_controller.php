<?php
class ContentsController extends BrownieAppController {

	var $name = 'Contents';
	var $Model;
	var $helpers = array('Brownie.Fck', 'Javascript', 'Html');

	function beforeFilter() {
		if(!empty($this->params['pass'][0])) {
			$model = $this->params['pass'][0];
		} elseif(!empty($this->data['Content']['model'])) {
			$model = $this->data['Content']['model'];
		}

		if(empty($model) or !$this->Content->modelExists($model)) {
			$this->cakeError('error404');
		}

		if(!$this->_checkPermissions($model, $this->params['action'])) {
			$this->Session->setFlash(__d('brownie', 'You are not allowed to perform this action', true));
			$this->redirect(array('controller' => 'brownie', 'action' => 'index'));
		}

		$this->Model = ClassRegistry::init($model);
		$this->Model->recursive = -1;
		$this->Model->Behaviors->attach('Brownie.Cms');

		$brwConfig = $this->Content->getCmsConfig($this->Model);
		$schema = $this->Model->_schema;
		$this->set(compact('model', 'schema', 'brwConfig'));

		parent::beforeFilter();
	}


	function index() {
		if ($this->Content->isTree($this->Model)) {
			$this->helpers[] = 'tree';
			$this->set('records', $this->Model->find('threaded'));
			$this->set('isTree', true);
			$this->set('displayField', $this->Model->displayField);
		} else {
			$this->paginate = $this->Content->getCmsConfig($this->Model, 'paginate');
			$this->set('records', $this->Content->formatForView($this->paginate($this->Model), $this->Model));
		}
		$this->set('foreignKeyValue', '');
		$permissions[$this->Model->name] = $this->arrayPermissions($this->Model->name);
		$this->set('permissions', $permissions);
	}


	function view($model, $id = null) {

		$contain = array();

		if($this->Content->getCmsConfig($this->Model, 'images')) {
			$contain['BrwImage'] = array('order' => 'BrwImage.category_code, BrwImage.modified asc');
		}

		if($this->Content->getCmsConfig($this->Model, 'files')) {
			$contain['BrwFile'] = array('order' => 'BrwFile.category_code, BrwFile.modified asc');
		}

		$this->Model->Behaviors->attach('Containable');
		$record = array_shift(
			$this->Model->find(
				'all',
				array(
					'conditions' => array($this->Model->name . '.id' => $id),
					'contain' => $contain
				)
			)
		);


		if(!$record){
			$this->redirect(array('action' => 'index', $model));
		}

		$this->set('record', $this->Content->formatForView($record, $this->Model));
		$this->set('neighbors', $this->Model->find('neighbors', array('field' => 'id', 'value' => $id)));

		$permissions[$model] = $this->arrayPermissions($model);

		$assoc_models = $pages = $names = array();
		if(!empty($this->Model->hasMany)){
			foreach($this->Model->hasMany as $key_model => $related_model){
				if(!in_array($key_model, array('BrwImage', 'BrwFile'))){
					$AssocModel = $this->Model->$key_model;
					$AssocModel->Behaviors->attach('Brownie.Cms');
					//ClassRegistry::init($related_model['className']);
					//$AssocModel->recursive = -1;
					//$this->Content->cmsConfigInit($AssocModel);
					$this->paginate[$AssocModel->name] = $this->Content->getCmsConfig($AssocModel, 'paginate');
					//$paginator->__defaultModel = $AssocModel->name;
					//$page = $this->pageForPagination($AssocModel->name);
					/*
					$this->paginate[$AssocModel->name]['page'] = $page;
					$this->paginate['page'] = $page;
					$pages[$AssocModel->name] = $page;
					*/
					//$this->paginate['conditions'] = array($related_model['foreignKey'] => $id);

					if($this->_checkPermissions($key_model)){
						$assoc_models[] = array(
							'brwConfig' => $this->Content->getCmsConfig($AssocModel),
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
		//pr($assoc_models);
		//$this->set('pages', $pages);

		$this->set('assoc_models', $assoc_models);
		$this->set('permissions', $permissions);

	}


	function edit($model = null, $id = null) {

		if (!empty($this->data)) {

			$this->Content->addValidationsRules($this->Model, $id);
			$this->data = $this->Content->brownieBeforeSave($this->data, $this->Model);
			//pr($this->Model->hasMany);

			//pr($this->Model->{'Brownie.BrwImage'}->validationErrors);
			if($this->Model->saveAll($this->data, array('validate' => 'first', 'model' => $this->Model->name))) {
				$this->Session->setFlash(__d('brownie', 'The information has been saved', true));
				if(!empty($this->data['Content']['backto'])){
					$this->redirect($this->data['Content']['backto']);
				} else {
					$this->redirect(array('action'=>'view', $this->Model->name, $this->Model->id));
				}
			} else {
				$this->Session->setFlash(__d('brownie', 'The information could not be saved. Please, check the error messages.', true));
			}
		}
		if($id) {
			$fields = $this->Content->fieldsEdit($this->Model);
		} else {
			$fields = $this->Content->fieldsAdd($this->Model);
		}
		$this->set('fields', $fields);


		$related = array();
		$contain = array();
		if(!empty($this->Model->belongsTo)){
			foreach($this->Model->belongsTo as $key_model => $related_model){
				$AssocModel = $this->Model->$key_model;
				if($this->Content->isTree($AssocModel)){
					$relatedData = $AssocModel->generatetreelist();
				} else {
					$relatedData = $AssocModel->find('list');
				}
				$related['belongsTo'][$related_model['foreignKey']] = $relatedData;
			}
			$contain[] = $key_model;
		}

		if(!empty($this->Model->hasAndBelongsToMany)){
			foreach($this->Model->hasAndBelongsToMany as $key_model => $related_model){
				$related['hasAndBelongsToMany'][$key_model] = $this->Model->$key_model->find('list');
				$contain[] = $key_model;
			}
		}

		if($this->Content->isTree($this->Model)){
			$related['tree']['parent_id'] = $this->Model->generatetreelist();
		}
		$this->set('related', $related);

		if($this->Content->getCmsConfig($this->Model, 'images')){
			$contain[] = 'BrwImage';
		}

		if($this->Content->getCmsConfig($this->Model, 'files')){
			$contain[] = 'BrwFile';
		}


		if(empty($this->data)){
			if ($id) {
				$this->Model->Behaviors->attach('Containable');
				$data = $this->Model->find(
					'all',
					array(
						'conditions' => array($this->Model->name . '.id' => $id),
						'contain' => $contain
					)
				);

				$this->data = array_shift($data);
			} else {
				$this->data = array($this->Model->name => $this->Model->brownieCmsConfig['default']);
			}
		}
		$this->set('fckFields', $this->Content->fckFields($this->Model));

	}


	function delete($model, $id = null) {
		if (!$id) {
			$this->Session->setFlash(__d('brownie', 'Invalid identifier', true));
			$this->redirect(array('action' => 'index'));
		} elseif ($this->Model->delete($id)) {
			$this->Session->setFlash(__d('brownie', 'Successful delete', true));
		} else {
			$this->Session->setFlash(__d('brownie', 'Unable to delete', true));
		}

		if(env('HTTP_REFERER')) {
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
			//pr($this->data);
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
				//pr($ret);
				if ($imageId){
					$this->Session->setFlash(__d('brownie', 'The image was Successfully edited', true));
				} else {
					$this->Session->setFlash(__d('brownie', 'The image was Successfully added', true));
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
				$this->Session->setFlash(sprintf(__d('brownie', '%d images successfully added', true), $saved));
				$this->redirect(array('controller' => 'contents', 'action' => 'view', $this->Model->name, $recordId));
			} else {
				$this->Session->setFlash(__d('brownie', 'None images uploaded. Please try again.', true), $saved);
			}

		}
		$this->set(compact('categoryCode', 'recordId', 'imageId'));
	}

	function edit_file($model = null, $recordId = null, $categoryCode = null, $fileId = null) {
		if (!empty($this->data)) {
			//pr($this->data);
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
				//pr($ret);
				if($fileId){
					$this->Session->setFlash(__d('brownie', 'The file was Successfully edited', true));
				} else {
					$this->Session->setFlash(__d('brownie', 'The file was Successfully added', true));
				}
				$this->redirect(array('controller' => 'contents', 'action' => 'view', $this->Model->name, $recordId));
			}
		}

		if($fileId and empty($this->data)) {
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
				$this->Session->setFlash(__d('brownie', 'The import could not be done. Please try again', true));
			} else {
				$this->Session->setFlash($result);
				$this->redirect(array('controller' => 'contents', 'action' => 'index', $model));
			}
		}

		if (Configure::read('debug') and !method_exists($this->Model, 'brwImport')) {
			$this->Session->setFlash(sprintf(__d('brownie', 'Warning: %s::brwImport() must be defined', true), $model));
		}
	}


}
?>