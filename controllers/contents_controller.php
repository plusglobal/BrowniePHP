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


		$this->Model->brwConfig['actions'] = array_merge(
			$this->Model->brwConfig['actions'],
			$this->arrayPermissions($this->Model->alias)
		);

		if (!Configure::read('Auth.BrwUser.root')) {
			$this->Model->brwConfig['actions'] = Set::merge(
				$this->Model->brwConfig['actions'],
				$this->Model->brwConfig['actions_no_root']
			);
			$this->Model->brwConfig['fields'] = Set::merge(
				$this->Model->brwConfig['fields'],
				$this->Model->brwConfig['fields_no_root']
			);
		}
	}

	function beforeRender() {
		$brwConfig = $this->Model->brwConfig;
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
			$this->Model->brwConfig['paginate']['conditions'][] = array(
				$this->Model->name . '.brw_user_id' => Configure::read('Auth.BrwUser.id')
			);
			$this->Model->brwConfig['actions']['add'] = false;
			$this->Model->brwConfig['actions']['delete'] = false;
		}

		$this->paginate = $this->Model->brwConfig['paginate'];
		if ($this->Content->isTree($this->Model)) {
			$this->set('isTree', true);
			$this->paginate['order'] = 'lft';
		}
		$filters = $this->_filterConditions($this->Model);
		$this->paginate['conditions'] = $filters;


		$records = $this->paginate($this->Model);
		$isUniqueRecord = (
			count($records) == 1
			and !$this->Model->brwConfig['actions']['add']
			and !$this->Model->brwConfig['actions']['delete']
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

		$this->set(array(
			'records' => $this->_formatForView($records, $this->Model),
			'permissions' => array($this->Model->alias => $this->Model->brwConfig['actions']),
			'filters' => $filters,
		));
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
				$this->Model->brwConfig['actions']['add'] = false;
				$this->Model->brwConfig['actions']['delete'] = false;
			}
		}

		$contain = array();

		if ($this->Model->brwConfig['images']) {
			$contain['BrwImage'] = array('order' => 'BrwImage.category_code, BrwImage.modified asc');
		}

		if ($this->Model->brwConfig['files']) {
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
					!empty($this->Model->brwConfig['sortable']['direction'])
					and $this->Model->brwConfig['sortable']['direction'] == 'desc'
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
		if (!empty($this->Model->hasMany) and $this->Model->brwConfig['show_children']){
			foreach ($assocs as $key_model => $related_model) {
				if (!in_array($key_model, $this->Model->brwConfig['hide_children'])) {
					$AssocModel = $this->Model->$key_model;
					$AssocModel->Behaviors->attach('Brownie.Cms');
					if ($this->_checkPermissions($key_model)) {
						if ($indx = array_search($related_model['foreignKey'], $AssocModel->brwConfig['paginate']['fields'])) {
							unset($AssocModel->brwConfig['paginate']['fields'][$indx]);
						}
						$filters = $this->_filterConditions($AssocModel);
						$this->paginate[$AssocModel->name] = Set::merge(
							$AssocModel->brwConfig['paginate'],
							array('conditions' => $filters)
						);
						$assoc_models[] = array(
							'brwConfig' => $AssocModel->brwConfig,
							'model' => $key_model,
							'records' => $this->_formatForView($this->paginate($AssocModel, array($related_model['foreignKey'] => $id)), $AssocModel),
							'schema' => $this->Content->schemaForView($this->Model->$key_model),
							'filters' => array_merge(
								$this->_filterConditions($AssocModel),
								array($AssocModel->alias . '.' . $related_model['foreignKey'] => $id)
							),
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
			if ($this->Model->brwConfig['sortable']) {
				$fieldList[] = $this->Model->brwConfig['sortable']['field'];
			}
			if ($this->Model->saveAll($this->data, array('fieldList' => $fieldList, 'validate' => 'first'))) {
				$msg =	($this->Model->brwConfig['names']['gender'] == 1) ?
					sprintf(__d('brownie', 'The %s has been saved [male]', true), $this->Model->brwConfig['names']['singular']):
					sprintf(__d('brownie', 'The %s has been saved [female]', true), $this->Model->brwConfig['names']['singular']);
				$this->Session->setFlash($msg, 'flash_success');

				if (!empty($this->data['Content']['after_save'])) {
					switch ($this->data['Content']['after_save']) {
						case 'edit':
							$this->redirect(array('action'=>'edit', $this->Model->name, $this->Model->id, 'after_save' => 'edit'));
						break;
						case 'add':
							$this->redirect(array('action' => 'edit', $this->Model->name, 'after_save' => 'add'));
						break;
						case 'index':
							$this->redirect(array('action' => 'index', $this->Model->name));
						break;
						case 'parent':
							if ($parent = $this->Model->brwConfig['parent']) {
								$foreignKey = $this->Model->belongsTo[$parent]['foreignKey'];
								if (!empty($this->data[$this->Model->alias][$foreignKey])) {
									$this->redirect(array('action' => 'view', $parent, $this->data[$this->Model->alias][$foreignKey]));
								}
							}
							$this->redirect(array('action' => 'index', $this->Model->name));
						break;
						case 'view':
							$this->redirect(array('action' => 'view', $this->Model->name, $this->Model->id));
						break;
						case 'home':
							$this->redirect(array('controller' => 'brownie', 'action' => 'index'));
						break;
					}
				}
			} else {
				$msg =	($this->Model->brwConfig['names']['gender'] == 1) ?
					sprintf(__d('brownie', 'The %s could not be saved. Please, check the error messages.[male]', true), $this->Model->brwConfig['names']['singular']):
					sprintf(__d('brownie', 'The %s could not be saved. Please, check the error messages.[female]', true), $this->Model->brwConfig['names']['singular']);
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
				$this->data = Set::merge(
					$this->Content->defaults($this->Model),
					$this->_filterConditions($this->Model, true)
				);
			}
		}

		if (method_exists($this->Model, 'brwBeforeEdit')) {
			$this->data = $this->Model->brwBeforeEdit($this->data);
			$this->set('schema', $this->Content->schemaForView($this->Model));
		}

		$this->set('fields', $fields);
		$this->set('fckFields', $this->Content->fckFields($this->Model));
		$this->_setAfterSaveOptionsParams($this->Model);
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
			or empty($this->Model->brwConfig[($uploadType == 'BrwFile') ? 'files' : 'images'][$categoryCode])
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
			$max = ($this->Model->brwConfig[$uploadKey][$categoryCode]['index'])? 1:10;
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
		if (!$this->Model->brwConfig['actions']['import']) {
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
			and empty($this->Model->brwConfig['sortable'])
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
		$fieldsConfig = $Model->brwConfig['fields'];
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

	function _setAfterSaveOptionsParams($Model) {
		$params = array(
			'type' => 'select',
			'label' => __d('brownie', 'After save', true),
			'options' => array(
				'edit' => ($Model->brwConfig['names']['gender'] == 1) ?
					sprintf(__d('brownie', 'Continue editing this %s [male]', true), $Model->brwConfig['names']['singular']):
					sprintf(__d('brownie', 'Continue editing this %s [female]', true), $Model->brwConfig['names']['singular'])
				,
				'add' =>  ($Model->brwConfig['names']['gender'] == 1) ?
					sprintf(__d('brownie', 'Add another %s [male]', true), $Model->brwConfig['names']['singular']):
					sprintf(__d('brownie', 'Add another %s [female]', true), $Model->brwConfig['names']['singular'])
				,
				'index' => ($Model->brwConfig['names']['gender'] == 1) ?
					sprintf(__d('brownie', 'Go to to index of all %s [male]', true), $Model->brwConfig['names']['plural']):
					sprintf(__d('brownie', 'Go to to index of all %s [female]', true), $Model->brwConfig['names']['plural'])
				,
				'view' => ($Model->brwConfig['names']['gender'] == 1) ?
					sprintf(__d('brownie', 'View saved %s [male]', true), $Model->brwConfig['names']['singular']):
					sprintf(__d('brownie', 'View saved %s [female]', true), $Model->brwConfig['names']['singular'])
				,
				'home' => __d('brownie', 'Go home', true),
			),
			'default' => (empty($this->params['named']['after_save']))? 'view':$this->params['named']['after_save'],
		);
		foreach (array('add', 'view', 'index') as $action) {
			if (!$Model->brwConfig['actions'][$action]) {
				unset($params['options'][$action]);
			}
		}

		if ($Model->brwConfig['parent']) {
			$parentModel = $Model->{$Model->brwConfig['parent']};
			$params['options']['parent'] =	($parentModel->brwConfig['names']['gender'] == 1) ?
				sprintf(__d('brownie', 'Go to the %s [male]', true), $parentModel->brwConfig['names']['singular']):
				sprintf(__d('brownie', 'Go to the %s [female]', true), $parentModel->brwConfig['names']['singular']);
		}

		$this->set('afterSaveOptionsParams', $params);
	}


	function _filterConditions($Model, $forData = false) {
		$filter = array();
		foreach ($Model->_schema as $field => $value) {
			if ($field == 'id') continue;
			$keyNamed = $Model->alias . '.' . $field;
			if (array_key_exists($keyNamed, $this->params['named'])) {
				if ($forData) {
					$filter[$Model->alias][$field] = $this->params['named'][$keyNamed];
				} else {
					$filter[$keyNamed] = $this->params['named'][$keyNamed];
				}
			}
		}
		return $filter;
	}


}