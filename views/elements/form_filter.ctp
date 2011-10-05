<?php if ($isAnyFilter): ?>
<div class="flash_notice flash">
	<?php __d('brownie', 'This following listing is filtered.'); ?>
	<?php echo $html->link(__d('brownie', 'View complete index', true), array('action' => 'index', $model)) ?>
</div>
<?php  endif ?>

<?php
echo $form->create('Filter', array(
	'url' => array('controller' => 'contents', 'action' => 'filter', $model),
	'class' => 'filter clearfix'
));

$isAvanced = false;
foreach ($brwConfig['fields']['filter'] as $field => $multiple) {
	$fieldType = $schema[$field]['type'];
	$params = array();
	$before = $after = '';
	if (array_key_exists($field, $brwConfig['fields']['filter_advanced'])) {
		$before = '<div class="advanced">';
		$after = '</div>';
		$isAvanced = true;
	}

	if (in_array($fieldType, array('datetime', 'date')) or $schema[$field]['class'] == 'number') {
		if ($schema[$field]['class'] == 'number') {
			$params += array('class' => 'number');
		} else {
			$params += array(
				'type' => $fieldType,
				'minYear' => $brwConfig['fields']['date_ranges'][$field]['minYear'],
				'maxYear' => $brwConfig['fields']['date_ranges'][$field]['maxYear'],
				'dateFormat' => $brwConfig['fields']['date_ranges'][$field]['dateFormat'],
				'monthNames' => $brwConfig['fields']['date_ranges'][$field]['monthNames'],
				'timeFormat' => '24',
				'empty' => '-',
			);
		}
		echo $before . $form->input(
			$model . '.' . $field . '_from',
			$params + array('label' => $brwConfig['fields']['names'][$field] . ' ' . __d('brownie', 'from', true))
		) . $form->input(
			$model . '.' . $field . '_to',
			$params + array('label' => $brwConfig['fields']['names'][$field] . ' ' . __d('brownie', 'to', true))
		) . $after;
	} else {
		$params += array(
			'empty' => '-',
			'label' => __($brwConfig['fields']['names'][$field], true),
		);
		if ($fieldType == 'boolean') {
			$params += array(
				'type' => 'select',
				'options' => array(1 => __d('brownie', 'Yes', true), 0 => __d('brownie', 'No', true)),
			);
		} elseif ($multiple) {
			$params = array_merge($params, array(
				'empty' => false,
				'multiple' => 'checkbox',
				'between' => '<div class="filter-checkbox clearfix">',
				'after' => '</div>',
			));
		}
		echo $before . $form->input($model . '.' . $field, $params) . $after;
	}
}
echo $form->submit(__d('brownie', 'Filter', true), array('id' => 'filterSubmit'));
echo $form->end();
?>

