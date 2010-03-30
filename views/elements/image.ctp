<div class="image">
		<ul class="image-actions clearfix">
			<?php
			if($permissions[$model]['edit']){
				echo '<li class="edit">' . $html->link(__d('brownie', 'Edit', true), array(
					'controller' => 'contents',
					'action' => 'edit_image',
					'plugin' => 'brownie',
					$image['model'], $image['record_id'], $image['category_code'], $image['id']
				)) . '</li>
				<li class="delete">' . $html->link(__d('brownie', 'Delete', true), array(
					'controller' => 'images',
					'action' => 'delete',
					'plugin' => 'brownie',
					$image['id']
				), null, __d('brownie', 'Are you sure you want to delete this image?', true)) . '</li>';
			}
			?>
		</ul>
		<?php echo $image['tag']; ?>
</div>