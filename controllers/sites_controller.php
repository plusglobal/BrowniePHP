<?php
class SitesController extends BrownieAppController {

	var $name = 'Sites';
	var $uses = array();

	function select() {
		$siteId = null;
		if (!empty($this->data['Site']['id'])) {
			$siteId = $this->data['Site']['id'];
		} else if(!empty($this->params['named']['site_id'])) {
			$siteId = $this->params['named']['site_id'];
		}

		if ($siteId) {
			$this->Session->write('BrwSite', $this->Site->read(null, $siteId));
		} else {
			$this->Session->delete('BrwSite');
		}
		$this->redirect(array('controller' => 'brownie', 'action' => 'index'));
	}

}