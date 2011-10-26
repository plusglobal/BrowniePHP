<?php

App::import('Component', 'Auth');

class MyAuthComponent extends AuthComponent {


    function identify($user = null, $conditions = null) {
		$models = Configure::read('brwSettings.userModels');
        foreach ($models as $model) {
            $this->userModel = $model;
            $this->params['data'][$model] = $this->params['data']['BrwUser'];
            $result = parent::identify($this->params['data'][$model], $conditions);
            if ($result) {
            	$this->Session->write('authModel', $model);
                return $result;
            }
        }
        return null; // login failure
    }


}