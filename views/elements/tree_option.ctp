<?php list($modelName, $model) = each($data) ?>

<span class="name">
<?php echo $model[$displayField] ?>
</span>

<div class="actions">
<?php
if($permissions[$modelName]['view']){
	echo $html->link(__d('brownie', 'View', true),
	array('action' => 'view', $modelName, $model['id']),
	array('class' => 'view'));
}
if($permissions[$modelName]['edit']){
	echo $html->link(__d('brownie', 'Edit', true),
	array('action' => 'edit', $modelName, $model['id']),
	array('class' => 'edit'));
}
if($permissions[$modelName]['delete']){
	echo $html->link(__d('brownie', 'Delete', true),
	array('action' => 'delete', $modelName, $model['id']),
	array('class' => 'delete'),
	sprintf(__d('brownie', 'Are you sure you want to delete # %s?', true), $model['id']));
}
?>
</div>
