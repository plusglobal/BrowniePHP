<table>
	<?php foreach ($data as $value): ?>
	<tr>
		<td class="locale_<?php echo $value['locale'] ?>"><strong><?php
		switch ($value['locale']) {
			case 'eng': __d('brownie', 'English'); break;
			case 'spa': __d('brownie', 'Spanish'); break;
		}
		?></strong></td>
		<td class="fcktxt"><?php echo $value['content'] ?></td>
	</tr>
	<?php endforeach ?>
</table>