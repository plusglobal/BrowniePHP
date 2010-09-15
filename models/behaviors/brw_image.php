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
		if (is_array($BrwImage->data['BrwImage']['file'])) {
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
			if ($BrwImage->data['BrwImage']['file'][0] == '/') {
				$BrwImage->data['BrwImage']['file'] = substr($BrwImage->data['BrwImage']['file'], 1);
			}
		}
	}

	function beforeSave($BrwImage) {
		$updating = !empty($BrwImage->data['BrwImage']['id']);
		$file_changed = !empty($BrwImage->data['BrwImage']['file']);
		if ($updating) {
			if($file_changed) {
				$image = array_shift($BrwImage->findById($BrwImage->id));
				$BrwImage->data['name_prev'] = $image['name'];
			} else {
				unset($BrwImage->data['BrwImage']['name']);
				return true;
			}
		}
		if (empty($BrwImage->data['BrwImage']['name'])) {
			return false;
		}
	}

	function afterSave($BrwImage, $created) {

		$file_changed = !empty($BrwImage->data['BrwImage']['file']);
		if ($file_changed) {
			$model = $BrwImage->data['BrwImage']['model'];
			$source = $BrwImage->data['BrwImage']['file'];
			$dest_dir = 'uploads/' . $model . '/' . $BrwImage->data['BrwImage']['record_id'];
			if (!is_dir($dest_dir)) {
				if (!mkdir($dest_dir, 0777, true)) {
					$this->log('Brownie CMS: unable to create dir ' . $dest_dir);
				} else {
					chmod($dest_dir, 0777);
				}
			}
			$dest =  $dest_dir . '/' . $BrwImage->data['BrwImage']['name'];
			$updating = !empty($BrwImage->data['BrwImage']['id']);
			if ($updating and $file_changed) {
				$this->_deleteFiles($model, $BrwImage->data['BrwImage']['record_id'], $BrwImage->data['name_prev']);
			}
			if ($this->_copy($BrwImage, $source, $dest)) {
				chmod($dest, 0777);
			}
		}
	}

	function _copy($BrwImage, $source, $dest) {
		$newDest = $dest;
		while (is_file($newDest)) {
			$parts = explode('/', $newDest);
			$file = '_' . array_pop($parts);
			$newDest = join('/', $parts) . '/' . $file;
		}
		if (copy($source, $newDest)) {
			if($newDest != $dest) {
				return $BrwImage->save(array('id' => $BrwImage->id, 'name' => $file), array('callbacks' => false, 'validate' => false));
			} else {
				return true;
			}
		} else {
			return false;
		}
	}

	function beforeDelete($BrwImage) {
		$image = $BrwImage->read();
		$image = array_shift($image);
		$this->_deleteFiles($image['model'], $image['record_id'], $image['name']);
	}




	function _deleteFiles($model, $record, $filename) {
		$filePath = WWW_ROOT . 'uploads' . DS . $model . DS . $record . DS . $filename;
		if(is_file($filePath)) {
			unlink($filePath);
		}
		$baseCacheDir = WWW_ROOT . 'uploads' . DS . 'thumbs' . DS . $model;
		if(is_dir($baseCacheDir)) {
			$handle = opendir($baseCacheDir);
			while ($sizeDir = readdir($handle)) {
				if(is_dir($baseCacheDir . DS . $sizeDir)) {
					$fileToDelete = $baseCacheDir . DS . $sizeDir . DS . $record . DS . $filename;
					if (is_file($fileToDelete)) {
						unlink($fileToDelete);
					}
				}
			}
		}
	}


	function validateSizeFile($BrwImage, $data) {
		if (empty($BrwImage->data['BrwImage']['file'])) {
			return true;
		}

		if (substr($BrwImage->data['BrwImage']['file'], 0, 7) == 'http://') {
			$filesize = 0;
		} else {
			$filesize = filesize($BrwImage->data['BrwImage']['file']);
		}

		if ($filesize > $this->max_upload_size) {
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