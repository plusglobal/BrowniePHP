<div class="files clearfix">
	<a href="<?php echo $file['url'] ?>" class="brw-file"><?php echo $file['description'] ?></a>
	<ul class="files-actions actions clearfix">
		<?php
		if ($permissions[$model]['edit']) {
			echo '
			<li class="view">
				<a href="' . $file['url'] . '" target="_blank">' . __d('brownie', 'View', true) . '</a>
			</li>
			<li class="download">
				<a href="' . $file['force_download'] . '">' . __d('brownie', 'Download', true) . '</a>
			</li>
			<li class="edit">' . $html->link(__d('brownie', 'Edit', true), array(
				'plugin' => 'brownie', 'controller' => 'contents', 'action' => 'edit_upload',
				$file['model'], 'BrwFile' ,$file['record_id'], $file['category_code'], $file['id']
			)) . '
			</li>
			<li class="delete">' . $html->link(__d('brownie', 'Delete', true), array(
				'plugin' => 'brownie', 'controller' => 'contents', 'action' => 'delete_upload',
				$file['model'], 'BrwFile', $file['id']
			), null, __d('brownie', 'Are you sure you want to delete this file?', true)) . '
			</li>';
		}
		?>
	</ul>
</div>