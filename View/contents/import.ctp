<div class="form">
<?php
$adding = empty($this->data[$model]['id']);
$url = array('controller' => 'contents', 'action' => 'import', $model);
echo $this->Form->create('Content', array('type' => 'file', 'url' => $url));
?>
<fieldset>
	<legend><?php
	echo String::insert(__d('brownie', 'Import :name_plural'), array('name_plural' => $brwConfig['names']['plural']));
	?></legend>
	<?php
	echo $this->Form->input('model', array('value' => $model, 'type' => 'hidden'));
	echo $this->Form->input('file', array('type' => 'file'));
	?>
</fieldset>
<?php echo $this->Form->end(__d('brownie', 'Submit'));?>
</div>