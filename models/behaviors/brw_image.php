<?php

class BrwImageBehavior extends ModelBehavior {

	var $max_upload_size = 0;
	var $extensions = array('png', 'jpg', 'gif', 'jpeg');
	var $excluded_extensions = array('php');

	function setup($BrwImage, $config = array()) {
		$this->max_upload_size = 5 * 1024 * 1024;
	}


	function beforeValidate(&$BrwImage) {
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
					'message' => __d('brownie', 'Invalid image. Only jpg, gif and png is allowed.', true),
				),
				/*
				'upload_ok' => array (
					'rule' => array('validateUploadedFile'),
					'message' => __d('brownie', 'Error in the upload, please try again.', true),
				),*/

			),
		);

		$BrwImage->data['BrwImage']['name'] = null;
		if (
			is_array($BrwImage->data['BrwImage']['file'])
			and !empty($BrwImage->data['BrwImage']['file']['tmp_name'])
			and empty($BrwImage->data['BrwImage']['file']['error'])
		) {
			$BrwImage->data['BrwImage']['name'] = $BrwImage->data['BrwImage']['file']['name'];
			$BrwImage->data['BrwImage']['file'] = $BrwImage->data['BrwImage']['file']['tmp_name'];
		} elseif(is_string($data['file'])) {
			$fileInfo = pathinfo($data['file']);
			$BrwImage->data['BrwImage']['name'] = $data['file']['name'] = $fileInfo['filename'];
		}
	}

	function beforeSave($BrwImage) {
		pr($BrwImage->data);
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

		//$this->_cleanImages($dest_dir, $BrwImage->id);

		echo $dest =  $dest_dir . '/' . $BrwImage->data['BrwImage']['name'];
		copy($source, $dest);
		chmod($dest, 0777);
	}


	function beforeDelete($BrwImage) {
		$image = array_shift($BrwImage->findById($BrwImage->id));
		$dest_dir = 'uploads/' . $image['model'] . '/' . $image['record_id'] ;
		$this->_cleanImages($dest_dir, $BrwImage->id);
		return true;
	}

	function _cleanImages($dest_dir, $id_image) {
		$handle = opendir($dest_dir);
		while($file = readdir($handle)){
			if(strstr($file, $id_image)){
				unlink($dest_dir . DS . $file);
			}
		}
	}

	/*
	function afterDelete($BrwImage, $data, $created) {
		pr($data);
	}
	*/

	/*
	Validate functions
	*/

	/*
	function validateUploadedFile($BrwImage, $data)
	{
		$upload_info = array_shift($data);
		if ($upload_info['error'] == 4) {
			//the image was not uploaded and it isn't required
			return true;
		}

		if ($upload_info['size'] == 0) {
			return false;
		}

		if ($upload_info['error'] !== 0) {
			return false;
		}

		$extension = '.' . strtolower(end(explode('.', $upload_info['name'])));
		if(!empty($this->extension)){
			if(!in_array($extension, $this->extension)){
				return false;
			}
		}
		if(!empty($this->excluded_extensions)){
			if(in_array($extension, $this->excluded_extensions)){
				return false;
			}
		}

		return is_uploaded_file($upload_info['tmp_name']);

	}*/


	function validateSizeFile($BrwImage, $data) {
		if(filesize($BrwImage->data['BrwImage']['file']) > $this->max_upload_size){
			return false;
		} else {
			return true;
		}
	}


	function validateImageFile($BrwImage, $data) {
		return getimagesize($BrwImage->data['BrwImage']['file']);
	}


}