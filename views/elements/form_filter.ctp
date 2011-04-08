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
		echo $form->input($model . '.' . $field . '_from', array(
			'label' => $brwConfig['fields']['names'][$field] . ' <span>' . __d('brownie', 'from', true) . '</span>',
			'type' => $schema[$field]['type'],
			'timeFormat' => '24'
		));
		echo $form->input($model . '.' . $field . '_to', array(
			'label' => $brwConfig['fields']['names'][$field] . ' <span>' . __d('brownie', 'to', true) . '</span>',
			'type' => $schema[$field]['type'],
			'timeFormat' => '24'
		));
	} else {
		echo $form->input($model . '.' . $field, array('empty' => '-'));
	}
}
echo $form->end(__d('brownie', 'Filter', true));
?>

