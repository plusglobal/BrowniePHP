<?php if (!empty($record[$model]['brw_actions'])): ?>
<ul>
<?php foreach ($record[$model]['brw_actions'] as $action => $params): ?>
	<li class="<?php echo $params['class'] ?>">
	<?php echo $html->link($params['title'], $params['url'], $params['options'], $params['confirmMessage']) ?>
	</li>
<?php endforeach ?>
</ul>
<?php endif ?>