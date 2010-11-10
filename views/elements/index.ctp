<div class="index">
	<div class="clearfix">
		<h2><?php echo $brwConfig['names']['plural'] ?></h2>
		<div class="actions">
			<ul>
			<?php
			if ($permissions[$model]['edit'] and $permissions[$model]['add']) {
				echo '
				<li class="add">' . $html->link(
					String::insert(
						__d('brownie', 'Add :name_singular', true),
						array('name_singular' => $brwConfig['names']['singular'])
					),
					array('action' => 'edit', $model, $foreignKeyValue, 'after_save' => 'index')
				) . '</li>';
			}
			if ($brwConfig['actions']['import']) {
				echo '
				<li class="import">' . $html->link(
					__d('brownie', 'Import', true),
					array('action' => 'import', $model)
				) . '</li>';
			}
			?>
			</ul>
		</div>
	</div>
</div>
<?php echo $this->element('list', array(
	'model' => $model, 'records' => $records, 'schema' => $schema, 'brwConfig' => $brwConfig
)) ?>