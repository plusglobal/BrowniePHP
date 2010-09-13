<?php
/**
* based on http://bakery.cakephp.org/articles/view/thumbnails-generation-with-phpthumb
*/
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
	* w-200 ancho maximo
	* h-300 alto maximo
	*/
	function view($model, $recordId, $sizes, $file) {
		$sourceFile = WWW_ROOT . DS . 'uploads' . DS . $model . DS . $recordId . DS . $file;
		if (!is_file($sourceFile) or !$this->_checkValidSizes($model, $sizes)) {
			$this->cakeError('error404');
		}
		$pathinfo = pathinfo($sourceFile);
		App::import('Vendor', 'Brownie.phpThumb', array('file' => 'phpThumb' . DS . 'phpthumb.class.php'));
		$phpThumb = new phpthumb();
		$phpThumb->src = $sourceFile;
		$phpThumb->q = '95';
		$phpThumb->config_output_format = $pathinfo['extension'];
		$phpThumb->config_error_die_on_error = true;
		$phpThumb->config_temp_directory = ROOT . DS . APP_DIR . DS . 'tmp';
		$phpThumb->config_cache_directory = WWW_ROOT . DS . 'uploads' . DS . 'thumbs';
		$phpThumb->config_cache_disable_warning = true;
		$uploadDir = $phpThumb->config_cache_directory . DS . $model . DS . $sizes. DS . $recordId;
		if (!is_dir($uploadDir)) {
			if (!mkdir($uploadDir, 0755, true)) {
				$this->log('cant create dir on ' . __FILE__ . ' line ' . __LINE__);
			}
		}
		$phpThumb->cache_filename = $uploadDir . DS . $file;
		$sizes = $this->_sizes($sizes, $phpThumb);

		if (!is_file($phpThumb->cache_filename)) {
			ini_set('memory_limit', '128M');
			if ($phpThumb->GenerateThumbnail()) {
				$phpThumb->RenderToFile($phpThumb->cache_filename);
				chmod($phpThumb->cache_filename, 0755);
			} else {
				die('Failed: '.$phpThumb->error);
			}
		}

		if (is_file($phpThumb->cache_filename)) {
			$cachedImage = getimagesize($phpThumb->cache_filename);
			header('Content-Type: '.$cachedImage['mime']);
			readfile($phpThumb->cache_filename);
			exit;
		}

    }

	function generate() {
		$args = func_get_args();
		$sizes = array_shift($args);
		$sourceFile = implode('/', $args);
		if (substr($sourceFile, 0, 8) != 'uploads/' or !is_file($sourceFile)) {
			$this->cakeError('error404');
		}
		$pathinfo = pathinfo($sourceFile);
		App::import('Vendor', 'Brownie.phpThumb', array('file' => 'phpThumb' . DS . 'phpthumb.class.php'));
		$phpThumb = new phpthumb();
		$phpThumb->src = $sourceFile;
		$phpThumb->q = '95';
		$phpThumb->config_output_format = $pathinfo['extension'];
		$phpThumb->config_error_die_on_error = true;
		$phpThumb->config_temp_directory = ROOT . DS . APP_DIR . DS . 'tmp';
		$sizes = $this->_sizes($sizes, $phpThumb);
		$phpThumb->GenerateThumbnail();
		$phpThumb->OutputThumbnail();
	}


	function _sizes($sizes, $phpThumb) {

		$s = explode('x', $sizes);
    	if(count($s == 2) and ctype_digit($s[0]) and ctype_digit($s[1])) {
			$phpThumb->w = $s[0];
			$phpThumb->h = $s[1];
			$phpThumb->zc = 'C';
			return;
		}

		$s = explode('_', $sizes);
		if (count($s == 2) and ctype_digit($s[0]) and ctype_digit($s[1])) {
			$phpThumb->w = $s[0];
			$phpThumb->h = $s[1];
			return;
		}

		if (strstr($sizes, '-')) {
			$type = $sizes[0];
			$s = substr($sizes, 2);
			if ($type == 'w') {
				$phpThumb->w = $s;
			} elseif($type == 'h') {
				$phpThumb->h = $s;
			}
			return;
		}
    }

    function _checkValidSizes($model, $sizes) {
    	return true;

		$Model = ClassRegistry::init($model);
		$Model->Behaviors->attach('Brownie.Cms');
		foreach ($Model->brownieCmsConfig['images'] as $imageCategory) {
			if (in_array($sizes, $imageCategory['sizes'])) {
				return true;
			}
		}
		return false;
    }


}

