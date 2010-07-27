<?php
class SitesController extends BrownieAppController {

	var $name = 'Sites';
	var $uses = array();

	function select() {
		if (!empty($this->data['Site']['id'])) {
			$this->Session->write('BrwSite', $this->Site->read(null, $this->data['Site']['id']));
		} else {
			$this->Session->delete('BrwSite');
		}
		$this->redirect(array('controller' => 'brownie', 'action' => 'index'));
	}

}