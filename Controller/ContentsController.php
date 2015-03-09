<?php

class ContentsController extends BrownieAppController {

	public $components = array('Paginator');
	public $helpers = array('Brownie.i18n');
	public $Model;
	public $uses = array('Brownie.Content');
	public $paginate = array();
	public $data = array();


	public function beforeFilter() {
		parent::beforeFilter();

		if (!empty($this->params['pass'][0])) {
			$model = $this->params['pass'][0];
		} elseif (!empty($this->request->data['Content']['model'])) {
			$model = $this->request->data['Content']['model'];
		}
		if (empty($model) or !$this->Content->modelExists($model)) {
			throw new NotFoundException('Model does not exists');
		}

		$this->Model = ClassRegistry::init($model);
		$this->Model->recursive = -1;
		$this->Model->Behaviors->attach('Brownie.BrwPanel');
		$this->Model->attachBackend();

		$action = $this->params['action'];
		if ($action == 'edit' and empty($this->params['pass'][1]))  {
			$action = 'add';
		}
		if (!$this->_brwCheckPermissions($model, $action)) {
			throw new NotFoundException('No permissions');
		}

		$this->Model->brwConfig['actions'] = array_merge(
			$this->Model->brwConfig['actions'],
			$this->arrayPermissions($this->Model->alias)
		);
		$this->_checkBrwUserCrud();
		$this->Content->i18nInit($this->Model);

		$brwConfig = $this->Model->brwConfig;
		$schema = $this->Content->schemaForView($this->Model);
		$model = $this->Model->alias;
		$this->set(compact('model', 'schema', 'brwConfig'));
	}


	public function index() {
		$this->Paginator->settings = $this->Model->brwConfig['paginate'];
		$this->Paginator->settings['fields'] = array_diff(
			$this->Model->brwConfig['paginate']['fields'],
			array_keys($this->Model->brwConfig['fields']['virtual'])
		);

		if ($this->Model->Behaviors->attached('Tree')) {
			$this->set('isTree', true);
			$this->Paginator->settings['order'] = 'lft';
		}
		$filters = $this->_filterConditions($this->Model);
		$this->Paginator->settings['conditions'] = Set::merge($this->Paginator->settings['conditions'], $filters);
		$this->Paginator->settings['contain'] = $this->Content->relatedModelsForIndex($this->Model, $this->Paginator->settings);
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


	public function view($model, $id) {
		$this->Model->Behaviors->attach('Containable');
		$params = array(
			'conditions' => array($this->Model->name . '.id' => $id),
			'contain' => $this->Content->relatedModelsForView($this->Model),
		);
		$record = $this->Model->find('all', $params);

		if (empty($record)) {
			throw new NotFoundException('Record does not exists');
		}

		if (method_exists($this->Model, 'brwAfterFind')) {
			$record = $this->Model->brwAfterFind($record);
		}
		$record = $record[0];

		//ejecutar brwAfterFind en los modelos relacionados que estan en $contain

		$neighbors = $this->Content->neighborsForView($this->Model, $record, $restricted = null, $this->params['named']);
		$permissions[$model] = $this->arrayPermissions($model);

		$assocs = array_merge($this->Model->hasMany, $this->Model->hasOne);
		if ($this->Model->Behaviors->attached('Tree')) {
			$assocs[$model] = array('className' => 'User', 'foreignKey' => 'parent_id');
		}
		$assoc_models = array();
		if (!empty($this->Model->hasMany) and $this->Model->brwConfig['show_children']) {
			foreach ($assocs as $key_model => $related_model) {
				if (substr($key_model, 0, 8) == 'BrwI18n_') continue;
				if (!in_array($key_model, $this->Model->brwConfig['hide_children'])) {
					if ($key_model == $model) {
						$AssocModel = $this->Model;
					} else {
						$AssocModel = $this->Model->$key_model;
					}
					$AssocModel->Behaviors->attach('Brownie.BrwPanel');
					if ($this->_brwCheckPermissions($key_model)) {
						if ($indx = array_search($related_model['foreignKey'], $AssocModel->brwConfig['paginate']['fields'])) {
							unset($AssocModel->brwConfig['paginate']['fields'][$indx]);
						}
						$filters = Hash::merge(
							$this->_filterConditions($AssocModel),
							(!empty($this->Model->hasMany[$AssocModel->name]['conditions'])) ?
								$this->Model->hasMany[$AssocModel->name]['conditions'] : array()
						);
						$this->Paginator->settings = [];
						$this->Paginator->settings[$AssocModel->name] = Set::merge(
							$AssocModel->brwConfig['paginate'],
							array('conditions' => $filters),
							array('contain' => $this->Content->relatedModelsForIndex($AssocModel, $AssocModel->brwConfig['paginate']))
						);
						$this->Paginator->settings[$AssocModel->name]['fields'] = array_diff(
							$AssocModel->brwConfig['paginate']['fields'],
							array_keys($AssocModel->brwConfig['fields']['virtual'])
						);
						$assoc_models[] = array(
							'brwConfig' => $AssocModel->brwConfig,
							'model' => $key_model,
							'records' => $this->_formatForView(
								$this->paginate(
									$AssocModel,
									array($AssocModel->alias . '.' . $related_model['foreignKey'] => $id)
								),
								$AssocModel
							),
							'schema' => $this->Content->schemaForView($AssocModel),
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

		$this->_hideConditionalFields($this->Model, $record);
		$record = $this->Content->formatHABTMforView($record, $this->Model);
		$record = $this->_formatForView($record, $this->Model);
		$record = $this->Content->addI18nValues($record, $this->Model);
		$this->set('record', $record);
		$this->set('neighbors', $neighbors);
		$this->set('assoc_models', $assoc_models);
		$this->set('permissions', $permissions);
		$this->set('brwConfig', $this->Model->brwConfig);
		$this->_setI18nParams($this->Model);
	}


	public function edit($model, $id = null) {
		if (!empty($id)) {
			if (!$this->Model->read(array('id'), $id)) {
				throw new NotFoundException('Record does not exists');
			}
			$action = 'edit';
		} else {
			$action = 'add';
		}
		if (!$this->_brwCheckPermissions($model, $action)) {
			throw new NotFoundException('No permissions');
		}
		$fields = $id ? $this->Content->fieldsEdit($this->Model) : $this->Content->fieldsAdd($this->Model);
		if (!empty($this->request->data)) {
			if (!empty($this->request->data[$this->Model->alias]['id']) and $this->request->data[$this->Model->alias]['id'] != $id) {
				throw new NotFoundException('Record does not exists');
			}
			$this->Content->addValidationsRules($this->Model, $id);
			$this->request->data = $this->Content->brownieBeforeSave($this->request->data, $this->Model, $this->Session);

			if ($this->Content->makeSave($this->Model, $this->request->data, $fields)) {
				$msg =	($this->Model->brwConfig['names']['gender'] == 1) ?
					__d('brownie', 'The %s has been saved [male]', __($this->Model->brwConfig['names']['singular'])):
					__d('brownie', 'The %s has been saved [female]', __($this->Model->brwConfig['names']['singular']));
				$this->Session->setFlash($msg, 'flash_success');

				if (!empty($this->request->data['Content']['after_save'])) {
					$this->_afterSaveRedirect();
				}
			} else {
				$msg =	($this->Model->brwConfig['names']['gender'] == 1) ?
					__d('brownie', 'The %s could not be saved. Please, check the error messages.[male]', __($this->Model->brwConfig['names']['singular'])):
					__d('brownie', 'The %s could not be saved. Please, check the error messages.[female]', __($this->Model->brwConfig['names']['singular']));
				$this->Session->setFlash($msg, 'flash_error');
			}
		}


		$contain = array();
		if (!empty($this->Model->hasAndBelongsToMany)) {
			foreach ($this->Model->hasAndBelongsToMany as $key_model => $related_model) {
				if (!in_array($key_model, $contain)) {
					$contain[] = $key_model;
				}
			}
		}

		$this->set('related', $this->Content->relatedData($this->Model));

		if (empty($this->request->data)) {
			if ($id) {
				$this->Model->Behaviors->attach('Containable');
				if ($this->Model->brwConfig['images']) {
					$contain[] = 'BrwImage';
				}
				if ($this->Model->brwConfig['files']) {
					$contain[] = 'BrwFile';
				}
				foreach ((array)$this->Model->schema() as $field => $cnf) {
					$this->Model->brwConfig['fields']['sanitize_html'][$field] = false;
				}
				$this->request->data = $this->Model->find('first', array(
					'conditions' => array($this->Model->name . '.id' => $id),
					'contain' => $contain,
				));
				$this->request->data = $this->Content->i18nForEdit($this->request->data, $this->Model);
			} else {
				$this->request->data = Set::merge(
					$this->Content->defaults($this->Model),
					$this->_filterConditions($this->Model, true)
				);
			}
			$this->request->data['Content']['referer'] = env('HTTP_REFERER') ? $this->referer() : null;
		}

		if (method_exists($this->Model, 'brwBeforeEdit') or !empty($this->Model->Behaviors->__methods['brwBeforeEdit'])) {
			$this->request->data = $this->Model->brwBeforeEdit($this->request->data);
			$this->set('schema', $this->Content->schemaForView($this->Model));
		}

		$this->set('fields', $fields);
		$this->set('fckFields', $this->Content->fckFields($this->Model));
		$this->_setI18nParams($this->Model);
		$this->_setAfterSaveOptionsParams($this->Model, $this->request->data);
	}


	public function delete($model, $id) {
		$record = $this->Model->findById($id);
		if (empty($record)) {
			throw new NotFoundException('Record does not exists');
		}
		$home = array('plugin' => 'brownie', 'controller' => 'brownie', 'action' => 'index', 'brw' => false);
		$redirect = $this->referer($home);
		$deleted = $this->Content->remove($this->Model, $id);
		if (!$deleted) {
			$this->Session->setFlash(__d('brownie', 'Unable to delete'), 'flash_error');
			$this->redirect($redirect);
		} else {
			$this->Session->setFlash(__d('brownie', 'Successful delete'), 'flash_success');
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
				if ($this->Model->brwConfig['actions']['index']) {
					$redirect = array(
						'plugin' => 'brownie', 'controller' => 'contents',
						'action' => 'index', $model
					);
				} else {
					$redirect = $home;
				}
			}
			$this->redirect($redirect);
		}
	}


	public function delete_multiple($model) {
		$plural = $this->Model->brwConfig['names']['plural'];
		if (empty($this->request->data['Content']['id'])) {
			$msg = __d('brownie', 'No %s selected to delete', $plural);
			$this->Session->setFlash($msg, 'flash_notice');
		} else {
			$deleted = $no_deleted = 0;
			foreach ($this->request->data['Content']['id'] as $id) {
				if ($this->Content->remove($this->Model, $id)) {
					$deleted++;
				} else {
					$no_deleted++;
				}
			}
			$msg_deleted = $msg_no_deleted = '';
			if ($deleted) {
				$msg_deleted = __d('brownie', '%d %s deleted.', $deleted, $plural) . ' ';
			}
			if ($no_deleted) {
				$msg_no_deleted = __d('brownie', '%d %s no deleted.', $no_deleted, $plural) . ' ';
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


	public function edit_upload($model, $uploadType, $recordId, $categoryCode, $uploadId = null) {
		if (
			!in_array($uploadType, array('BrwFile', 'BrwImage'))
			or empty($this->Model->brwConfig[($uploadType == 'BrwFile') ? 'files' : 'images'][$categoryCode])
		) {
			$this->response->statusCode('404');
		}

		if (!empty($this->request->data)) {
			$cantSaved = 0;
			foreach ($this->request->data[$uploadType] as $data) {
				if ($this->Model->{$uploadType}->save($data)) {
					$cantSaved++;
				}
			}
			if ($cantSaved) {
				$msg = ($uploadType == 'BrwFile') ?
					__d('brownie', '%s files saved', $cantSaved) :
					__d('brownie', '%s images saved', $cantSaved);
				$msgType = 'flash_success';
			} else {
				$msg = ($uploadType == 'BrwFile') ?
					__d('brownie', 'No files saved', $cantSaved):
					__d('brownie', 'No images saved', $cantSaved);
				$msgType = 'flash_notice';
			}
			$this->Session->setFlash($msg, $msgType);

			$this->redirect(array(
				'plugin' => 'brownie', 'controller' => 'contents',
				'action' => 'view', $model, $recordId
			));

		}
		if (empty($this->request->data) and $uploadId) {
			$data = $this->Model->{$uploadType}->findById($uploadId);
			$this->request->data[$uploadType][0] = $data[$uploadType];
			$max = 1;
		} else {
			$uploadKey = ($uploadType == 'BrwFile') ? 'files' : 'images';
			$max = ($this->Model->brwConfig[$uploadKey][$categoryCode]['index'])? 1:10;
		}
		$this->set(compact('model', 'uploadType', 'recordId', 'categoryCode', 'uploadId', 'max'));
	}


	public function delete_upload($model, $uploadType, $recordId) {
		if (!in_array($uploadType, array('BrwFile', 'BrwImage'))) {
			$this->response->statusCode('404');
		}
		if ($this->Model->{$uploadType}->delete($recordId)) {
			$msg = ($uploadType == 'BrwFile') ?
				__d('brownie', 'The file was deleted') :
				__d('brownie', 'The image was deleted');
			$this->Session->setFlash($msg, 'flash_success');
		} else {
			$msg = ($uploadType == 'BrwFile') ?
				__d('brownie', 'The file could not be deleted') :
				__d('brownie', 'The image could not be deleted');
			$this->Session->setFlash($msg, 'flash_error');
		}

		$redirecTo = env('HTTP_REFERER');
		if (!$redirecTo) {
			$redirecTo = array('controller' => 'brownie', 'action' => 'index', 'plugin' => 'brownie');
		}
		$this->redirect($redirecTo);
	}


	public function import($model) {
		if (!$this->Model->brwConfig['actions']['import']) {
			$this->response->statusCode('404');
		}
		if (!empty($this->request->data)) {
			$result = $this->Model->brwImport($this->request->data);
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
					$import['msg'] = __d('brownie', 'The import could not be done. Please try again');
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
			$msg = __d('brownie', 'Warning: %s::brwImport() must be defined', $model);
			$this->Session->setFlash($msg, 'flash_error');
		}

		$this->set('related', $this->Content->relatedData($this->Model));
	}


	public function export($model) {
		$type = $this->Model->brwConfig['export']['type'];
		if (empty($type)) {
			throw new NotFoundException();
		}
		if (!in_array($type, array('xml', 'csv', 'json', 'php', 'xls', 'xlsx'))) {
			$type = 'xml';
		}
		$this->layout = 'ajax';
		if ($type == 'xml') {
			$this->helpers[] = 'Xml';
		}
		header('Content-Disposition: attachment; filename=' . $model . '.' . $type . '');
		header('Content-Type: application/force-download');
		header('Pragma: no-cache');
		header('Pragma: public');
		header('Expires: 0');
		header('Content-Transfer-Encoding: binary');
		$this->set(array(
			'records' => $this->Content->getForExport($this->Model, $this->params['named']),
			'relatedBrwConfig' => $this->Content->getRelatedBrwConfig($this->Model),
		));
		$this->render('/Contents/export/' . $type);
	}


	public function reorder($model, $direction, $id) {
		if (
			!in_array($direction, array('up', 'down'))
			and !$this->Model->Bheaviors->attached('Tree')
			and empty($this->Model->brwConfig['sortable'])
		) {
			$this->response->statusCode('404');
		}

		if ($this->Content->reorder($this->Model, $direction, $id)) {
			$this->Session->setFlash(__d('brownie', 'Successfully reordered'), 'flash_success');
		} else {
			$this->Session->setFlash(__d('brownie', 'Failed to reorder'), 'flash_error');
		}

		if ($ref = env('HTTP_REFERER')) {
			$this->redirect($ref);
		} else {
			$this->redirect(array('controller' => 'contents', 'action' => 'index', $model));
		}
	}


	public function filter($model) {
		$url = array('controller' => 'contents', 'action' => 'index', $model);
		foreach ($this->Model->brwSchema() as $field => $cnf) {
			$type = $cnf['type'];
			if (in_array($type, array('date', 'datetime'))) {
				foreach (array('_from', '_to') as $key) {
					if (!empty($this->request->data[$model][$field . $key]['year'])) {
						$data = $this->Content->dateComplete($this->request->data[$model][$field . $key], $key, $type);
						$url[$model . '.' . $field . $key] = $data['year'] . '-' . $data['month'] . '-' . $data['day'];
						if ($type == 'datetime') {
							$url[$model . '.' . $field . $key] .= ' ' . $data['hour'] . ':' . $data['min'] . ':' . $data['sec'];
						}
					}
				}
			} elseif (
				$type == 'float' or
				(
					$type == 'integer'
					and !$this->Content->brwIsForeignKey($this->Model, $field)
					and array_key_exists($field, $this->Model->brwConfig['fields']['filter'])
					and empty($this->Model->brwConfig['fields']['filter'][$field])
				)
			) {
				foreach (array('_from', '_to') as $key) {
					if (
						array_key_exists($field . $key, $this->request->data[$model])
						and
						$this->request->data[$model][$field . $key] != ''
					) {
						$url[$model . '.' . $field . $key] = $this->request->data[$model][$field . $key];
					}
				}
			} elseif ($type == 'boolean') {
				if (
					array_key_exists($field, $this->request->data[$model])
					and
					in_array($this->request->data[$model][$field], array('1', '0'))
				) {
					$url[$model . '.' . $field] = $this->request->data[$model][$field];
				}
			} elseif (!empty($this->request->data[$model][$field])) {
				if (is_array($this->request->data[$model][$field])) {
					$url[$model . '.' . $field] = join('.', $this->request->data[$model][$field]);
				} else {
					$url[$model . '.' . $field] = trim($this->request->data[$model][$field]);
				}
			}
		}
		foreach ($this->Model->hasAndBelongsToMany as $relatedModel => $cnf) {
			if (!empty($this->request->data[$relatedModel][$relatedModel])) {
				$url[$relatedModel] = join('.', $this->request->data[$relatedModel][$relatedModel]);
			}
		}
		$this->redirect($url);
	}


	public function _formatForView($data, $Model) {
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


	public function _formatSingleForView($data, $Model, $inView = false) {
		$fieldsConfig = $Model->brwConfig['fields'];
		$fieldsHide = $fieldsConfig['hide'];
		$fK = $this->Content->getForeignKeys($Model);
		$permissions = $this->arrayPermissions($Model->name);
		$retData = $data;
		$schema = $Model->schema();
		if (!empty($retData[$Model->name])) {
			foreach ($retData[$Model->name] as $key => $value) {
				if (in_array($key, $fieldsHide)) {
					unset($retData[$Model->name][$key]);
				} elseif (in_array($key, $fieldsConfig['code'])) {
					$retData[$Model->name][$key] = '<pre>' . htmlspecialchars($retData[$Model->name][$key]) . '</pre>';
				} elseif (isset($fK[$key]) and !empty($data[$fK[$key]['alias']])) {
					$RelModel = ($fK[$key]['className'] == $Model->name) ? $Model : $Model->{$fK[$key]['alias']};
					$retData[$Model->name][$key] = $data[$fK[$key]['alias']][$RelModel->displayField];
					if ($this->_brwCheckPermissions($RelModel->name, 'view', $data[$fK[$key]['alias']]['id'])) {
						$relatedURL = Router::url(array(
							'controller' => 'contents', 'action' => 'view', 'plugin' => 'brownie',
							$fK[$key]['className'], $data[$fK[$key]['alias']]['id']
						));
						$retData[$Model->name][$key] = '<a href="'.$relatedURL.'">' . $retData[$Model->name][$key] . '</a>';
					}
				} else {
					if (!empty($schema[$key]['type'])) {
						switch($schema[$key]['type']) {
							case 'boolean':
								if (!is_null($retData[$Model->name][$key])) {
									$retData[$Model->name][$key] = $retData[$Model->name][$key] ?
										__d('brownie', 'Yes'): __d('brownie', 'No');
								} else {
									$retData[$Model->name][$key] = '';
								}
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
				if (in_array($key, $fieldsConfig['email']) and $schema[$key]['type'] == 'string') {
					$email = $retData[$Model->name][$key];
					$retData[$Model->name][$key] = '<a class="mailto" href="mailto:' . $email . '">' . $email . '</a>';
				}
			}
			$retData[$Model->name]['brw_actions'] = $this->Content->actions($Model, $data, $permissions);
		}
		return $retData;
	}


	public function _formatTree($data, $Model) {
		$treeList = $Model->generateTreeList(null, null, null, '<span class="tree_prepend"></span>');
		foreach ($data as $i => $value) {
			$displayValue = $data[$i][$Model->alias][$Model->displayField];
			$data[$i][$Model->alias][$Model->displayField] =
				str_replace($displayValue, '', $treeList[$value[$Model->alias]['id']])
				. '<span class="tree_arrow"></span>' . $displayValue;
		}
		return $data;
	}


	public function _formatDate($date) {
		if (empty($date) or $date == '0000-00-00') {
			return __d('brownie', 'Date not set');
		} else {
			return date(Configure::read('brwSettings.dateFormat'), strtotime($date));
		}
	}


	public function _formatDateTime($datetime) {
		if (empty($datetime) or $datetime == '0000-00-00 00:00:00') {
			return __d('brownie', 'Datetime not set');
		} else {
			return date(Configure::read('brwSettings.datetimeFormat'), strtotime($datetime));
		}
	}


	public function _setAfterSaveOptionsParams($Model, $data) {

		if (!empty($this->params['named']['after_save'])) {
			$default = $this->params['named']['after_save'];
		} elseif ($data['Content']['referer']) {
			$default = 'referer';
		} elseif ($Model->brwConfig['actions']['view']) {
			$default = 'view';
		} elseif ($Model->brwConfig['actions']['index']) {
			$default = 'index';
		} else {
			$default = 'home';
		}

		$params = array(
			'type' => 'select',
			'label' => __d('brownie', 'After save'),
			'options' => array(
				'referer' => __d('brownie', 'Back to where I was'),
				'view' => ($Model->brwConfig['names']['gender'] == 1) ?
					__d('brownie', 'View saved %s [male]', __($Model->brwConfig['names']['singular'])):
					__d('brownie', 'View saved %s [female]', __($Model->brwConfig['names']['singular']))
				,
				'add' =>  ($Model->brwConfig['names']['gender'] == 1) ?
					__d('brownie', 'Add another %s [male]', __($Model->brwConfig['names']['singular'])):
					__d('brownie', 'Add another %s [female]', __($Model->brwConfig['names']['singular']))
				,
				'index' => ($Model->brwConfig['names']['gender'] == 1) ?
					__d('brownie', 'Go to to index of all %s [male]', __($Model->brwConfig['names']['plural'])):
					__d('brownie', 'Go to to index of all %s [female]', __($Model->brwConfig['names']['plural']))
				,
				'edit' => ($Model->brwConfig['names']['gender'] == 1) ?
					__d('brownie', 'Continue editing this %s [male]', __($Model->brwConfig['names']['singular'])):
					__d('brownie', 'Continue editing this %s [female]', __($Model->brwConfig['names']['singular']))
				,
				'home' => __d('brownie', 'Go home'),
			),
			'default' => $default,
		);
		foreach (array('add', 'view', 'index') as $action) {
			if (!$Model->brwConfig['actions'][$action]) {
				unset($params['options'][$action]);
			}
		}

		if ($Model->brwConfig['parent']) {
			$parentModel = $Model->{$Model->brwConfig['parent']};
			$params['options']['parent'] =	($parentModel->brwConfig['names']['gender'] == 1) ?
				__d('brownie', 'Go to the %s [male]', __($parentModel->brwConfig['names']['singular'])):
				__d('brownie', 'Go to the %s [female]', __($parentModel->brwConfig['names']['singular']));
		}
		if (!$data['Content']['referer'] or !empty($this->params['named']['after_save'])) {
			unset($params['options']['referer']);
		}
		$this->set('afterSaveOptionsParams', $params);
	}


	public function _filterConditions($Model, $forData = false) {
		return $this->Content->filterConditions($Model, $this->params['named'], $forData);
	}


	public function _filtersForView($filters) {
		foreach ($filters as $field => $value) {
			if (strstr($field, '>') or strstr($field, '<')) {
				unset($filters[$field]);
			} elseif (is_array($value)) {
				$filters[$field] = join('.', $value);
			}
		}
		return $filters;
	}


	public function _setFilterData($Model) {
		$filterFields = $this->Model->brwConfig['fields']['filter'];
		$model = $Model->alias;
		foreach ($filterFields as $field => $multiple) {
			if ($field == 'brwHABTM') continue;
			$schema = $Model->brwSchema();
			$type = $schema[$field]['type'];
			$isRange = (
				in_array($type, array('date', 'datetime', 'float'))
				or
				(in_array($type, array('integer')) and !$this->Content->brwIsForeignKey($this->Model, $field) and !$multiple)
			);
			if ($isRange) {
				foreach (array('_from', '_to') as $key) {
					if (isset($this->params['named'][$model . '.' . $field . $key])) {
						$this->request->data[$model][$field . $key] = $this->params['named'][$model . '.' . $field . $key];
					}
				}
			} elseif ($type == 'integer' or $type == 'boolean' or $type == 'string' or $type == 'select') {
				if (array_key_exists($model . '.' . $field, $this->params['named'])) {
					$fieldData = $this->params['named'][$model . '.' . $field];
					if ($type  == 'integer' and strstr($fieldData, '.')) {
						$fieldData = explode('.', $fieldData);
					}
					$this->request->data[$model][$field] = $fieldData;
				}
			}
		}
		foreach ($filterFields['brwHABTM'] as $relatedModel) {
			if (!empty($this->params['named'][$relatedModel])) {
				$this->request->data[$relatedModel][$relatedModel] = explode('.', $this->params['named'][$relatedModel]);
			}
		}
		$relatedClassNames = array();
		foreach ($Model->belongsTo as $alias => $relatedModel) {
			if (in_array($relatedModel['foreignKey'], array_keys($filterFields))) {
				$relatedClassNames[] = $alias;
			}
		}
		foreach ($Model->hasAndBelongsToMany as $alias => $relatedModel) {
			if (in_array($relatedModel['className'], $filterFields['brwHABTM'])) {
				$relatedClassNames[] = $alias;
			}
		}
		foreach ($relatedClassNames as $className) {
			$varSet = Inflector::pluralize($className);
			$varSet[0] = strToLower($varSet[0]);
			if ($this->Model->{$className}->Behaviors->attached('Tree')) {
				$list = $this->Model->{$className}->generateTreeList(null, null, null, '_');
			} else {
				$list = $this->Model->{$className}->find('list');
			}
			$this->set($varSet, $list);
		}
	}


	public function _setI18nParams($Model) {
		$i18nFields = array();
		if ($Model->Behaviors->enabled('Translate')) {
			$i18nFields = array_keys($Model->Behaviors->Translate->settings[$Model->alias]);
		}
		$this->set(array('i18nFields' => $i18nFields, 'langs3chars' => Configure::read('Config.langs')));
	}


	public function _checkBrwUserCrud() {
		$authModel = AuthComponent::user('model');
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
			$this->redirect(array('action' => 'view', $authModel, AuthComponent::user('id')));
		}
	}


	public function _hideConditionalFields($Model, $record) {
		$habtmToHide = $fieldsToHide = array();
		foreach ($Model->brwConfig['fields']['conditional'] as $field => $config) {
			if (isset($record[$Model->alias][$field])) {
				$toHide = array_diff(
					$config['hide'],
					$config['show_conditions'][$record[$Model->alias][$field]]
				);
				$fieldsToHide = array_merge($fieldsToHide, $toHide);
				if (!empty($fieldsToHide['HABTM'])) {
					$habtmToHide = array_merge($habtmToHide, $fieldsToHide['HABTM']);
					unset($fieldsToHide['HABTM']);
				}
			}
		}
		$Model->brwConfig['fields']['no_view']
			= array_merge($Model->brwConfig['fields']['hide'], $fieldsToHide);
		$Model->brwConfig['hide_related']['hasAndBelongsToMany']
			= array_merge($Model->brwConfig['hide_related']['hasAndBelongsToMany'], $habtmToHide);
	}


	public function _afterSaveRedirect() {
		switch ($this->request->data['Content']['after_save']) {
			case 'referer':
				if ($this->request->data['Content']['referer']) {
					$this->redirect($this->request->data['Content']['referer']);
				} else {
					$this->redirect(array('controller' => 'brownie', 'action' => 'index'));
				}
			break;
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
					if (!empty($this->request->data[$this->Model->alias][$foreignKey])) {
						$idRedir = $this->request->data[$this->Model->alias][$foreignKey];
					} else {
						$record = $this->Model->findById($this->Model->id);
						$idRedir = $record[$this->Model->alias][$foreignKey];
					}
					$this->redirect(array('action' => 'view', $parent, $idRedir));
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

}