<?php

class ContentsController extends BrownieAppController {

	var $name = 'Contents';
	var $helpers = array('Brownie.Fck');
	var $Model;
	var $uses = array('Brownie.Content');


	function beforeFilter() {

		parent::beforeFilter();

		if (!empty($this->params['pass'][0])) {
			$model = $this->params['pass'][0];
		} elseif (!empty($this->data['Content']['model'])) {
			$model = $this->data['Content']['model'];
		}

		if (empty($model) or !$this->Content->modelExists($model)) {
			$this->cakeError('error404');
		}

		$this->Model = ClassRegistry::init($model);
		$this->Model->recursive = -1;
		$this->Model->Behaviors->attach('Brownie.Cms');


		if (!$this->_checkPermissions($model, $this->params['action'])) {
			$this->cakeError('error404');
		}


		$this->Model->brownieCmsConfig['actions'] = array_merge(
			$this->Model->brownieCmsConfig['actions'],
			$this->arrayPermissions($this->Model->alias)
		);

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
		$schema = $this->Content->schemaForView($this->Model);
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
			$this->set('isTree', true);
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
		if (method_exists($this->Model, 'brwAfterFind')) {
			$records = $this->Model->brwAfterFind($records);
		}

		$this->set('records', $this->_formatForView($records, $this->Model));
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
		$record = $this->Model->find('all', array(
			'conditions' => array($this->Model->name . '.id' => $id),
			'contain' => $contain
		));

		if (empty($record)) {
			$this->redirect(array('action' => 'index', $model));
		}

		if (method_exists($this->Model, 'brwAfterFind')) {
			$record = $this->Model->brwAfterFind($record);
		}
		$record = $record[0];

		//ejecutar brwAfterFind en los modelos relacionados que estan en $contain

		if (!$restricted) {
			if (is_array($this->Model->order)) {
				list($keyOrder) = each($this->Model->order);
				$keyOrder = str_replace($this->Model->alias . '.', '', $keyOrder);
				$neighbors = $this->Model->find('neighbors', array('field' => $keyOrder, 'value' => $record[$model][$keyOrder]));
				if (
					!empty($this->Model->brownieCmsConfig['sortable']['direction'])
					and $this->Model->brownieCmsConfig['sortable']['direction'] == 'desc'
				) {
					$tmp = $neighbors['prev'];
					$neighbors['prev'] = $neighbors['next'];
					$neighbors['next'] = $tmp;
				}
			} else {
				$neighbors = $this->Model->find('neighbors', array('field' => 'id', 'value' => $id));
			}
		} else {
			$neighbors = array();
		}

		$permissions[$model] = $this->arrayPermissions($model);

		$assocs = array_merge($this->Model->hasMany, $this->Model->hasOne);
		$assoc_models = array();
		if (!empty($this->Model->hasMany) and $this->Model->brownieCmsConfig['show_children']){
			foreach ($assocs as $key_model => $related_model) {
				if (!in_array($key_model, $this->Model->brownieCmsConfig['hide_children'])) {
					$AssocModel = $this->Model->$key_model;
					$AssocModel->Behaviors->attach('Brownie.Cms');
					if ($this->_checkPermissions($key_model)) {
						if ($indx = array_search($related_model['foreignKey'], $AssocModel->brownieCmsConfig['paginate']['fields'])) {
							unset($AssocModel->brownieCmsConfig['paginate']['fields'][$indx]);
						}
						$this->paginate[$AssocModel->name] = $AssocModel->brownieCmsConfig['paginate'];
						$assoc_models[] = array(
							'brwConfig' => $AssocModel->brownieCmsConfig,
							'model' => $key_model,
							'records' => $this->_formatForView($this->paginate($AssocModel, array($related_model['foreignKey'] => $id)), $AssocModel),
							'foreignKeyValue' => $related_model['foreignKey'] . ':' . $id,
							'schema' => $this->Content->schemaForView($this->Model->$key_model),
						);
						$permissions[$key_model] = $this->arrayPermissions($key_model);
					}
				}
			}
		}

		$this->set('record', $this->_formatForView($record, $this->Model));
		$this->set('neighbors', $neighbors);
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


		if ($id) {
			$fields = $this->Content->fieldsEdit($this->Model);
		} else {
			$fields = $this->Content->fieldsAdd($this->Model);
		}


		if (!empty($this->data)) {

			if (!empty($this->data[$this->Model->alias]['id']) and $this->data[$this->Model->alias]['id'] != $id) {
				$this->cakeError('error404');
			}

			$this->Content->addValidationsRules($this->Model, $id);
			$this->data = $this->Content->brownieBeforeSave($this->data, $this->Model);

			$fieldList = array_merge(array_keys($fields), array('name', 'model', 'category_code', 'description', 'record_id'));
			if (Configure::read('multiSitesModel')) {
				$fieldList[] = 'site_id';
			}
			if ($this->Model->brownieCmsConfig['sortable']) {
				$fieldList[] = $this->Model->brownieCmsConfig['sortable']['field'];
			}
			if ($this->Model->saveAll($this->data, array('fieldList' => $fieldList, 'validate' => 'first'))) {
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
							if ($parent = $this->Model->brownieCmsConfig['parent']) {
								$foreignKey = $this->Model->belongsTo[$parent]['foreignKey'];
								if (!empty($this->data[$this->Model->alias][$foreignKey])) {
									$this->redirect(array('action' => 'view', $parent, $this->data[$this->Model->alias][$foreignKey]));
								}
							}
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
			foreach($this->Model->belongsTo as $key_model => $related_model) {
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
		}

		if (!empty($this->Model->hasAndBelongsToMany)) {
			foreach ($this->Model->hasAndBelongsToMany as $key_model => $related_model) {
				$related['hasAndBelongsToMany'][$key_model] = $this->Model->$key_model->find('list', $related_model);
				if (!in_array($key_model, $contain)) {
					$contain[] = $key_model;
				}
			}
		}

		if ($this->Content->isTree($this->Model)) {
			$related['tree']['parent_id'] = $this->Model->generatetreelist();
		}
		$this->set('related', $related);

		if (empty($this->data)) {
			if ($id) {
				$this->Model->Behaviors->attach('Containable');
				$this->Model->Behaviors->detach('Brownie.Cms');
				$this->data = $this->Model->find('first', array(
					'conditions' => array($this->Model->name . '.id' => $id),
					'contain' => $contain,
				));
			} else {
				$this->data = $this->Content->defaults($this->Model);
			}
		}

		if (method_exists($this->Model, 'brwBeforeEdit')) {
			$this->data = $this->Model->brwBeforeEdit($this->data);
			$this->set('schema', $this->Content->schemaForView($this->Model));
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
	function _add_images($model, $recordId, $categoryCode) {
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
	}*/


	function edit_upload($model, $uploadType, $recordId, $categoryCode, $uploadId = null) {
		if (
			!in_array($uploadType, array('BrwFile', 'BrwImage'))
			or empty($this->Model->brownieCmsConfig[($uploadType == 'BrwFile') ? 'files' : 'images'][$categoryCode])
		) {
			$this->cakeError('error404');
		}

		if (!empty($this->data)) {
			$cantSaved = 0;
			foreach ($this->data[$uploadType] as $data) {
				if ($this->Model->{$uploadType}->save($data)) {
					$cantSaved++;
				}
			}
			if ($cantSaved) {
				$msg = ($uploadType == 'BrwFile') ?
					sprintf(__d('brownie', '%s files saved', true), $cantSaved):
					sprintf(__d('brownie', '%s images saved', true), $cantSaved);
				$msgType = 'flash_success';
			} else {
				$msg = ($uploadType == 'BrwFile') ?
					sprintf(__d('brownie', 'No files saved', true), $cantSaved):
					sprintf(__d('brownie', 'No images saved', true), $cantSaved);
				$msgType = 'flash_notice';
			}
			$this->Session->setFlash($msg, $msgType);

			$this->redirect(array(
				'plugin' => 'brownie', 'controller' => 'contents',
				'action' => 'view', $model, $recordId
			));

		}
		if (empty($this->data) and $uploadId) {
			$data = $this->Model->{$uploadType}->findById($uploadId);
			$this->data[$uploadType][0] = $data[$uploadType];
			$max = 1;
		} else {
			$uploadKey = ($uploadType == 'BrwFile') ? 'files' : 'images';
			$max = ($this->Model->brownieCmsConfig[$uploadKey][$categoryCode]['index'])? 1:10;
		}
		$this->set(compact('model', 'uploadType', 'recordId', 'categoryCode', 'uploadId', 'max'));
	}


	function delete_upload($model, $uploadType, $recordId) {
		if (!in_array($uploadType, array('BrwFile', 'BrwImage'))) {
			$this->cakeError('error404');
		}
		if ($this->Model->{$uploadType}->delete($recordId)) {
			$msg = ($uploadType == 'BrwFile') ?
				__d('brownie', 'The file was deleted', true) :
				__d('brownie', 'The image was deleted', true);
			$this->Session->setFlash($msg, 'flash_success');
		} else {
			$msg = ($uploadType == 'BrwFile') ?
				__d('brownie', 'The file could not be deleted', true) :
				__d('brownie', 'The image could not be deleted', true);
			$this->Session->setFlash($msg, 'flash_error');
		}

		$redirecTo = env('HTTP_REFERER');
		if (!$redirecTo) {
			$redirecTo = array('controller' => 'brownie', 'action' => 'index', 'plugin' => 'brownie');
		}
		$this->redirect($redirecTo);
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


	function reorder($model, $direction, $id) {
		if (
			!in_array($direction, array('up', 'down'))
			and !$this->Content->isTree($this->Model)
			and empty($this->Model->brownieCmsConfig['sortable'])
		) {
			$this->CakeError('error404');
		}

		if ($this->Content->reorder($this->Model, $direction, $id)) {
			$this->Session->setFlash(__d('brownie', 'Successfully reordered', true), 'flash_success');
		} else {
			$this->Session->setFlash(__d('brownie', 'Failed to reorder', true), 'flash_error');
		}

		if ($ref = env('HTTP_REFERER')) {
			$this->redirect($ref);
		} else {
			$this->redirect(array('controller' => 'contents', 'action' => 'index', $model));
		}/**/
	}


	function _formatForView($data, $Model) {
		$out = array();
		if (!empty($data[$Model->name])) {
			$out = $this->_formatSingleForView($data, $Model);
		} else {
			if ($this->Content->isTree($Model)) {
				$data = $this->_formatTree($data, $Model);
			}
			foreach ($data as $dataset) {
				$out[] = $this->_formatSingleForView($dataset, $Model);
			}
		}
		return $out;
	}

	function _formatSingleForView($data, $Model, $inView = false) {
		$fieldsConfig = $Model->brownieCmsConfig['fields'];
		$fieldsHide = $fieldsConfig['hide'];
		$foreignKeys = $this->Content->getForeignKeys($Model);
		$permissions = $this->arrayPermissions($Model->name);
		$retData = $data;
		foreach ($retData[$Model->name] as $key => $value) {
			if (in_array($key, $fieldsHide)) {
				unset($retData[$Model->name][$key]);
			} elseif (in_array($key, $fieldsConfig['code'])) {
				$retData[$Model->name][$key] = '<pre>' . htmlspecialchars($retData[$Model->name][$key]) . '</pre>';
			} elseif (isset($foreignKeys[$key])) {
				$read = $Model->{$foreignKeys[$key]}->findById($retData[$Model->name][$key]);
				$retData[$Model->name][$key] = $read[$foreignKeys[$key]][$Model->{$foreignKeys[$key]}->displayField];
				if ($this->_checkPermissions($Model->{$foreignKeys[$key]}->name, 'view', $read[$foreignKeys[$key]]['id'])) {
					$relatedURL = Router::url(array(
						'controller' => 'contents', 'action' => 'view', 'plugin' => 'brownie',
						$foreignKeys[$key], $read[$foreignKeys[$key]]['id']
					));
					$retData[$Model->name][$key] = '<a href="'.$relatedURL.'">' . $retData[$Model->name][$key] . '</a>';
				}

			} elseif (!empty($Model->_schema[$key]['type'])) {
				switch($Model->_schema[$key]['type']) {
					case 'boolean':
						$retData[$Model->name][$key] = $retData[$Model->name][$key]? __d('brownie', 'Yes', true): __d('brownie', 'No', true);
					break;
					case 'datetime':
						$retData[$Model->name][$key] = $this->_formatDateTime($retData[$Model->name][$key]);
					break;
					case 'date':
						$retData[$Model->name][$key] = $this->_formatDate($retData[$Model->name][$key]);
					break;
				}
			}
		}
		$retData[$Model->name]['brw_actions'] = $this->Content->actions($Model, $data, $permissions);
		return $retData;
	}


	function _formatTree($data, $Model) {
		$treeList = $Model->generateTreeList(null, null, null, '<span class="tree_prepend"></span>');
		foreach ($data as $i => $value) {
			$displayValue = $data[$i][$Model->alias][$Model->displayField];
			$data[$i][$Model->alias][$Model->displayField] =
				str_replace($displayValue, '', $treeList[$value[$Model->alias]['id']])
				. '<span class="tree_arrow"></span>' . $displayValue;
		}
		return $data;
	}

	function _formatDate($date) {
		if (empty($date) or $date == '0000-00-00') {
			return __d('brownie', 'Date not set', true);
		} else {
			App::Import('Helper', 'Time');
			$time = new TimeHelper();
			return $time->format('d/m/Y', $date, __d('brownie', 'Invalid date', true));
		}
	}

	function _formatDateTime($datetime) {
		if (empty($datetime) or $datetime == '0000-00-00 00:00:00') {
			return __d('brownie', 'Datetime not set', true);
		} else {
			App::Import('Helper', 'Time');
			$time = new TimeHelper();
			return $time->format('d/m/Y H:i:s', $datetime, __d('brownie', 'Invalid datetime', true));
		}
	}


}