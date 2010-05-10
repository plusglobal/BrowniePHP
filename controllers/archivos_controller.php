<?php
class ArchivosController extends BrownieAppController {

	var $name = 'Archivos';
	var $FileModel;

	function beforeFilter() {
		$this->FileModel = ClassRegistry::init('BrwFile');
		parent::beforeFilter();
	}

	function delete($id = null) {
		if($this->FileModel->delete($id)) {
			$this->Session->setFlash(__d('brownie', 'The file was deleted', true));
		} else {
			$this->Session->setFlash(__d('brownie', 'The file couldn\'t be deleted', true));
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