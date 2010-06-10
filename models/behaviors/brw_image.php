<?php

class BrwImageBehavior extends ModelBehavior {

	var $max_upload_size = 0;
	var $extensions = array('png', 'jpg', 'gif', 'jpeg');
	var $excluded_extensions = array('php');

	function setup($BrwImage, $config = array()) {
		$this->max_upload_size = 5 * 1024 * 1024;
	}


	function beforeValidate($BrwImage) {
		$kB = round($this->max_upload_size / 1024, 2);
		$mB = round($this->max_upload_size / (1024 * 1024), 2);
		$BrwImage->validate = array (
			'file' => array (
				'valid_size' => array(
					'rule' => array('validateSizeFile'),
					'message' => sprintf(__d('brownie', 'File too heavy. Maximum allowed: %s KB (%s MB)', true), $kB, $mB)
				),
				'valid_image' => array(
					'rule' => array('validateImageFile'),
					'message' => __d('brownie', 'Invalid image. Only jpg, gif and png are allowed.', true),
				),
			),
		);

		$BrwImage->data['BrwImage']['name'] = null;
		if (isset($BrwImage->data['BrwImage']['file']['tmp_name'])) {
			//the image was uploaded
			switch ($BrwImage->data['BrwImage']['file']['error']) {
				case 0:
					$BrwImage->data['BrwImage']['name'] = $BrwImage->data['BrwImage']['file']['name'];
					$BrwImage->data['BrwImage']['file'] = $BrwImage->data['BrwImage']['file']['tmp_name'];
				break;
				case 4:
					$BrwImage->data['BrwImage']['file'] = '';
				break;
			}
		} elseif(is_string($BrwImage->data['BrwImage']['file'])) {
			$BrwImage->data['BrwImage']['name'] = end(explode('/', $BrwImage->data['BrwImage']['file']));
		}
		//pr($BrwImage->data);
	}

	function beforeSave($BrwImage) {
		$updating = !empty($BrwImage->data['BrwImage']['id']);
		if ($updating) {
			$image = array_shift($BrwImage->findById($BrwImage->id));
			echo $BrwImage->data['name_prev'] = $image['name'];
		}

		if (empty($BrwImage->data['BrwImage']['name'])) {
			return false;
		}
	}

	function afterSave($BrwImage, $created) {
		pr($BrwImage->data);
		$model = $BrwImage->data['BrwImage']['model'];
		$source = $BrwImage->data['BrwImage']['file'];
		$dest_model_dir = 'uploads/' . $model;
		if (!is_dir($dest_model_dir)) {
			if (!mkdir($dest_model_dir, 0777)) {
				$BrwImage->log('Brownie CMS: unable to create dir ' . $dest_model_dir);
			} else {
				chmod($dest_model_dir, 0777);
			}
		}
		$dest_dir = $dest_model_dir . '/' . $BrwImage->data['BrwImage']['record_id'];
		if (!is_dir($dest_dir)) {
			if(!mkdir($dest_dir, 0777)){
				$this->log('Brownie CMS: unable to create dir ' . $dest_dir);
			} else {
				chmod($dest_dir, 0777);
			}
		}
		//eliminar cache al actualizar
		$dest =  $dest_dir . '/' . $BrwImage->data['BrwImage']['name'];
		if(copy($source, $dest)){
			chmod($dest, 0777);
			$updating = !empty($BrwImage->data['BrwImage']['id']);
			$file_changed = !empty($BrwImage->data['BrwImage']['file']);
			if ($updating and $file_changed) {
				$this->_deleteFiles($model, $BrwImage->data['BrwImage']['record_id'], $BrwImage->data['name_prev']);
			}
		}

	}


	function beforeDelete($BrwImage) {
		$image = array_shift($BrwImage->findById($BrwImage->id));
		$this->_deleteFiles($image['model'], $image['record_id'], $image['name']);
	}


	function _deleteFiles($model, $record, $filename) {
		$filePath = WWW_ROOT . 'uploads' . DS . $model . DS . $record . DS . $filename;
		unlink($filePath);
		$baseCacheDir = WWW_ROOT . 'uploads' . DS . 'thumbs' . DS . $model;
		if(is_dir($baseCacheDir)) {
			$handle = opendir($baseCacheDir);
			while ($sizeDir = readdir($handle)) {
				if(is_dir($baseCacheDir . DS . $sizeDir)) {
					$fileToDelete = $baseCacheDir . DS . $sizeDir . DS . $record . DS . $filename;
					if (file_exists($fileToDelete)) {
						unlink($fileToDelete);
					}
				}
			}
		}
	}


	function validateSizeFile($BrwImage, $data) {
		//pr($BrwImage->data['BrwImage']);
		if (empty($BrwImage->data['BrwImage']['file'])) {
			return true;
		}

		if(filesize($BrwImage->data['BrwImage']['file']) > $this->max_upload_size){
			return false;
		} else {
			return true;
		}
	}


	function validateImageFile($BrwImage, $data) {
		if (empty($BrwImage->data['BrwImage']['file'])) {
			return true;
		}

		return getimagesize($BrwImage->data['BrwImage']['file']);
	}


}