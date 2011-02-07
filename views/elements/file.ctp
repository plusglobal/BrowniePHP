<div class="file clearfix">
		<ul class="file-actions clearfix">
			<?php
			if ($permissions[$model]['edit']) {
				echo '
				<li class="edit">' . $html->link(__d('brownie', 'Edit', true), array(
					'controller' => 'contents', 'action' => 'edit_file', 'plugin' => 'brownie',
					$file['model'], $file['record_id'], $file['category_code'], $file['id']
				)) . '
				</li>
				<li class="delete">' . $html->link(__d('brownie', 'Delete', true), array(
					'controller' => 'contents', 'action' => 'delete_upload', 'plugin' => 'brownie',
					$file['model'], 'BrwFile', $file['id']
				), null, __d('brownie', 'Are you sure you want to delete this file?', true)) . '
				</li>';
			}
			?>
		</ul>
		<?php echo $file['tag_force_download']; ?>
</div>