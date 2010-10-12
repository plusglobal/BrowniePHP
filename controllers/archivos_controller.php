<?php
class ArchivosController extends BrownieAppController {

	var $name = 'Archivos';

	function delete($id) {
		if (ClassRegistry::init('BrwFile')->delete($id)) {
			$this->Session->setFlash(__d('brownie', 'The file was deleted', true), 'flash_success');
		} else {
			$this->Session->setFlash(__d('brownie', 'The file couldn\'t be deleted', true), 'flash_error');
		}

		if (env('HTTP_REFERER')) {
			$redirecTo = env('HTTP_REFERER');
		} else {
			$redirecTo = array('controller' => 'brownie', 'action' => 'index', 'plugin' => 'brownie');
		}

		$this->redirect($redirecTo);

	}

}