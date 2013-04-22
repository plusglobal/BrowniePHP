<?php

class DownloadsController extends BrownieAppController {

	public $uses = array();


	public function beforeFilter() {
		$this->Auth->allow();
	}


	public function view($model, $idRecord, $category_code, $file) {
		$this->_get($model, 'images', $idRecord, $category_code, $file, false);
	}


	public function get($model, $type, $idRecord, $category_code, $file) {
		$this->_get($model, $type, $idRecord, $category_code, $file, true);
	}


	public function _get($model, $type, $idRecord, $category_code, $file, $download = true) {
		$Model = ClassRegistry::init($model);
		$filePath = $Model->brwConfig[$type][$category_code]['path'] . DS . $model . DS . $idRecord . DS . $file;
		/*$isPublic = (substr($file, 0, strlen(WWW_ROOT)) === WWW_ROOT);
		var_dump($isPublic);
		if (!$isPublic and !$this->Session->check('Auth.BrwUser')) {
			$this->response->statusCode('404');
		}*/
		$this->viewClass = 'Media';
		$pathinfo = pathinfo($filePath);
		$params = array(
			'id' => $pathinfo['basename'],
			'name' => $pathinfo['filename'],
			'extension' => $pathinfo['extension'],
			'download' => $download,
			'path' => $pathinfo['dirname'] . DS
		);
		$this->set($params);
	}


}