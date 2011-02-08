<?php
$after_save = null;
switch ($calledFrom) {
	case 'view':
		$after_save = 'view';
	break;
	case 'parent':
		$after_save = 'parent';
	break;
	case 'index':
		$after_save = 'index';
	break;
}
if (!empty($record[$model]['brw_actions']['edit'])) {
	$record[$model]['brw_actions']['edit']['url']['after_save'] = $after_save;
}

if (!empty($record[$model]['brw_actions'])): ?>
<ul class="actions">
<?php foreach ($record[$model]['brw_actions'] as $action => $params): ?>
	<?php if (($action == 'view' and $calledFrom == 'view') or ($action == 'add' and $calledFrom != 'view')) continue ?>
	<li class="<?php echo $params['class'] ?>">
	<?php echo $html->link(__($params['title'], true), $params['url'], $params['options'], __($params['confirmMessage'], true)) ?>
	</li>
<?php endforeach ?>
</ul>
<?php endif ?>