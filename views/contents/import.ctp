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
	$session->flash();
	echo $form->input('model', array('value' => $model, 'type' => 'hidden'));
	echo $form->input('file', array('type' => 'file'));
	?>
</fieldset>
<?php echo $form->end(__d('brownie', 'Submit', true));?>
</div>