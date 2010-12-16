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
				echo $html->link($label, $url); ?>
			</li>
		<?php endforeach ?>
		</ul>
	</li>

<?php endforeach ?>
</ul>

<?php if (!empty($sitesOptions) and count($sitesOptions) > 1) { ?>
<div id="siteSelector">
	<h2><?php __d('brownie', 'Choose site') ?></h2>
	<?php
	echo $form->create(array(
		'url' => $html->url(array('controller' => 'sites', 'action' => 'select')),
	));
	echo $form->select('Site.id', $sitesOptions, Configure::read('currentSite.id'), array(
		'empty' => '- ' . __d('brownie', 'Choose site', true),
	));
	echo $form->submit(__d('brownie', 'Go', true));
	echo $form->end();
	?>
</div>
<?php } ?>
