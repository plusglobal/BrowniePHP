<ul id="menu-items">
<?php
foreach($menuSections as $section => $items){
	echo '<li><h2>' . $section . '</h2><ul>';
	foreach($items as $label => $model) {
		echo '
		<li>
			' . $html->link($label, array('plugin'=>'brownie', 'controller' => 'contents', 'action'=> 'index', $model)) . '
		</li>';
	}
	echo '</ul></li>';
}
?>
</ul>