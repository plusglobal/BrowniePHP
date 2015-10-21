<?php

class BrwUploadBehavior extends ModelBehavior {

	public $max_upload_size = 0;
	public $extensions = array('png', 'jpg', 'gif', 'jpeg');
	public $excluded_extensions = array('php');


	public function setup(Model $Model, $config = array()) {
		$this->max_upload_size = 50 * 1024 * 1024;
	}


	public function beforeValidate(Model $Model, $options = array()) {
		$kB = round($this->max_upload_size / 1024, 2);
		$mB = round($this->max_upload_size / (1024 * 1024), 2);
		$validate = array(
			'file' => array(
				'valid_size' => array(
					'rule' => array('validateSizeFile'),
					'message' => __d('brownie', 'File too heavy. Maximum allowed: %s KB (%s MB)', $kB, $mB)
				),
			),
		);
		if ($Model->alias == 'BrwImage') {
			$validate['file']['valid_image'] = array(
				'rule' => array('validateImageFile'),
				'message' => __d('brownie', 'Invalid image. Only jpg, gif and png are allowed.'),
			);
		}
		if (!empty($Model->validate['file'])) {
			$validate['file'] = Hash::merge($validate['file'], $Model->validate['file']);
		}
		$Model->validate['file'] = $validate['file'];

		$Model->data[$Model->alias]['name'] = null;
		if (!empty($Model->data[$Model->alias]['file'])) {
			if (is_array($Model->data[$Model->alias]['file'])) {
				//the image was uploaded
				switch ($Model->data[$Model->alias]['file']['error']) {
					case 0:
						$Model->data[$Model->alias]['name'] = $Model->data[$Model->alias]['file']['name'];
						$Model->data[$Model->alias]['file'] = $Model->data[$Model->alias]['file']['tmp_name'];
					break;
					default:
						$Model->data[$Model->alias]['file'] = '';
					break;
				}
			} elseif(is_string($Model->data[$Model->alias]['file'])) {
				$Model->data[$Model->alias]['name'] = basename($Model->data[$Model->alias]['file']);
				if ($Model->data[$Model->alias]['file'][0] == '/') {
					$Model->data[$Model->alias]['file'] = substr($Model->data[$Model->alias]['file'], 1);
				}
			}
			$Model->data[$Model->alias]['name'] = $this->_cleanFileName($Model->data[$Model->alias]['name']);
		}
		return true;
	}


	public function beforeSave(Model $Model, $options = array()) {
		if (!empty($Model->data[$Model->alias]['description'])) {
			$Model->data[$Model->alias]['description'] = trim($Model->data[$Model->alias]['description']);
		}
		$updating = !empty($Model->data[$Model->alias]['id']);
		$file_changed = !empty($Model->data[$Model->alias]['file']);
		if ($updating) {
			if ($file_changed) {
				$image = $Model->findById($Model->id);
				$image = $image[$Model->alias];
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


	public function afterSave(Model $Model, $created, $options = array()) {
		if (!empty($Model->data[$Model->alias]['file'])) {
			$data = $Model->data[$Model->alias];
			$uploadType = ($Model->alias == 'BrwFile')? 'files' : 'images';
			$uploadsPath = classRegistry::init($data['model'])->brwConfig[$uploadType][$data['category_code']]['path'];
			$dest_dir = $uploadsPath . DS . $data['model'] . DS . $data['record_id'];
			if (!$created) {
				$this->_deleteFiles($uploadsPath, $data['model'], $data['record_id'], $Model->data['name_prev']);
			}
			if (!is_dir($dest_dir)) {
				if (!mkdir($dest_dir, 0777, true)) {
					$this->log('BrowniePHP: unable to create dir ' . $dest_dir);
				} else {
					chmod($dest_dir, 0777);
				}
			}
			$this->_copy($Model, $data['file'], $dest_dir . DS . $data['name']);
		}
	}


	public function _copy($Model, $source, $dest) {
		$newDest = $dest;
		while (is_file($newDest)) {
			$parts = explode(DS, $newDest);
			$file = '_' . array_pop($parts);
			$newDest = join(DS, $parts) . DS . $file;
		}
		if (copy($source, $newDest)) {
			chmod($newDest, 0777);
			if ($newDest != $dest) {
				return $Model->save(array('id' => $Model->id, 'name' => $file), array('callbacks' => false, 'validate' => false));
			} else {
				return true;
			}
		} else {
			return false;
		}
	}


	public function beforeDelete(Model $Model, $cascade = true) {
		$upload = $Model->findById($Model->id);
		$upload = $upload[$Model->alias];
		$uploadType = ($Model->alias == 'BrwImage') ? 'images' : 'files';
		$relModel = ClassRegistry::init($upload['model']);
		$uploadsPath = $relModel->brwConfig[$uploadType][$upload['category_code']]['path'];
		$this->_deleteFiles($uploadsPath, $upload['model'], $upload['record_id'], $upload['name']);
		return true;
	}


	public function _deleteFiles($uploadsPath, $model, $record, $filename) {
		$baseFilePath = $uploadsPath . DS . $model . DS . $record;
		$filePath = $baseFilePath . DS . $filename;
		if (is_file($filePath)) {
			unlink($filePath);
		}
		if (is_dir($baseFilePath)) {
			if (count(scandir($baseFilePath)) <= 2) {
				rmdir($baseFilePath);
			}
		}
		$baseCacheDir = $uploadsPath . DS . 'thumbs' . DS . $model;
		if (is_dir($baseCacheDir)) {
			$handle = opendir($baseCacheDir);
			while ($sizeDir = readdir($handle)) {
				if (is_dir($baseCacheDir . DS . $sizeDir)) {
					$fileToDelete = $baseCacheDir . DS . $sizeDir . DS . $record . DS . $filename;
					if (is_file($fileToDelete)) {
						unlink($fileToDelete);
					}
				}
			}
		}
	}


	public function validateSizeFile($Model, $data) {
		if (empty($Model->data[$Model->alias]['file'])) {
			return true;
		}

		if (in_array(substr($Model->data[$Model->alias]['file'], 0, 7), array('http://', 'https:/'))) {
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


	public function validateImageFile($Model, $data) {
		if (empty($Model->data[$Model->alias]['file'])) {
			return true;
		}
		return getimagesize($Model->data[$Model->alias]['file']);
	}

	public function _cleanFileName($filename) {
		if (strstr($filename, '?')) {
			$filename = explode('?', $filename)[0];
		}
		$info = pathinfo($filename);
		$parts = explode('.', $info['basename']);
		foreach ($parts as $key => $part) {
			$parts[$key] = Inflector::slug($part, '-');
		}
		return join('.', $parts);
	}


	public function resizedVersions($Model, $model, $recordId, $sizes, $category_code, $file) {
		$RelModel = ClassRegistry::init($model);
		$uploadsPath = $RelModel->brwConfig['images'][$category_code]['path'];
		$sourceFile = $uploadsPath . DS . $model . DS . $recordId . DS . $file;
		if (!file_exists($sourceFile)) {
			return false;
		}
		$pathinfo = pathinfo($sourceFile);
		App::import('Vendor', 'Brownie.resizeimage');
		$format = $pathinfo['extension'];
		$cacheDir = $uploadsPath . DS . 'thumbs';
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
			brwResizeImage($cachedFile, $sizes);
		}
		return $cachedFile;
	}


}