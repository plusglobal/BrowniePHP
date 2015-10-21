<?php

class DownloadsController extends BrownieAppController {

	public $uses = array();


	public function beforeFilter() {
		$this->Auth->allow();
	}


	public function view($model, $type, $idRecord, $category_code, $file) {
		return $this->_get($model, $type, $idRecord, $category_code, $file, false);
	}


	public function get($model, $type, $idRecord, $category_code, $file) {
		return $this->_get($model, $type, $idRecord, $category_code, $file, true);
	}


	public function _get($model, $type, $idRecord, $category_code, $file, $download = true) {
		$Model = ClassRegistry::init($model);
		$filePath = $Model->brwConfig[$type][$category_code]['path'] . DS . $model . DS . $idRecord . DS . $file;
		$isPublic = (substr($filePath, 0, strlen(WWW_ROOT)) === WWW_ROOT);
		if (!$isPublic and !$this->Session->check('Auth.BrwUserLogged')) {
			throw new NotFoundException();
		}
		$this->response->file($filePath, array('download' => $download));
		return $this->response;
	}


}