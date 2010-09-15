<?php

class ImagesController extends BrownieAppController {

	var $name = 'Images';

	function delete($id = null) {
		$BrwImage = ClassRegistry::init('BrwImage');
		if($BrwImage->delete($id)) {
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