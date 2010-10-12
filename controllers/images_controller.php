<?php

class ImagesController extends BrownieAppController {

	var $name = 'Images';

	function delete($id = null) {
		if (ClassRegistry::init('BrwImage')->delete($id)) {
			$this->Session->setFlash(__d('brownie', 'The image was deleted', true), 'flash_success');
		} else {
			$this->Session->setFlash(__d('brownie', 'The image couldn\'t be deleted', true), 'flash_error');
		}

		if (env('HTTP_REFERER')) {
			$redirecTo = env('HTTP_REFERER');
		} else {
			$redirecTo = array('controller' => 'brownie', 'action' => 'index', 'plugin' => 'brownie');
		}

		$this->redirect($redirecTo);

	}

}