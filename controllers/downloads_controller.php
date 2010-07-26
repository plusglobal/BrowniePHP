<?php

class DownloadsController extends BrownieAppController {

	var $name = 'Downloads';
	var $uses = array();

	function beforeFilter() {
		$this->Auth->allow('*');
	}

	function get($model, $idRecord, $file) {
		//Configure::write('debug', 0);
		$this->view = 'Media';
		$pathinfo = pathinfo($file);
		$params = array(
			'id' => $file,
			'name' => $pathinfo['filename'],
			'extension' => $pathinfo['extension'],
			'download' => true,
			'mimeType' => array($pathinfo['extension'] => 'application/' . $pathinfo['extension']),
			'path' => ROOT . DS . WEBROOT_DIR . DS . 'uploads' . DS . $model . DS . $idRecord . DS
		);
		$this->set($params);
	}

}