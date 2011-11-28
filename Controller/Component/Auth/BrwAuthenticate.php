<?php
App::uses('FormAuthenticate', 'Controller/Component/Auth');

class BrwAuthenticate extends FormAuthenticate {

	public function authenticate(CakeRequest $request, CakeResponse $response) {
		$userModels = array('Author', 'BrwUser');
		foreach ($userModels as $userModel) {
			$this->settings['userModel'] = $userModel;
			$request->data[$userModel] = $request->data['BrwUser'];
	        $authenticated = parent::authenticate($request, $response);
	        if ($authenticated) {
				return array_merge($authenticated, array('model' => $userModel));
			}
		}
		return false;
    }

}