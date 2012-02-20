<ul id="menu-items">
<?php foreach ($brwMenu as $section => $items) : ?>
	<li>
		<h2><?php echo $section ?></h2>
		<ul>
		<?php foreach($items as $label => $url) : ?>
			<li>
				<?php
				if (!is_array($url)) {
					$url = array('plugin' => 'brownie', 'controller' => 'contents', 'action'=> 'index', $url);
				}
				$url = array_merge(array('brw' => false, 'plugin' => 'brownie'), $url);
				echo $this->Html->link(__($label), $url); ?>
			</li>
		<?php endforeach ?>
		</ul>
	</li>

<?php endforeach ?>
</ul>