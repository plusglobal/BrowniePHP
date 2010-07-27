<div id="home">
<?php
foreach ($brwMenu as $section => $items) {
	echo '
	<div class="section clearfix">
	<h2>' . $section . '</h2>';
	foreach($items as $label => $model){
		echo '
		<div class="home-box">
			<h3><span>' . $label . '</span></h3>
			<ul class="clearfix">
				<li class="add">' . $html->link(__d('brownie', 'Add', true), array(
					'controller' => 'contents',
					'action' => 'edit',
					$model,
				)) . '</li>
				<li class="index">' . $html->link(__d('brownie', 'Index', true), array(
					'controller' => 'contents',
					'action' => 'index',
					$model,
				)) . '</li>
			</ul>
		</div>';
	}
	echo '
	</div>';
}
?>
</div>