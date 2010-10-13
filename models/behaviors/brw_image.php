<?php

class BrwImageBehavior extends ModelBehavior {

	var $max_upload_size = 0;
	var $extensions = array('png', 'jpg', 'gif', 'jpeg');
	var $excluded_extensions = array('php');

	function setup($Model, $config = array()) {
		$this->max_upload_size = 5 * 1024 * 1024;
	}


	function beforeValidate($Model) {
		$kB = round($this->max_upload_size / 1024, 2);
		$mB = round($this->max_upload_size / (1024 * 1024), 2);
		$Model->validate = array (
			'file' => array (
				'valid_size' => array(
					'rule' => array('validateSizeFile'),
					'message' => sprintf(__d('brownie', 'File too heavy. Maximum allowed: %s KB (%s MB)', true), $kB, $mB)
				),
			),
		);

		if ($Model->alias == 'BrwImage') {
			$Model->validate['file']['valid_image'] = array(
				'rule' => array('validateImageFile'),
				'message' => __d('brownie', 'Invalid image. Only jpg, gif and png are allowed.', true),
			);
		}

		$Model->data[$Model->alias]['name'] = null;
		if (!empty($Model->data[$Model->alias]['file'])) {
			if (is_array($Model->data[$Model->alias]['file'])) {
				//the image was uploaded
				switch ($Model->data[$Model->alias]['file']['error']) {
					case 0:
						$Model->data[$Model->alias]['name'] = $Model->data[$Model->alias]['file']['name'];
						$Model->data[$Model->alias]['file'] = $Model->data[$Model->alias]['file']['tmp_name'];
					break;
					case 4:
						$Model->data[$Model->alias]['file'] = '';
					break;
				}
			} elseif(is_string($Model->data[$Model->alias]['file'])) {
				$Model->data[$Model->alias]['name'] = end(explode(DS, $Model->data[$Model->alias]['file']));
				if ($Model->data[$Model->alias]['file'][0] == '/') {
					$Model->data[$Model->alias]['file'] = substr($Model->data[$Model->alias]['file'], 1);
				}
			}
		}
	}

	function beforeSave($Model) {
		$updating = !empty($Model->data[$Model->alias]['id']);
		$file_changed = !empty($Model->data[$Model->alias]['file']);
		if ($updating) {
			if($file_changed) {
				$image = array_shift($Model->findById($Model->id));
				$Model->data['name_prev'] = $image['name'];
			} else {
				unset($Model->data[$Model->alias]['name']);
				return true;
			}
		}
		if (empty($Model->data[$Model->alias]['name'])) {
			return false;
		}
		return true;
	}

	function afterSave($Model, $created) {
		$file_changed = !empty($Model->data[$Model->alias]['file']);
		if ($file_changed) {
			$model = $Model->data[$Model->alias]['model'];
			$source = $Model->data[$Model->alias]['file'];
			$dest_dir = WWW_ROOT . 'uploads' . DS . $model . DS . $Model->data[$Model->alias]['record_id'];
			$dest =  $dest_dir . DS . $Model->data[$Model->alias]['name'];
			$updating = !empty($Model->data[$Model->alias]['id']);
			if ($updating and $file_changed) {
				$this->_deleteFiles($model, $Model->data[$Model->alias]['record_id'], $Model->data['name_prev']);
			}
			if (!is_dir($dest_dir)) {
				if (!mkdir($dest_dir, 0777, true)) {
					$this->log('Brownie CMS: unable to create dir ' . $dest_dir);
				} else {
					chmod($dest_dir, 0777);
				}
			}
			if ($this->_copy($Model, $source, $dest)) {
				chmod($dest, 0777);
			}
		}
	}

	function _copy($Model, $source, $dest) {
		$newDest = $dest;
		while (is_file($newDest)) {
			$parts = explode(DS, $newDest);
			$file = '_' . array_pop($parts);
			$newDest = join(DS, $parts) . DS . $file;
		}
		if (copy($source, $newDest)) {
			if($newDest != $dest) {
				return $Model->save(array('id' => $Model->id, 'name' => $file), array('callbacks' => false, 'validate' => false));
			} else {
				return true;
			}
		} else {
			return false;
		}
	}

	function beforeDelete($Model) {
		$image = $Model->read();
		$image = array_shift($image);
		$this->_deleteFiles($image['model'], $image['record_id'], $image['name']);
	}




	function _deleteFiles($model, $record, $filename) {
		$baseFilePath = WWW_ROOT . 'uploads' . DS . $model . DS . $record;
		$filePath = $baseFilePath . DS . $filename;
		if (is_file($filePath)) {
			unlink($filePath);
		}
		if (is_dir($baseFilePath)) {
			if (count(scandir($baseFilePath)) <= 2) {
				rmdir($baseFilePath);
			}
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


	function validateSizeFile($Model, $data) {
		if (empty($Model->data[$Model->alias]['file'])) {
			return true;
		}

		if (substr($Model->data[$Model->alias]['file'], 0, 7) == 'http://') {
			$filesize = 0;
		} else {
			$filesize = filesize($Model->data[$Model->alias]['file']);
		}

		if ($filesize > $this->max_upload_size) {
			return false;
		} else {
			return true;
		}
	}


	function validateImageFile($Model, $data) {
		if (empty($Model->data[$Model->alias]['file'])) {
			return true;
		}
		return getimagesize($Model->data[$Model->alias]['file']);
	}


}