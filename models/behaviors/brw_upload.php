<?php

class BrwUploadBehavior extends ModelBehavior {

	var $max_upload_size = 0;
	var $extensions = array('png', 'jpg', 'gif', 'jpeg');
	var $excluded_extensions = array('php');

	function setup($Model, $config = array()) {
		$this->max_upload_size = 50 * 1024 * 1024;
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
			$Model->data[$Model->alias]['name'] = $this->_cleanFileName($Model->data[$Model->alias]['name']);
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
			$data = $Model->data[$Model->alias];
			$uploadType = ($Model->alias == 'BrwImage') ? 'Images' : 'Files';
			$uploadsFolder = Configure::read('brwSettings.' . $uploadType . '.' . $data['model'] . '.' . $data['category_code'] . '.folder');
			$uploadsPath = Configure::read('brwSettings.' . $uploadType . '.' . $data['model'] . '.' . $data['category_code'] . '.path');
			if (empty($uploadsFolder) or empty($uploadsPath)) {
				$RelModel = ClassRegistry::init($data['model']);
				$uploadsFolder = $RelModel->brwConfig['images'][$data['category_code']]['folder'];
				$uploadsPath = $RelModel->brwConfig['images'][$data['category_code']]['path'];
			}
			$model = $data['model'];
			$source = $data['file'];
			$dest_dir = $uploadsPath . $uploadsFolder . DS . $model . DS . $data['record_id'];
			$dest = $dest_dir . DS . $data['name'];
			$updating = !empty($data['id']);
			if ($updating and $file_changed) {
				$this->_deleteFiles($uploadsPath, $uploadsFolder, $model, $data['record_id'], $Model->data['name_prev']);
			}
			if (!is_dir($dest_dir)) {
				if (!mkdir($dest_dir, 0777, true)) {
					$this->log('BrowniePHP: unable to create dir ' . $dest_dir);
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
		$upload = $Model->read();
		$upload = array_shift($upload);
		$uploadType = ($Model->alias == 'BrwImage') ? 'Images' : 'Files';
		$uploadsFolder = Configure::read('brwSettings.' . $uploadType . '.' . $upload['model'] . '.' . $upload['category_code'] . '.folder');
		$uploadsPath = Configure::read('brwSettings.' . $uploadType . '.' . $upload['model'] . '.' . $upload['category_code'] . '.path');
		$this->_deleteFiles($uploadsPath, $uploadsFolder, $upload['model'], $upload['record_id'], $upload['name']);
	}

	function _deleteFiles($uploadsPath, $uploadsFolder, $model, $record, $filename) {
		$baseFilePath = $uploadsPath . $uploadsFolder . DS . $model . DS . $record;
		$filePath = $baseFilePath . DS . $filename;
		if (is_file($filePath)) {
			unlink($filePath);
		}
		if (is_dir($baseFilePath)) {
			if (count(scandir($baseFilePath)) <= 2) {
				rmdir($baseFilePath);
			}
		}
		$baseCacheDir = $uploadsPath . $uploadsFolder . DS . 'thumbs' . DS . $model;
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


	function _cleanFileName($filename) {
		$parts = explode('.', $filename);
		foreach ($parts as $key => $part) {
			$parts[$key] = Inflector::slug($part, '-');
		}
		return join('.', $parts);
	}

	function createResizedVersions($Model, $model, $recordId, $sizes, $category_code, $file) {
		$RelModel = ClassRegistry::init($model);
		$uploadsFolder = $RelModel->brwConfig['images'][$category_code]['folder'];
		$uploadsPath = $RelModel->brwConfig['images'][$category_code]['path'];
		$sourceFile = $uploadsPath . $uploadsFolder . DS . $model . DS . $recordId . DS . $file;
		if (!file_exists($sourceFile)) {
			return false;
		}
		$pathinfo = pathinfo($sourceFile);
		App::import('Vendor', 'Brownie.resizeimage');
		$format = $pathinfo['extension'];
		$cacheDir = $uploadsPath . $uploadsFolder . DS . 'thumbs';
		$destDir = $cacheDir . DS . $model . DS . $sizes. DS . $recordId;
		if (!is_dir($destDir)) {
			if (!mkdir($destDir, 0755, true)) {
				$this->log('cant create dir on ' . __FILE__ . ' line ' . __LINE__);
			}
		}
		$cachedFile = $destDir . DS . $file;
		if (!is_file($cachedFile)) {
			ini_set('memory_limit', '128M');
			copy($sourceFile, $cachedFile);
			resizeImage($cachedFile, $sizes);
		}
		return $cachedFile;
	}

}