<?php if (!empty($brwConfig['fields']['conditional'])) echo $this->element('js_conditional_fields'); ?>
<div class="form">
<?php
$url = array('controller' => 'contents', 'action' => 'edit', $model);
$adding = empty($this->data[$model]['id']);
if (!$adding) {
	$url[] = $this->data[$model]['id'];
}
echo $this->Form->create('Content', array(
	'type' => 'file', 'action' => 'edit', 'autocomplete' => 'off', 'url' => $url, 'novalidate' => true,
	'inputDefaults' => array('separator' => ' ')
));
?>
<fieldset>
	<legend>
	<?php
	$action = $adding ? __d('brownie', 'Add :name_singular') : __d('brownie', 'Edit :name_singular');
	echo CakeText::insert($action, array('name_singular' => __($brwConfig['names']['singular'])));
	?>
	</legend>
	<?php
	echo $this->Form->input('model', array('value' => $model, 'type' => 'hidden'));


	/*if (!empty($langs3chars)) {
		echo '<div id="enabledLangs" class="clearfix">
		<label class="enabledLangs">' . __d('brownie', 'Enabled languages', true) . '</label>' ;
		foreach ($langs3chars as $lang2 => $lang3) {
			echo $this->Form->input('enabled_' . $lang3, array(
				'type' => 'checkbox', 'label' => $this->i18n->humanize($lang3)
			));
		}
		echo '</div>';
	}*/

	foreach ($fields as $key => $value) {
		$params = array();
		if (isset($related['belongsTo'][$key])) {
			$params = array('type' => 'select', 'options' => $related['belongsTo'][$key], 'escape' => false);
			if ($schema[$key]['null']) {
				$params['empty'] = '-';
			}
		} elseif (isset($related['tree'][$key])) {
			if (!empty($related['tree'][$key])) {
				$params = array(
					'type' => 'select',
					'options' => $related['tree'][$key],
					'empty' => __d('brownie', '(No parent)'),
				);
				if (!empty($this->params['named'][$key])) {
					$params['selected'] = $this->params['named'][$key];
				}
			} else {
				continue;
			}
		}
		if (in_array($value['type'], array('datetime', 'date'))) {
			$params['minYear'] = $brwConfig['fields']['date_ranges'][$key]['minYear'];
			$params['maxYear'] = $brwConfig['fields']['date_ranges'][$key]['maxYear'];
			$params['dateFormat'] = $brwConfig['fields']['date_ranges'][$key]['dateFormat'];
			if ($value['null']) {
				$params['empty'] = '-';
			}
		}

		if (!empty($brwConfig['fields']['legends'][$key])) {
			$params['after'] = $brwConfig['fields']['legends'][$key];
		}

		if (strstr($key, 'password')) {
			$params['type'] = 'password';
		} elseif (
			(empty($schema[$key]['key']) or $schema[$key]['key'] != 'primary')
			and !$schema[$key]['isForeignKey']
			and (in_array($schema[$key]['type'], array('string', 'integer', 'float')))
			and empty($related['tree'][$key])
		) {
			$params['type'] = 'text';
		}


		$params['div'] = array('id' => 'brw' . $model . Inflector::camelize($key));
		$params['label'] = __($brwConfig['fields']['names'][$key]);
		if (in_array($key, $fckFields)) {
			$params['class'] = 'richEditor';
		}
		if (!in_array($key, $i18nFields)) {
			echo $this->Form->input($model . '.' . $key, $params);
		} else {
			echo $this->element('i18n_input', array('model' => $model, 'field' => $key, 'params' => $params));
		}
	}

	if (!empty($related['hasAndBelongsToMany'])) {
		foreach ($related['hasAndBelongsToMany'] as $key => $list) {
			if (!empty($list)) {
				$params = array('multiple' => 'checkbox', 'options' => $list);
				if (count($list) > 5) {
					$params['multiple'] = 'multiple';
					$params['escape'] = false;
					$params['size'] = 5;
					$params['class'] = 'combo-select';
					echo $this->Html->script('/brownie/js/jquery.selso');
					echo $this->Html->script('/brownie/js/jquery.comboselect');
				}
				echo $this->Form->input($key . '.' . $key, $params);
			}
		}
	}
	?>
</fieldset>
<?php
$uploads = array('Image', 'File');
$i = 0;
foreach ($uploads as $upload) :

	$continue = false;
	if ($upload == 'Image' and !empty($brwConfig['images'])) {
		$continue = true;
		$uploadConfig = $brwConfig['images'];
	} elseif ($upload == 'File' and !empty($brwConfig['files'])) {
		$continue = true;
		$uploadConfig = $brwConfig['files'];
	}

	if ($continue and $adding) :
		foreach ($uploadConfig as $categoryCode => $uploadCat) : ?>
			<fieldset class="fieldsUploads">
				<legend><?php echo $uploadCat['name_category'] ?></legend>
				<?php $classes = array('fieldsetUploads'); if (!$uploadCat['index']) $classes[] = 'hide'; ?>
				<div id="fieldset<?php echo $i ?>" class="<?php echo  join(' ', $classes) ?>">
					<input type="file" name="data[Brw<?php echo $upload ?>][file][]" size="100%" />
					<input type="hidden" name="data[Brw<?php echo $upload ?>][model][]" value="<?php echo $model ?>" />
					<input type="hidden" name="data[Brw<?php echo $upload ?>][category_code][]" value="<?php echo $categoryCode ?>" />
					<?php
					if ($uploadCat['description']) :
						echo $this->Form->input('Brw' . $upload . '.' . $i . '.description', array(
							'label' => __d('brownie', 'Description'),
							'name' => 'data[Brw' . $upload . '][description][]',
						));
					else : ?>
						<input type="hidden" name="data[Brw<?php echo $upload ?>][description][]" value="" />
					<?php endif ?>

					<?php if (!$uploadCat['index']) : ?>
						<ul class="actions"><li class="delete"><a href="#" class="cloneRemove">Remove</a></li></ul>
					<?php endif ?>

				</div>
				<?php if (!$uploadCat['index']) : ?>
				<div id="cloneHoder<?php echo $i ?>" class="cloneHolder"></div>
				<a href="#" class="cloneLink cloneLink_<?php echo $upload ?>" id="clone_<?php echo $i ?>"><?php
				echo ($upload == 'Image')? __d('brownie', 'Add Image') : __d('brownie', 'Add File')
				?></a>
				<?php endif ?>
			</fieldset>
		<?php
		$i++;
		endforeach;
	endif;
endforeach;


?>

<fieldset>
<?php echo $this->Form->input('after_save', $afterSaveOptionsParams) ?>
</fieldset>
<?php echo $this->Form->hidden('referer') ?>
<div class="submit">
	<input type="submit" value="<?php echo __d('brownie', 'Save') ?>" />
	<a href="<?php echo Router::url(array('controller' => 'brownie', 'action' => 'index')) ?>" class="cancel">Cancel</a>
</div>

<?php echo $this->Form->end(); ?>
</div>