<?php
if (!empty($brwConfig['fields']['conditional'])) {
	echo $javascript->link(Router::url(array('controller' => 'contents', 'action' => 'js_edit', $model, 'js_edit.js')));
}
?><div class="form">
<?php
$url = array('controller' => 'contents', 'action' => 'edit', $model);
$adding = empty($this->data[$model]['id']);
if (!$adding) {
	$url[] = $this->data[$model]['id'];
}
echo $form->create('Content', array('type' => 'file', 'action' => 'edit', 'autocomplete' => 'off', 'url' => $url));
?>
	<fieldset>
 		<legend>
		<?php
		if ($adding) {
			$action = __d('brownie', 'Add :name_singular', true);
		} else {
			$action = __d('brownie', 'Edit :name_singular', true);
		}

		echo String::insert($action, array('name_singular' => $brwConfig['names']['singular']));
		?>
		</legend>
		<?php
		echo $form->input('model', array('value' => $model, 'type' => 'hidden'));
		foreach ($fields as $key => $value) {
			$params = array();
			//pr($related);
			if (isset($related['belongsTo'][$key])) {
				$params = array('type' => 'select', 'options' => $related['belongsTo'][$key]);
				if ($schema[$key]['null']) {
					$params['empty'] = '- ' . __d('brownie', 'None', true);
				}
			} elseif (isset($related['tree'][$key])) {
				if (!empty($related['tree'][$key])) {
					$params = array(
						'type' => 'select',
						'options' => $related['tree'][$key],
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
			if (in_array($key, $fckFields)) {
				echo $fck->load($model . '.' . Inflector::camelize($key), 'Brownie');
			}
		}

		if (!empty($related['hasAndBelongsToMany'])) {
			foreach ($related['hasAndBelongsToMany'] as $key => $list) {
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
foreach ($uploads as $upload) :

	$continue = false;
	if ($upload == 'Image' and !empty($brwConfig['images'])) {
		$continue = true;
		$uploadConfig = $brwConfig['images'];
	} elseif (!empty($brwConfig['files'])) {
		$continue = true;
		$uploadConfig = $brwConfig['files'];
	}

	if ($continue and $adding) :
		$i=0;
		foreach ($uploadConfig as $categoryCode => $uploadCat) : ?>
			<fieldset class="fieldsUploads">
				<legend><?php echo $uploadCat['name_category'] ?></legend>
				<?php $classes = array('fieldsetImages'); if (!$uploadCat['index']) $classes[] = 'hide'; ?>
				<div id="fieldset<?php echo $i ?>" class="<?php echo  join(' ', $classes) ?>">
					<input type="file" name="data[BrwImage][file][]" />
					<input type="hidden" name="data[BrwImage][model][]" value="<?php echo $model ?>" />
					<input type="hidden" name="data[BrwImage][category_code][]" value="<?php echo $categoryCode ?>" />
					<?php
					if ($uploadCat['description']) :
						echo $form->input('Brw' . $upload . '.' . $i . '.description', array(
							'label' => __d('brownie', 'Description', true),
							'name' => 'data[BrwImage][description][]',
						));
					else : ?>
						<input type="hidden" name="data[BrwImage][description][]" value="" />
					<?php endif ?>

					<?php if (!$uploadCat['index']) : ?>
						<a href="#" class="cloneRemove">Remove</a>
					<?php endif ?>

				</div>
				<div id="cloneHoder<? echo $i ?>" class="cloneHolder"></div>
				<?php if (!$uploadCat['index']) : ?>
				<a href="#" class="cloneLink" id="clone_<?php echo $i ?>">Add another</a>
				<?php endif ?>
			</fieldset>
		<?php
		$i++;
		endforeach;
	endif;
endforeach;


?>

<fieldset>
<?php echo $form->input('after_save', $afterSaveOptionsParams) ?>
</fieldset>

<?php echo $form->end(__d('brownie', 'Save', true)); ?>
</div>