<div class="form">
<?php
if (!empty($brwConfig['fields']['conditional'])) {
	$javascript->link(Router::url(array('controller' => 'contents', 'action' => 'js_edit', $model, 'js_edit.js')), false);
}
$javascript->link('/js/fckeditor/fckeditor', false);
$url = array('controller' => 'contents', 'action' => 'edit', $model);
$adding = empty($this->data[$model]['id']);
if(!$adding){
	$url[] = $this->data[$model]['id'];
}
echo $form->create('Content', array('type' => 'file', 'action' => 'edit', 'autocomplete' => 'off', 'url' => $url));
?>
	<fieldset>
 		<legend>
		<?php
		if ($adding){
			$action = __d('brownie', 'Add :name_singular', true);
		} else {
			$action = __d('brownie', 'Edit :name_singular', true);
		}

		echo String::insert($action, array('name_singular' => $brwConfig['names']['singular']));
		?>
		</legend>
		<?php
		$session->flash();

		echo $form->input('model', array('value' => $model, 'type' => 'hidden'));
		foreach ($fields as $key => $value) {
			$params = array();
			//pr($related);
			if (isset($related['belongsTo'][$key])) {
				$params = array('type' => 'select', 'options' => $related['belongsTo'][$key]);
				if ($schema[$key]['null']) {
					$params['empty'] = '- ' . __d('brownie', 'None', true);
				}
				if (!empty($this->params['named'][$key])) {
					$params['selected'] = $this->params['named'][$key];
				}
			} elseif (isset($related['tree'][$key])) {
				if (!empty($related['tree'][$key])) {
					$params = array(
						'type' => 'select',
						'options' => $related['tree'][$key],
						/*
						'after' => $form->input(
							$model . '.' . $key . '_NULL',
							array(
								'label' => __d('brownie', 'No parent', true),
								'type' => 'checkbox'
							)
						),*/
						'empty' => __d('brownie', '(No parent)', true),
					);
					if (!empty($this->params['named'][$key])) {
						$params['selected'] = $this->params['named'][$key];
					}
				} else {
					continue;
				}
			}
			if ($value['type'] == 'date') {
				$params['minYear'] = date('Y') - 200;
				$params['maxYear'] = date('Y') + 200;
				if ($value['null']) {
					$params['empty'] = '-';
				}
			}

			if (strstr($value['type'], 'enum(')) {
				$options = enum2array($value['type']);
				$translatedOptions = array();
				foreach ($options as $field) {
					$translatedOptions[$field] = __($field, true);
				}
				$params = array('type' => 'select', 'options' => $translatedOptions);
			}
			if (!empty($brwConfig['legends'][$key])) {
				$params['after'] = $brwConfig['legends'][$key];
			}

			if (strstr($key, 'password')) {
				$params['type'] = 'password';
			}

			$params['div'] = array('id' => 'brw' . $model . Inflector::camelize($key));
			//pr($params);
			echo $form->input($model . '.' . $key, $params);
			if (in_array($key, $fckFields)){
				echo $fck->load($model . '.' . Inflector::camelize($key), 'Brownie');
			}
		}

		if (!empty($related['hasAndBelongsToMany'])) {
			foreach($related['hasAndBelongsToMany'] as $key => $list){
				if (!empty($list)) {
					$params = array('multiple' => 'checkbox', 'options' => $list);
					if(count($list) > 5) {
						$params['multiple'] = 'multiple';
						$params['size'] = 5;
						$params['class'] = 'combo-select';
						$javascript->link('/brownie/js/jquery.selso', false);
						$javascript->link('/brownie/js/jquery.comboselect', false);
					}
					//pr($params);
					echo $form->input($key . '.' . $key, $params);
				}
			}
		}
		?>
	</fieldset>
<?php
$uploads = array('Image', 'File');
foreach($uploads as $upload){

	$continue = false;
	if ($upload == 'Image' and !empty($brwConfig['images'])){
		$continue = true;
		$uploadConfig = $brwConfig['images'];
	} elseif (!empty($filesConfig)) {
		$continue = true;
		$uploadConfig = $filesConfig;
	}

	if ($continue and $adding) {
		$i=0;
		foreach($uploadConfig as $categoryCode => $uploadCat){
			//pr($image);
			echo '
			<fieldset>
				<legend>' . $uploadCat['name_category'] . '</legend>';

				if ($uploadCat['index']) {
					echo '
					' . $form->input('Brw' . $upload . '.' . $i . '.file', array('type' => 'file', 'label' => __d('brownie', $upload, true))) . '
					' .	$form->input('Brw' . $upload . '.' . $i . '.model', array('value' => $model, 'type' => 'hidden')) . '
					' .	$form->input('Brw' . $upload . '.' . $i . '.category_code', array('value' => $categoryCode, 'type' => 'hidden'));
					if ($uploadCat['description']){
						echo $form->input('Brw' . $upload . '.' . $i . '.description', array('label' => __d('brownie', 'Description', true)));
					}
					if (!$adding){
						echo $form->input('Brw' . $upload . '.' . $i . '.record_id', array('value' => $this->data[$model]['id'], 'type' => 'hidden'));
					}
					$i++;
				} elseif ($adding) {
					echo '
					<div class="comment">' ;
					if ($adding){
						__d('brownie', 'These files are optional. You will be able to add more images later.');
					} else {
						__d('brownie', 'These files will be added among to the rest. If you want to replace images click the "Images" button at the top');
					}
					echo '</div>';
					for($n = 1; $n <= 10; $n++){
						echo $form->input('Brw' . $upload . '.' . $i . '.file', array('type' => 'file', 'label' => sprintf(__d('brownie', 'File %s', true), $n)));
						echo $form->input('Brw' . $upload . '.' . $i . '.model', array('value' => $model, 'type' => 'hidden'));
						echo $form->input('Brw' . $upload . '.' . $i . '.category_code', array('value' => $categoryCode, 'type' => 'hidden'));
						if ($uploadCat['description']){
							echo $form->input('Brw' . $upload . '.' . $i . '.description', array('label' => sprintf(__d('brownie', 'Description %d', true), $n)));
						}
						if (!$adding){
							echo $form->input('Brw' . $upload . '.' . $i . '.record_id', array('value' => $this->data[$model]['id'], 'type' => 'hidden'));
						}
						$i++;
					} // for
				}
			echo '
			</fieldset>';
		}
	}
}


?>

<fieldset>
<?php

if(!empty($this->params['named']['after_save'])) {
	$default = $this->params['named']['after_save'];
} else {
	$default = 'view';
}

echo $form->input('after_save', array(
	'type' => 'select',
	'label' => __d('brownie', 'After save', true),
	'options' => array(
		'continue_editing' => ($brwConfig['names']['gender'] == 1) ?
			sprintf(__d('brownie', 'Continue editing this %s [male]', true), $brwConfig['names']['singular']):
			sprintf(__d('brownie', 'Continue editing this %s [female]', true), $brwConfig['names']['singular'])
		,
		'add_new' =>  ($brwConfig['names']['gender'] == 1) ?
			sprintf(__d('brownie', 'Add another %s [male]', true), $brwConfig['names']['singular']):
			sprintf(__d('brownie', 'Add another %s [female]', true), $brwConfig['names']['singular'])
		,
		'back_home' => __d('brownie', 'Back to home', true),
		'view' => __d('brownie', 'View saved information', true),
	),
	'default' => $default,
));
?>

</fieldset>

<?php
echo $form->end(__d('brownie', 'Save', true)); ?>
</div>