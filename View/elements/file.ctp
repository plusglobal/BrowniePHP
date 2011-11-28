<div class="files clearfix">
	<a target="_blank" href="<?php echo $file['url'] ?>" class="brw-file" title="<?php echo $file['title'] ?>">
		<?php echo $file['title'] ?>
	</a>
	<ul class="files-actions actions clearfix">
		<?php
		if ($permissions[$model]['edit']) {
			echo '
			<li class="view">
				<a href="' . $file['url'] . '" target="_blank">' . __d('brownie', 'View') . '</a>
			</li>
			<li class="download">
				<a href="' . $file['force_download'] . '">' . __d('brownie', 'Download') . '</a>
			</li>
			<li class="edit">' . $this->Html->link(__d('brownie', 'Edit'), array(
				'plugin' => 'brownie', 'controller' => 'contents', 'action' => 'edit_upload',
				$file['model'], 'BrwFile' ,$file['record_id'], $file['category_code'], $file['id']
			)) . '
			</li>
			<li class="delete">' . $this->Html->link(__d('brownie', 'Delete'), array(
				'plugin' => 'brownie', 'controller' => 'contents', 'action' => 'delete_upload',
				$file['model'], 'BrwFile', $file['id']
			), null, __d('brownie', 'Are you sure you want to delete this file?')) . '
			</li>';
		}
		?>
	</ul>
</div>