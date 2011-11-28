<?php
$w = $brwConfig['images'][$image['category_code']]['array_sizes'][0]['w'];
$h = $brwConfig['images'][$image['category_code']]['array_sizes'][0]['h'];
?>
<div class="image" style="width: <?php echo $w ?>px;">
	<ul class="image-actions clearfix actions">
		<?php
		if($permissions[$model]['edit']){
			echo '
			<li class="edit">' . $this->Html->link(__d('brownie', 'Edit'), array(
				'controller' => 'contents', 'action' => 'edit_upload', 'plugin' => 'brownie',
				$image['model'], 'BrwImage', $image['record_id'], $image['category_code'], $image['id']
			)) . '</li>
			<li class="delete">' . $this->Html->link(__d('brownie', 'Delete'), array(
				'controller' => 'contents', 'action' => 'delete_upload', 'plugin' => 'brownie',
				$image['model'], 'BrwImage', $image['id']
			), null, __d('brownie', 'Are you sure you want to delete this image?')) . '</li>';
		}
		?>
	</ul>
	<?php echo $image['tag']; ?>
	<?php if ($image['description']): ?>
		<p class="description"><?php echo $image['description'] ?></p>
	<?php endif ?>
</div>