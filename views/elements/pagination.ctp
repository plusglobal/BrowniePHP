<?php
	echo '<div class="pagination clearfix">';
	echo '<div class="paging_counter">';
	if ($numbers = $this->Paginator->numbers(array('model' => $model, 'separator' => ''))) {
		echo '
		<div class="paging clearfix">
			<span class="prev">' . $this->Paginator->prev(
				'&laquo; ' . __d('brownie', 'previous', true), array('model' => $model, 'escape' => false), null, array('class'=>'disabled')
			) . '</span>
			' . $numbers . '
			<span class="next">' . $this->Paginator->next(
				__d('brownie', 'next', true).' &raquo;', array('model' => $model, 'escape' => false), null, array('class'=>'disabled')
			) . '</span>
		</div>';
	}

	echo '
	<p class="counter">' . $this->Paginator->counter(array(
		'format' => String::insert(
			__d('brownie', 'Page %page% of %pages%, showing %current% :name_plural out of %count% total, starting on record %start%, ending on %end%', true),
			array('name_plural' => $brwConfig['names']['plural'])
		),
		'model' => $model
	)) . '</p>';

	echo '</div>';

	$limit = $brwConfig['paginate']['limit'];
	$limits = '';
	foreach (array($limit, $limit * 2, $limit * 5, $limit * 10) as $limit) {
		if ($this->Paginator->params['paging'][$model]['count'] >= $limit) {
			$params = array_merge(
				array('controller' => $this->params['controller'], 'action' => $this->params['action'], 'plugin' => 'brownie'),
				$this->params['pass'],
				array_merge($this->params['named'], array('limit' => $limit))
			);
			if (!empty($params['page'])) {
				unset($params['page']);
			}
			$limitCompare = (!empty($this->params['named']['limit'])? $this->params['named']['limit']:$brwConfig['paginate']['limit']);
			$limits .= '<li' . (($limitCompare == $limit)?' class="current"' : '') . '>
				<a href="'.Router::url($params).'">'.$limit.'</a>
			</li>';
		}
	}
	if($limits) {
		echo '
		<div class="limiter">
			<p>' . sprintf(__d('brownie', '%s per page', true), $brwConfig['names']['plural']) . ':</p>
			<ul>' . $limits . '</ul>
		</div>';
	}
	echo '</div>';
?>