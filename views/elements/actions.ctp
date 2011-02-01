<?php
//pr($record);
if (!empty($record[$model]['brw_actions'])): ?>
<ul class="actions">
<?php foreach ($record[$model]['brw_actions'] as $action => $params): ?>
	<?php if( ($action == 'view' and !empty($inView)) or ($action == 'add' and empty($inView))) continue ?>
	<li class="<?php echo $params['class'] ?>">
	<?php echo $html->link($params['title'], $params['url'], $params['options'], $params['confirmMessage']) ?>
	</li>
<?php endforeach ?>
</ul>
<?php endif ?>