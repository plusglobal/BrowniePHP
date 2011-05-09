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

foreach ($brwConfig['fields']['filter'] as $field) {
	if (in_array($schema[$field]['type'], array('datetime', 'date'))) {
		$params = array(
			'type' => $schema[$field]['type'],
			'minYear' => $brwConfig['fields']['date_ranges'][$field]['minYear'],
			'maxYear' => $brwConfig['fields']['date_ranges'][$field]['maxYear'],
			'dateFormat' => $brwConfig['fields']['date_ranges'][$field]['dateFormat'],
			'monthNames' => $brwConfig['fields']['date_ranges'][$field]['monthNames'],
			'timeFormat' => '24'
		);
		echo $form->input(
			$model . '.' . $field . '_from',
			$params + array('label' => $brwConfig['fields']['names'][$field] . ' ' . __d('brownie', 'from', true))
		);
		echo $form->input(
			$model . '.' . $field . '_to',
			$params + array('label' => $brwConfig['fields']['names'][$field] . ' ' . __d('brownie', 'to', true))
		);
	} else {
		echo $form->input($model . '.' . $field, array(
			'empty' => '-',
			'label' => $brwConfig['fields']['names'][$field],
		));
	}
}
echo $form->end(__d('brownie', 'Filter', true));
?>

