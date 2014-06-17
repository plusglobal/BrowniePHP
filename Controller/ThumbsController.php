<?php

class ThumbsController extends BrownieAppController{

	public $uses = array();
	public $autoRender = false;


	public function beforeFilter() {
		$this->Auth->allow();
	}


	public function view($model = '', $recordId = '', $sizes = '', $category_code = '', $file = '') {
		$model_list = array_flip(App::objects('model'));
  		if (!isset($model_list[$model])) {
  			throw new NotFoundException();
  		}

		$BrwImage = ClassRegistry::init('BrwImage');
		$cachedFile = $BrwImage->resizedVersions($model, $recordId, $sizes, $category_code, $file);
		if (is_file($cachedFile)) {
			$isPublic = (substr($cachedFile, 0, strlen(WWW_ROOT)) === WWW_ROOT);
			if (!$isPublic and !AuthComponent::user()) {
				$this->response->statusCode('404');
			}
			$cachedImage = getimagesize($cachedFile);
			header('Content-Type: ' . $cachedImage['mime']);
			readfile($cachedFile);
			$Model = ClassRegistry::init($model);
			if (method_exists($Model, 'brwAfterThumbnail')) {
				$Model->brwAfterThumbnail($recordId, $sizes, $category_code, $file);
			}
			exit;
		}
    }


	public function _sizes($sizes) {
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