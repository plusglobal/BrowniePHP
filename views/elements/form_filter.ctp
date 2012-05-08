<?php if ($isAnyFilter): ?>
<div class="flash_notice flash">
	<?php __d('brownie', 'This following listing is filtered.'); ?>
	<?php echo $html->link(__d('brownie', 'View complete index', true), array('action' => 'index', $model)) ?>
</div>
<?php  endif ?>

<?php
echo $form->create('Filter', array(
	'url' => array('controller' => 'contents', 'action' => 'filter', $model),
	'class' => 'filter clearfix',
	'inputDefaults' => array('separator' => ' '),
));

$isAvanced = false;
foreach ($brwConfig['fields']['filter'] as $field => $multiple) {
	if (!in_array($field, $brwConfig['fields']['hide'])) {
		$fieldType = $schema[$field]['type'];
		$params = array();
		$before = $after = '';
		if (array_key_exists($field, $brwConfig['fields']['filter_advanced'])) {
			$before = '<div class="advanced">';
			$after = '</div>';
			$isAvanced = true;
		}
		if ($schema[$field]['class'] == 'number') {
			$params += array('class' => 'number');
		}

		if (
			in_array($fieldType, array('datetime', 'date'))
			or ($schema[$field]['class'] == 'number' and empty($brwConfig['fields']['filter'][$field]))
		) {
			if ($schema[$field]['class'] == 'date') {
				$params += array(
					'type' => $fieldType,
					'minYear' => $brwConfig['fields']['date_ranges'][$field]['minYear'],
					'maxYear' => $brwConfig['fields']['date_ranges'][$field]['maxYear'],
					'dateFormat' => $brwConfig['fields']['date_ranges'][$field]['dateFormat'],
					'monthNames' => $brwConfig['fields']['date_ranges'][$field]['monthNames'],
					'timeFormat' => '24',
					'empty' => '-',
					'separator' => '',
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
			} elseif ($multiple and $schema[$field]['class'] != 'number') {
				$params = array_merge($params, array(
					'empty' => false,
					'multiple' => 'multiple',
					'between' => '<div class="filter-checkbox clearfix" id="filter-checkbox-' . $field . '">',
					'after' => '</div>',
				));
			}
			if ($fieldType == 'integer' and $schema[$field]['class'] == 'string' and empty($params['multiple'])) {
				$params['class'] = 'single-select';
			}
			if (
				!$schema[$field]['isForeignKey']
				and
				(in_array($schema[$field]['type'], array('string', 'integer', 'float')))
			) {
				$params['type'] = 'text';
			}
			echo $before . $form->input($model . '.' . $field, $params) . $after;
		}
	}
}
echo $form->submit(__d('brownie', 'Filter', true), array('id' => 'filterSubmit'));
echo $form->end();
?>

