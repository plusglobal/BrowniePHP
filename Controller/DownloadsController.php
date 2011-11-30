<?php

class DownloadsController extends BrownieAppController {

	var $uses = array();


	function beforeFilter() {
		$this->Auth->allow('*');
	}


	function view($model, $idRecord, $category_code, $file) {
		$this->_get($model, $idRecord, $category_code, $file, false);
	}


	function get($model, $idRecord, $category_code, $file) {
		$this->_get($model, $idRecord, $category_code, $file, true);
	}


	function _get($model, $idRecord, $category_code, $file, $download = true) {
		$Model = ClassRegistry::init($model);
		$filePath = $Model->brwConfig['files'][$category_code]['path']
			. DS . $model . DS . $idRecord . DS . $file;

		$isPublic = (substr($file, 0, strlen(WWW_ROOT)) === WWW_ROOT);
		if (!$isPublic and !$this->Session->check('Auth.BrwUser')) {
			$this->response->statusCode('404');
		}

		$this->view = 'Media';
		$pathinfo = pathinfo($filePath);
		$params = array(
			'id' => $pathinfo['basename'],
			'name' => $pathinfo['filename'],
			'extension' => $pathinfo['extension'],
			'download' => $download,
			//'mimeType' => array($pathinfo['extension'] => 'application/' . $pathinfo['extension']),
			'path' => $pathinfo['dirname'] . DS
		);
		$this->set($params);
	}


}