<?php

class UploadController extends BrownieAppController {

	public $uses = array('BrwImage');


	function upload($model, $uploadModel, $record_id, $category_code) {
		if (!empty($_FILES)) {
			$this->autoRender = false;
			$result = ClassRegistry::init($uploadModel)->save(array(
				'id' => null,
				'model' => $model,
				'record_id' => $record_id,
				'category_code' => $category_code,
				'file' => $_FILES['file']
			));
			$this->log('file');
			$this->log($_FILES);
			$this->log($result);
		} else {
			$this->layout = 'empty';
			$this->set(compact('model', 'record_id', 'category_code', 'uploadModel'));
		}
	}

}