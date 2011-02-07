<?php
$w = $brwConfig['images'][$image['category_code']]['array_sizes'][0]['w'];
$h = $brwConfig['images'][$image['category_code']]['array_sizes'][0]['h'];
?>
<div class="image <?php if ($brwConfig['images'][$image['category_code']]['description']) {
	echo 'with-description';
} ?>" style="width: <?php echo $w ?>px;">
	<ul class="image-actions clearfix actions">
		<?php
		if($permissions[$model]['edit']){
			echo '
			<li class="edit">' . $html->link(__d('brownie', 'Edit', true), array(
				'controller' => 'contents', 'action' => 'edit_upload', 'plugin' => 'brownie',
				$image['model'], 'BrwImage', $image['record_id'], $image['category_code'], $image['id']
			)) . '</li>
			<li class="delete">' . $html->link(__d('brownie', 'Delete', true), array(
				'controller' => 'contents', 'action' => 'delete_upload', 'plugin' => 'brownie',
				$image['model'], 'BrwImage', $image['id']
			), null, __d('brownie', 'Are you sure you want to delete this image?', true)) . '</li>';
		}
		?>
	</ul>
	<table>
		<tr>
			<td style="width: <?php echo $w ?>px; height: <?php echo $h ?>px"><?php echo $image['tag']; ?></td>
		</tr>
	</table>
	<?php if ($brwConfig['images'][$image['category_code']]['description']): ?>
		<p class="description"><?php echo $image['description'] ?></p>
	<?php endif ?>
</div>