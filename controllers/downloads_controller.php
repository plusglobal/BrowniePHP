<?php

class DownloadsController extends BrownieAppController {

	var $name = 'Downloads';
	var $uses = array();

	function beforeFilter() {
		$this->Auth->allow('*');
	}

	function get($model, $idRecord, $category_code, $file) {
		$Model = ClassRegistry::init($model);
		$uploadsFolder = $Model->brwConfig['files'][$category_code]['folder'];
		$this->view = 'Media';
		$pathinfo = pathinfo($file);
		$params = array(
			'id' => $file,
			'name' => $pathinfo['filename'],
			'extension' => $pathinfo['extension'],
			'download' => true,
			'mimeType' => array($pathinfo['extension'] => 'application/' . $pathinfo['extension']),
			'path' => ROOT . DS . WEBROOT_DIR . DS . $uploadsFolder . DS . $model . DS . $idRecord . DS
		);
		$this->set($params);
	}

}