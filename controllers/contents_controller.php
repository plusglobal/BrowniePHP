<?php

class ContentsController extends BrownieAppController {

	var $name = 'Contents';
	var $helpers = array('Brownie.i18n');
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
		$this->Model->Behaviors->attach('Brownie.Panel');


		if (!$this->_brwCheckPermissions($model, $this->params['action'])) {
			$this->cakeError('error404');
		}

		$this->Model->brwConfig['actions'] = array_merge(
			$this->Model->brwConfig['actions'],
			$this->arrayPermissions($this->Model->alias)
		);

		/*if (!Configure::read('Auth.BrwUser.root')) {
			$this->Model->brwConfig['actions'] = Set::merge(
				$this->Model->brwConfig['actions'],
				$this->Model->brwConfig['actions_no_root']
			);
			$this->Model->brwConfig['fields'] = Set::merge(
				$this->Model->brwConfig['fields'],
				$this->Model->brwConfig['fields_no_root']
			);
		}*/

		$this->_checkBrwUserCrud();

		$this->Content->i18nInit($this->Model);
	}

	function beforeRender() {
		$brwConfig = $this->Model->brwConfig;
		$schema = $this->Content->schemaForView($this->Model);
		$model = $this->Model->alias;
		$this->set(compact('model', 'schema', 'brwConfig'));
		parent::beforeRender();
	}


	function index() {
		$this->paginate = $this->Model->brwConfig['paginate'];
		if ($this->Model->Behaviors->attached('Tree')) {
			$this->set('isTree', true);
			$this->paginate['order'] = 'lft';
		}
		$filters = $this->_filterConditions($this->Model);
		$this->paginate['conditions'] = $filters;

		if (!empty($this->Model->brwConfig['paginate']['images'])) {
			$this->paginate['contain'][] = 'BrwImage';
		}

		$records = $this->paginate($this->Model);

		if (method_exists($this->Model, 'brwAfterFind')) {
			$records = $this->Model->brwAfterFind($records);
		}

		$this->set(array(
			'records' => $this->_formatForView($records, $this->Model),
			'permissions' => array($this->Model->alias => $this->Model->brwConfig['actions']),
			'filters' => $this->_filtersForView($filters),
			'isAnyFilter' => !empty($filters),
		));
		if ($this->Model->brwConfig['fields']['filter']) {
			$this->_setFilterData($this->Model);
		}
	}


	function view($model, $id) {
		$this->Model->Behaviors->attach('Containable');
		$record = $this->Model->find('all', array(
			'conditions' => array($this->Model->name . '.id' => $id),
			'contain' => $this->Content->relatedModelsForView($this->Model)
		));

		if (empty($record)) {
			$this->cakeError('error404');
		}

		if (method_exists($this->Model, 'brwAfterFind')) {
			$record = $this->Model->brwAfterFind($record);
		}
		$record = $record[0];

		//ejecutar brwAfterFind en los modelos relacionados que estan en $contain

		$neighbors = $this->Content->neighborsForView($this->Model, $record, $restricted = null, $this->params['named']);
		$permissions[$model] = $this->arrayPermissions($model);

		$assocs = array_merge($this->Model->hasMany, $this->Model->hasOne);
		$assoc_models = array();
		if (!empty($this->Model->hasMany) and $this->Model->brwConfig['show_children']) {
			foreach ($assocs as $key_model => $related_model) {
				if (substr($key_model, 0, 8) == 'BrwI18n_') continue;
				if (!in_array($key_model, $this->Model->brwConfig['hide_children'])) {
					$AssocModel = $this->Model->$key_model;
					$AssocModel->Behaviors->attach('Brownie.Panel');
					if ($this->_brwCheckPermissions($key_model)) {
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

		$record = $this->Content->formatHABTMforView($record, $this->Model);
		$record = $this->_formatForView($record, $this->Model);
		$record = $this->Content->addI18nValues($record, $this->Model);
		$this->set('record', $record);
		$this->set('neighbors', $neighbors);
		$this->set('assoc_models', $assoc_models);
		$this->set('permissions', $permissions);
		$this->_setI18nParams($this->Model);
	}


	function edit($model, $id = null) {
		if (!empty($id)) {
			if (!$this->Model->read(array('id'), $id)) {
				$this->cakeError('error404');
			}
			$action = 'edit';
		} else {
			$action = 'add';
		}

		if (!$this->_brwCheckPermissions($model, $action)) {
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
			if ($this->Model->brwConfig['sortable']) {
				$fieldList[] = $this->Model->brwConfig['sortable']['field'];
			}
			/*if (empty($this->data[$this->Model->alias]['id'])) {
				$this->Model->create();
				$this->Model->data = $this->data[$this->Model->alias];
			}*/
			if ($this->Model->saveAll($this->data, array('fieldList' => $fieldList, 'validate' => 'first'))) {
				$msg =	($this->Model->brwConfig['names']['gender'] == 1) ?
					sprintf(__d('brownie', 'The %s has been saved [male]', true), $this->Model->brwConfig['names']['singular']):
					sprintf(__d('brownie', 'The %s has been saved [female]', true), $this->Model->brwConfig['names']['singular']);
				$this->Session->setFlash($msg, 'flash_success');

				if (!empty($this->data['Content']['after_save'])) {
					switch ($this->data['Content']['after_save']) {
						case 'edit':
							$this->redirect(array('action' => 'edit', $this->Model->name, $this->Model->id, 'after_save' => 'edit'));
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

		$this->Model->brwConfig['fields']['no_sanitize_html'] = array_keys($this->Model->_schema);

		$contain = $related = array();
		if (!empty($this->Model->belongsTo)) {
			foreach ($this->Model->belongsTo as $key_model => $related_model) {
				$AssocModel = $this->Model->$key_model;
				if (!in_array($AssocModel, array('BrwImage', 'BrwFile'))) {
					if ($AssocModel->Behaviors->attached('Tree')) {
						$relatedData = $AssocModel->generatetreelist();
					} else {
						$relatedData = $this->Content->findList($AssocModel, $related_model);
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

		if ($this->Model->Behaviors->enabled('Tree')) {
			$related['tree']['parent_id'] = $this->Model->generatetreelist();
		}
		$this->set('related', $related);

		if (empty($this->data)) {
			if ($id) {
				$this->Model->Behaviors->attach('Containable');
				//$this->Model->Behaviors->detach('Brownie.Panel');
				if ($this->Model->brwConfig['images']) {
					$contain[] = 'BrwImage';
				}
				if ($this->Model->brwConfig['files']) {
					$contain[] = 'BrwFile';
				}
				$this->data = $this->Model->find('first', array(
					'conditions' => array($this->Model->name . '.id' => $id),
					'contain' => $contain,
				));
				$this->data = $this->Content->i18nForEdit($this->data, $this->Model);
			} else {
				$this->data = Set::merge(
					$this->Content->defaults($this->Model),
					$this->_filterConditions($this->Model, true)
				);
			}
		}

		if (method_exists($this->Model, 'brwBeforeEdit') or !empty($this->Model->Behaviors->__methods['brwBeforeEdit'])) {
			$this->data = $this->Model->brwBeforeEdit($this->data);
			$this->set('schema', $this->Content->schemaForView($this->Model));
		}

		$this->set('fields', $fields);
		$this->set('fckFields', $this->Content->fckFields($this->Model));
		$this->_setI18nParams($this->Model);
		$this->_setAfterSaveOptionsParams($this->Model);
	}


	function delete($model, $id) {
		$record = $this->Model->findById($id);
		if (!$record) {
			$this->cakeError('error404');
		}

		if ($this->Content->delete($this->Model, $id)) {
			$this->Session->setFlash(__d('brownie', 'Successful delete', true), 'flash_success');
		} else {
			$this->Session->setFlash(__d('brownie', 'Unable to delete', true), 'flash_error');
		}

		$afterDelete = empty($this->params['named']['after_delete'])? null : $this->params['named']['after_delete'];

		if ($afterDelete == 'parent') {
			$parentModel = $this->Model->brwConfig['parent'];
			if (!$parentModel) {
				$afterDelete = 'index';
			} else {
				$foreignKey = $this->Model->belongsTo[$parentModel]['foreignKey'];
				$redirect = array(
					'plugin' => 'brownie', 'controller' => 'contents',
					'action' => 'view', $parentModel, $record[$model][$foreignKey]
				);
			}
		}

		if ($afterDelete == 'index') {
			$redirect = array(
				'plugin' => 'brownie', 'controller' => 'contents',
				'action' => 'index', $model
			);
		}

		if (!$afterDelete) {
			$referer = env('HTTP_REFERER');
			if ($referer) {
				$redirect = $referer;
			} else {
				$redirect = array('plugin' => 'brownie', 'controller' => 'brownie', 'action' => 'index');
			}
		}

		$this->redirect($redirect);

	}

	function delete_multiple($model) {
		$plural = $this->Model->brwConfig['names']['plural'];
		if (empty($this->data['Content']['id'])) {
			$msg = sprintf(__d('brownie', 'No %s selected to delete', true), $plural);
			$this->Session->setFlash($msg, 'flash_notice');
		} else {
			$deleted = $no_deleted = 0;
			foreach ($this->data['Content']['id'] as $id) {
				if ($this->Content->delete($this->Model, $id)) {
					$deleted++;
				} else {
					$no_deleted++;
				}
			}
			$msg_deleted = $msg_no_deleted = '';
			if ($deleted) {
				$msg_deleted = sprintf(__d('brownie', '%d %s deleted.', true), $deleted, $plural) . ' ';
			}
			if ($no_deleted) {
				$msg_no_deleted = sprintf(__d('brownie', '%d %s no deleted.', true), $no_deleted, $plural) . ' ';
			}

			if ($deleted) {
				if ($no_deleted) $flashStatus = 'flash_notice';
				else $flashStatus = 'flash_success';
			} else {
				$flashStatus = 'flash_error';
			}
			$this->Session->setFlash($msg_deleted . $msg_no_deleted, $flashStatus);
		}

		$redir = env('HTTP_REFERER');
		if (empty($redir)) {
			$redir = array('action' => 'index', $model);
		}
		$this->redirect($redir);
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
			$this->cakeError('error404');
		}
		if (!empty($this->data)) {
			$result = $this->Model->brwImport($this->data);
			if (is_array($result)) {
				$import = $result;
				if (empty($import['flash'])) {
					$import['flash'] = ($import['result']) ? 'flash_success' : 'flash_error';
				}
			} else {
				if ($result) {
					$import['msg'] = $import['result'] = $result;
					$import['flash'] = 'flash_success';
				} else {
					$import['msg'] = __d('brownie', 'The import could not be done. Please try again', true);
					$import['result'] = false;
					$import['flash'] = 'flash_error';
				}
			}

			$this->Session->setFlash($import['msg'], $import['flash']);

			if ($import['result']) {
				$this->redirect(array('controller' => 'contents', 'action' => 'index', $model));
			}
		}

		if (Configure::read('debug') and !method_exists($this->Model, 'brwImport')) {
			$msg = sprintf(__d('brownie', 'Warning: %s::brwImport() must be defined', true), $model);
			$this->Session->setFlash($msg, 'flash_error');
		}
	}


	function export($model) {
		$type = $this->Model->brwConfig['actions']['export'];
		if (empty($type)) {
			$this->cakeError('error404');
		}
		if (!in_array($type, array('xml', 'csv', 'json', 'php', 'xls'))) {
			$type = 'xml';
		}
		$this->layout = 'ajax';
		if ($type == 'xml') {
			$this->helpers[] = 'Xml';
		}
		if ($type == 'xls') {
			header('Content-type: application/x-msdownload; charset=utf-8');
			$type = 'xls.csv';
		} else {
			header('Content-type: application/' . $type . '; charset=utf-8');
		}
		header('Content-Disposition: attachment; filename=' . $model . '.' . $type);
		header("Pragma: no-cache");
		header("Expires: 0");
		$this->set('records', $this->Content->getForExport($this->Model, $this->params['named']));
		$this->render('export/' . $type);
	}

	function reorder($model, $direction, $id) {
		if (
			!in_array($direction, array('up', 'down'))
			and !$this->Model->Bheaviors->attached('Tree')
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
		}
	}


	function filter($model) {
		//pr($this->data);
		$url = array('controller' => 'contents', 'action' => 'index', $model);
		foreach ($this->Model->brwConfig['fields']['filter'] as $field => $multiple) {
			$type = $this->Model->_schema[$field]['type'];
			if (in_array($type, array('date', 'datetime'))) {
				$keyFrom = $field . '_from';
				$data = $this->data[$model];
				foreach (array('_from', '_to') as $key) {
					if (
						!empty($this->data[$model][$field . $key]['year'])
						and !empty($this->data[$model][$field . $key]['month'])
						and !empty($this->data[$model][$field . $key]['day'])
					) {
						$url[$model . '.' . $field . $key] = $data[$field . $key]['year']
							. '-' . $data[$field . $key]['month'] . '-' . $data[$field . $key]['day'];
						if ($type == 'datetime') {
							if (
								!empty($this->data[$model][$field . $key]['hour'])
								and !empty($this->data[$model][$field . $key]['min'])
							) {
								$url[$model . '.' . $field . $key] .= ' ' . $data[$field . $key]['hour']
									. ':' . $data[$field . $key]['min'] . ':00';
							} else {
								$url[$model . '.' . $field . $key] .= ' ' . (($key == 'from') ? '00:00:00' : '23:59:59');
							}
						}
					}
				}
			} elseif (!empty($this->data[$model][$field])) {
				if (is_array($this->data[$model][$field])) {
					$url[$model . '.' . $field] = join('.', $this->data[$model][$field]);
				} else {
					$url[$model . '.' . $field] = $this->data[$model][$field];
				}
			}
		}
		$this->redirect($url);
	}


	function _formatForView($data, $Model) {
		$out = array();
		if (!empty($data[$Model->name])) {
			$out = $this->_formatSingleForView($data, $Model);
		} else {
			if ($Model->Behaviors->attached('Tree')) {
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
		if (!empty($retData[$Model->name])) {
			foreach ($retData[$Model->name] as $key => $value) {
				if (in_array($key, $fieldsHide)) {
					unset($retData[$Model->name][$key]);
				} elseif (in_array($key, $fieldsConfig['code'])) {
					$retData[$Model->name][$key] = '<pre>' . htmlspecialchars($retData[$Model->name][$key]) . '</pre>';
				} elseif (isset($foreignKeys[$key])) {
					$read = $Model->{$foreignKeys[$key]}->findById($retData[$Model->name][$key]);
					$retData[$Model->name][$key] = $read[$foreignKeys[$key]][$Model->{$foreignKeys[$key]}->displayField];
					if ($this->_brwCheckPermissions($Model->{$foreignKeys[$key]}->name, 'view', $read[$foreignKeys[$key]]['id'])) {
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
		}
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
				'view' => ($Model->brwConfig['names']['gender'] == 1) ?
					sprintf(__d('brownie', 'View saved %s [male]', true), $Model->brwConfig['names']['singular']):
					sprintf(__d('brownie', 'View saved %s [female]', true), $Model->brwConfig['names']['singular'])
				,
				'add' =>  ($Model->brwConfig['names']['gender'] == 1) ?
					sprintf(__d('brownie', 'Add another %s [male]', true), $Model->brwConfig['names']['singular']):
					sprintf(__d('brownie', 'Add another %s [female]', true), $Model->brwConfig['names']['singular'])
				,
				'index' => ($Model->brwConfig['names']['gender'] == 1) ?
					sprintf(__d('brownie', 'Go to to index of all %s [male]', true), $Model->brwConfig['names']['plural']):
					sprintf(__d('brownie', 'Go to to index of all %s [female]', true), $Model->brwConfig['names']['plural'])
				,
				'edit' => ($Model->brwConfig['names']['gender'] == 1) ?
					sprintf(__d('brownie', 'Continue editing this %s [male]', true), $Model->brwConfig['names']['singular']):
					sprintf(__d('brownie', 'Continue editing this %s [female]', true), $Model->brwConfig['names']['singular'])
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
		return $this->Content->filterConditions($Model, $this->params['named'], $forData);
	}


	function _filtersForView($filters) {
		foreach ($filters as $field => $value) {
			if (strstr($field, '>') or strstr($field, '<')) {
				unset($filters[$field]);
			}
		}
		return $filters;
	}


	function _setFilterData($Model) {
		$filterFields = $this->Model->brwConfig['fields']['filter'];
		$model = $Model->alias;
		foreach ($filterFields as $field => $multiple) {
			$type = $this->Model->_schema[$field]['type'];
			switch ($type) {
				case 'date': case 'datetime':
					foreach (array('_from', '_to') as $key) {
						if (!empty($this->params['named'][$model . '.' . $field . $key])) {
							$this->data[$model][$field . $key] = $this->params['named'][$model . '.' . $field . $key];
						} else {
							$this->data[$model][$field . $key] = date('Y-m-d');
							if ($type == 'datetime') {
								$this->data[$model][$field . $key] .= ' ' . (($key == '_from')? '00:00:00': '23:59:59');
							}
						}
					}
				break;
				case 'integer': case 'boolean':
					if (!empty($this->params['named'][$model . '.' . $field])) {
						$fieldData = $this->params['named'][$model . '.' . $field];
						if (strstr($fieldData, '.')) {
							$fieldData = explode('.', $fieldData);
						}
						$this->data[$model][$field] = $fieldData;
					}
				break;
			}
		}

		foreach ($Model->belongsTo as $relatedModel) {
			if (in_array($relatedModel['foreignKey'], array_keys($filterFields))) {
				$varSet = Inflector::pluralize($relatedModel['className']);
				$varSet[0] = strToLower($varSet[0]);
				$this->set($varSet, $this->Model->{$relatedModel['className']}->find('list'));
			}
		}

	}


	function _setI18nParams($Model) {
		$i18nFields = array();
		if ($Model->Behaviors->enabled('Translate')) {
			$i18nFields = array_keys($Model->Behaviors->Translate->settings[$Model->alias]);
		}
		$this->set(array('i18nFields' => $i18nFields, 'langs3chars' => Configure::read('Config.languages')));
	}


	function _checkBrwUserCrud() {
		$authModel = $this->Session->read('authModel');
		$mustRedirect = false;
		if ($this->Model->alias == 'BrwUser') {
			if ($authModel != 'BrwUser') {
				$mustRedirect = true;
			}
		} else {
			if ($this->Model->alias == $authModel and $this->params['action'] == 'index') {
				$mustRedirect = true;
			}
		}
		if ($mustRedirect) {
			$this->redirect(array('action' => 'view', $authModel, $this->Session->read('Auth.BrwUser.id')));
		}
	}

}