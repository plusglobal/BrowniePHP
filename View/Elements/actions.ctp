<?php
$named = array();
foreach ($this->params['named'] as $key => $value) {
	if ($key != 'back_to') {
		$named[$key] = urlencode($value);
	}
}

if (!empty($record[$model]['brw_actions']['view'])) {
	$record[$model]['brw_actions']['view']['url'] += $named;
	if (!empty($record[$model]['brw_actions']['view']['url']['page'])) {
		unset($record[$model]['brw_actions']['view']['url']['page']);
	}
	if ($calledFrom == 'index') {
		$record[$model]['brw_actions']['view']['url'] += array('back_to' => 'index');
	}
}
if (!empty($record[$model]['brw_actions']['delete']) and $calledFrom == 'view') {
	$record[$model]['brw_actions']['delete']['url']['after_delete'] = 'parent';
}
if (!empty($record[$model]['brw_actions']['add'])) {
	if ($calledFrom == 'view')
	$record[$model]['brw_actions']['add']['url']['after_save'] = 'view';
}


if (!empty($record[$model]['brw_actions'])): ?>
<ul class="actions">
<?php foreach ($record[$model]['brw_actions'] as $action => $params):

?>
	<?php if (
		($action == 'view' and $calledFrom == 'view')
		or ($action == 'add' and $calledFrom != 'view')
		or ($action == 'index' and ($calledFrom == 'index' or $calledFrom == 'parent'))
	) continue ?>
	<li class="<?php echo $params['class'] ?>">
	<?php echo $this->Html->link(__($params['title']), $params['url'], $params['options'], __($params['confirmMessage'])) ?>
	</li>
<?php endforeach ?>
</ul>
<?php endif ?>