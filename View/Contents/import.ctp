<div class="form">
<?php
$adding = empty($this->data[$model]['id']);
$url = array('controller' => 'contents', 'action' => 'import', $model);
echo $this->Form->create('Content', array('type' => 'file', 'url' => $url));
?>
<fieldset>
	<legend><?php
	echo CakeText::insert(__d('brownie', 'Import :name_plural'), array('name_plural' => __($brwConfig['names']['plural'])));
	?></legend>
	<?php
	echo $this->Form->input('model', array('value' => $model, 'type' => 'hidden'));
	echo $this->Form->input('file', array('type' => 'file'));
	foreach ($brwConfig['fields']['import'] as $field) {
		//esto lo hago asi nomas para ahora, pero hay que hacerlo bien para todo tipo de campos
		echo $this->Form->input($field, array(
			'options' => $related['belongsTo'][$field],
			'empty' => '-',
			'label' => $brwConfig['fields']['names'][$field],
		));
	}
	?>
</fieldset>
<?php echo $this->Form->end(__d('brownie', 'Submit'));?>
</div>