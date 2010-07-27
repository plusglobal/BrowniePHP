<ul id="menu-items">
<?php foreach ($brwMenu as $section => $items) : ?>
	<li>
		<h2><?php echo $section ?></h2>
		<ul>
		<?php foreach($items as $label => $model) : ?>
			<li>
				<?php echo $html->link($label, array('plugin'=>'brownie', 'controller' => 'contents', 'action'=> 'index', $model)); ?>
			</li>
		<?php endforeach ?>
		</ul>
	</li>

<?php endforeach ?>

<?php if (!empty($sitesOptions)) : ?>
	<li>
		<h2><?php __d('brownie', 'Sites') ?></h2>
		<ul>
			<li><?php
			echo $form->create(array('url' => $html->url(array('controller' => 'sites', 'action' => 'select'))));
			echo $form->select('Site.id', $sitesOptions, null, array('empty' => '- ' . __d('brownie', 'Choose site', true)));
			echo $form->end(__d('brownie', 'Submit', true));
			?></li>
		</ul>
	</li>
<?php endif ?>
</ul>
