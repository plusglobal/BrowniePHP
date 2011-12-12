<div class="form">
<?php
$adding = empty($this->data[$model]['id']);
$url = array('controller' => 'contents', 'action' => 'import', $model);
echo $form->create('Content', array('type' => 'file', 'url' => $url));
?>
<fieldset>
	<legend><?php
	echo String::insert(__d('brownie', 'Import :name_plural', true), array('name_plural' => $brwConfig['names']['plural']));
	?></legend>
	<?php
	echo $form->input('model', array('value' => $model, 'type' => 'hidden'));
	echo $form->input('file', array('type' => 'file'));
	foreach ($brwConfig['fields']['import'] as $field) {
		//estoy lo hago asi nomas para ahora, pero hay que hacerlo bien para todo tipo de campos
		echo $this->Form->input($field, array(
			'options' => $related['belongsTo'][$field],
			'empty' => '-',
			'label' => $brwConfig['fields']['names'][$field],
		));
	}
	?>
</fieldset>
<?php echo $form->end(__d('brownie', 'Submit', true));?>
</div>