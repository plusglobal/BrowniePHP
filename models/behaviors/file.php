<?php

class FileBehavior extends ModelBehavior {

	var $max_upload_size = 0;
	var $excluded_extensions = array('php');

	function setup($Model, $config = array()) {
		$this->max_upload_size = 5 * 1024 * 1024;
	}


	function beforeValidate(&$Model) {
		$kB = round($this->max_upload_size / 1024, 2);
		$mB = round($this->max_upload_size / (1024 * 1024), 2);
		$Model->validate = array (
			'file' => array (
				'valid_size' => array(
					'rule' => array('validateSizeFile'),
					'message' => sprintf(__d('brownie', 'File too heavy. Maximum allowed: %s KB (%s MB)', true), $kB, $mB)
				),
				'upload_ok' => array (
					'rule' => array('validateUploadedFile'),
					'message' => __d('brownie', 'Error in the upload, please try again.', true),
				),

			),
		);
	}


	function afterFind($Model, $results, $primary) {
		//if($Model->)
		//return $this->_addImagePaths($Model, $results);
	}


	function beforeSave($Model) {
		$data = $Model->data['BrwFile'];

		if($data['file']['error'] == 4){
			if(empty($data['id'])){
				return false;
			} else {
				return true;
			}
		}

		$add_data = array(
			'name' => $data['file']['name'],
		);

		$data = Set::merge($data, $add_data);

		/*
		if(!is_array($Model->data['BrwImage'])){
			$Model->data['BrwImage'] = $this->formatBeforeSave($Model->data['BrwImage']);
		} else {
			foreach($Model->data['BrwImage'] as $key => $value) {
				$Model->data[$key] = $this->formatBeforeSave($value);
			}
		}
		unset($Model->data['Content']);
		$Model->data['BrwImage'] = $Model->data;
		/**/
		$Model->data['BrwFile'] = $data;

		return $Model->beforeSave();
	}

	function afterSave($Model, $created) {

		if(!empty($Model->data['BrwFile']['file']['tmp_name'])){

			$model = $Model->data['BrwFile']['model'];
			$source = $Model->data['BrwFile']['file']['tmp_name'];

			$dest_model_dir = 'uploads/' . $model;

			if(!is_dir($dest_model_dir)){
				if(!mkdir($dest_model_dir, 0777)) {
					$Model->log('Brownie CMS: unable to create dir ' . $dest_model_dir);
				} else {
					chmod($dest_model_dir, 0777);
				}
			}

			$dest_dir = $dest_model_dir . '/' . $Model->data['BrwFile']['record_id'];
			if(!is_dir($dest_dir)){
				if(!mkdir($dest_dir, 0777)){
					$this->log('Brownie CMS: unable to create dir ' . $dest_dir);
				} else {
					chmod($dest_dir, 0777);
				}
			}

			//falta eliminar imagenes al actualizar

			//$this->_cleanImages($dest_dir, $Model->id);

			$dest =  $dest_dir . '/' . $Model->data['BrwFile']['name'];
			move_uploaded_file($source, $dest);
			chmod($dest, 0777);

		} else {
			$this->log('Brownie CMS: trying to save image without uploaded file');
		}

		return $Model->afterSave($created);
	}


	function beforeDelete($Model) {
		$file = array_shift($Model->findById($Model->id));
		unlink('uploads/' . $file['model'] . '/' . $file['record_id'] . '/' . $file['name']);
		return true;
	}


	/*
	function afterDelete($Model, $data, $created) {
		pr($data);
	}
	*/

	/*
	Validate functions
	*/

	function validateUploadedFile($Model, $data)
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

	}


	function validateSizeFile($Model, $data)
	{
		$upload_info = array_shift($data);
		if(!empty($upload_info['tmp_name'])){
			if($upload_info['size'] > $this->max_upload_size){
				return false;
			} else {
				return true;
			}
		} else {
			return true;
		}
	}



}