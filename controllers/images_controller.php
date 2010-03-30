<?php
class ImagesController extends BrownieAppController {

	var $name = 'Images';
	var $ImageModel;

	function beforeFilter() {
		$this->ImageModel = ClassRegistry::init('BrwImage');
		parent::beforeFilter();
	}

	function delete($id = null) {
		if($this->ImageModel->del($id)) {
			$this->Session->setFlash(__d('brownie', 'The image was deleted', true));
		} else {
			$this->Session->setFlash(__d('brownie', 'The image couldn\'t be deleted', true));
		}

		if(env('HTTP_REFERER')) {
			$redirecTo = env('HTTP_REFERER');
		} else {
			$redirecTo = array('controller' => 'brownie', 'action' => 'index', 'plugin' => 'brownie');
		}

		$this->redirect($redirecTo);

	}

}
?>