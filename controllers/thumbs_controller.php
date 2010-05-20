<?php
/**
* based on http://bakery.cakephp.org/articles/view/thumbnails-generation-with-phpthumb
*/
class ThumbsController extends AppController{
	var $name = 'Thumbs';
	var $uses = array();
	var $autoRender = false;

	function view($model, $recordId, $sizes, $file) {
		/*
		150x113 recorta, si es necesario agranda la imagen
		200_900 no recorta, no agranda
		w-200 ancho maximo
		h-300 alto maximo
		*/
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
		$phpThumb->config_cache_directory = ROOT . DS . APP_DIR . DS . 'tmp' . DS . 'cache' . DS . 'thumbs' . DS;
		$phpThumb->config_cache_disable_warning = true;
		$phpThumb->cache_filename = $phpThumb->config_cache_directory . DS . $model . DS
			. $recordId . DS . Security::hash($model . $recordId . $sizes . $file) . '.' . $pathinfo['extension'];

		if (!is_dir($phpThumb->config_cache_directory)) {
			mkdir($phpThumb->config_cache_directory);
		}
		if (!is_dir($phpThumb->config_cache_directory . DS . $model)) {
			mkdir($phpThumb->config_cache_directory . DS . $model);
		}
		if (!is_dir($phpThumb->config_cache_directory . DS . $model . DS . $recordId)) {
			mkdir($phpThumb->config_cache_directory . DS . $model . DS . $recordId);
		}

		$sizes = $this->_sizes($sizes, $phpThumb);

		if (!is_file($phpThumb->cache_filename)) {
			if ($phpThumb->GenerateThumbnail()) {
				$phpThumb->RenderToFile($phpThumb->cache_filename);
			} else {
				die('Failed: '.$phpThumb->error);
			}
		}

		if (is_file($phpThumb->cache_filename)) {
			$cachedImage = getimagesize($phpThumb->cache_filename);
			$this->log(convert(memory_get_usage()));
			header('Content-Type: '.$cachedImage['mime']);
			readfile($phpThumb->cache_filename);
			exit;
		}

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



function convert($size)
 {
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
 }
?>