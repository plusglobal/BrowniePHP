<div class="model-index" id="<?php echo $model ?>_index">

<div class="index clearfix">
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
				array_merge(
					array('action' => 'edit', $model, 'after_save' => ($this->params['action'] == 'view') ? 'parent':'index'),
					$filters
				)
			) . '</li>';
		}
		if ($brwConfig['actions']['import']) {
			echo '
			<li class="import">' . $html->link(
				__d('brownie', 'Import', true),
				array('action' => 'import', $model)
			) . '</li>';
		}
		if ($brwConfig['actions']['export']) {
			$url = array_merge(array('action' => 'export', $model), $this->params['named'], $filters);
			echo '<li class="export">' . $html->link(__d('brownie', 'Export', true), $url) . '</li>';
		}
		?>
		</ul>
	</div>
</div>
<?php
if ($brwConfig['fields']['filter'] and $calledFrom == 'index') {
	echo $this->element('form_filter', array('brwConfig' => $brwConfig));
}


$i = 0;
if ($records) {
	$controlsOnTop = ($this->Paginator->params['paging'][$model]['options']['limit'] >= 20);
	if ($controlsOnTop) {
		echo $this->element('pagination', array('model' => $model, 'brwConfig' => $brwConfig));
	}

	if ($brwConfig['actions']['delete_multiple']) {
		echo $form->create('Content', array(
			'id' => 'deleteMultiple',
			'url' => array('controller' => 'contents', 'action' => 'delete_multiple', $model)
		));
		if ($controlsOnTop) {
			echo '<div class="submit"><input type="submit" value="' . __d('brownie', 'Delete selected', true) . '" /></div>
			';
		}
	}

	echo '<table id="index">';

	foreach ($records as $record):
		if ($i == 0) {
			echo '<tr>';
			if ($brwConfig['actions']['delete_multiple']) {
				echo '
				<th class="delete_multiple">
					<input type="checkbox" id="deleteCheckAll" title="' . __d('brownie', 'Select/Unselect all', true) . '">
				</th>';
			}
			foreach($brwConfig['paginate']['fields'] as $field_name) {
				if (!empty($schema[$field_name])) {
					echo '
					<th class="' . $field_name . ' ' . $schema[$field_name]['class']
					. '">' . $paginator->sort(
						__($brwConfig['fields']['names'][$field_name], true),
						$field_name,
						array('model' => $model, 'escape' => false)
					) . '</th>';
				}
			}
			if (($brwConfig['sortable'] and empty($this->params['named']['sort'])) or !empty($isTree)) {
				echo '<th class="actions">' . __d('brownie', 'Sort', true) . '</th>';
			}
			echo '
				<th class="actions">' . __d('brownie', 'Actions', true) . '</th>
			</tr>';
		}

		$class = ($brwConfig['actions']['delete_multiple']) ? 'row_delete_multiple' : '';

		echo '
		<tr class="'.$class.' list">';
		if ($brwConfig['actions']['delete_multiple']) {
			echo '
			<td class="delete_multiple">
				<input type="checkbox" name="data[Content][id][]" value="' . $record[$model]['id'] . '">
			</td>';
		}

		foreach($brwConfig['paginate']['fields'] as $field_name) {
			if (!empty($schema[$field_name])) {
				echo '
				<td class="' . $field_name . ' ' . $schema[$field_name]['class'] . ' field">'
					. ( !empty($record[$model][$field_name]) ? $record[$model][$field_name] : '&nbsp;' )
				. '</td>';
			}
		}

		if (($brwConfig['sortable'] and empty($this->params['named']['sort'])) or !empty($isTree)): ?>
			<td class="sortable actions">
			<?php
			echo $html->link(__d('brownie', 'Sort up', true),
				array('controller' => 'contents', 'action' => 'reorder', $model, 'up', $record[$model]['id']),
				array('class' => 'up', 'title' => __d('brownie', 'Sort up', true))
			);
			echo $html->link(__d('brownie', 'Sort down', true),
				array('controller' => 'contents', 'action' => 'reorder', $model, 'down', $record[$model]['id']),
				array('class' => 'down', 'title' => __d('brownie', 'Sort down', true))
			);
			?>
			</td>
		<?php endif ?>
			<td class="actions">
			<?php
			$paramsAction = array('filters' => $filters, 'record' => $record, 'model' => $model, 'calledFrom' => $calledFrom);
			echo $this->element('actions', $paramsAction);
			?>
			</td>
		</tr>
	<?php
		$i++;
	endforeach;
	echo '</table>';
	if ($brwConfig['actions']['delete_multiple']) {
		echo '<div class="submit"><input type="submit" value="' . __d('brownie', 'Delete selected', true) . '" /></div>';
		echo $form->end();
	}
} else {
	echo '<p class="norecords">'
	. sprintf(__d('brownie', 'There are no %s', true), $brwConfig['names']['plural'])
	. '</p>';
};

if ($records) {
	echo $this->element('pagination', array('model' => $model, 'brwConfig' => $brwConfig));
	//$this->Paginator = null;
}
?>
</div>