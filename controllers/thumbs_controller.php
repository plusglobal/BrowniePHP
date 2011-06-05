<?php

class ThumbsController extends BrownieAppController{

	var $name = 'Thumbs';
	var $uses = array();
	var $autoRender = false;

	function beforeFilter() {
		$this->Auth->allow('*');
	}

	/**
	* 150x113 recorta, si es necesario agranda la imagen
	* 200_900 no recorta, no agranda
	*/
	function view($model = '', $recordId = '', $sizes = '', $category_code = '', $file = '') {
		$BrwImage = ClassRegistry::init('BrwImage');
		$cachedFile = $BrwImage->createResizedVersions($model, $recordId, $sizes, $category_code, $file);
		if (is_file($cachedFile)) {
			$isPublic = (substr($cachedFile, 0, strlen(WWW_ROOT)) === WWW_ROOT);
			if (!$isPublic and !$this->Session->check('Auth.BrwUser')) {
				$this->cakeError('error404');
			}
			$cachedImage = getimagesize($cachedFile);
			header('Content-Type: '.$cachedImage['mime']);
			readfile($cachedFile);
			exit;
		}

    }

	function _sizes($sizes) {

		$r_sizes = array();

		$s = explode('x', $sizes);
    	if(count($s == 2) and ctype_digit($s[0]) and ctype_digit($s[1])) {
    		$r_sizes = array('w' => $s[0], 'h' => $s[1], 'crop' => 'resizeCrop');
		} else {
			$s = explode('_', $sizes);
			if (count($s == 2) and ctype_digit($s[0]) and ctype_digit($s[1])) {
	    		$r_sizes = array('w' => $s[0], 'h' => $s[1], 'crop' => 'resize');
			}
		}

		return $r_sizes;
    }

}