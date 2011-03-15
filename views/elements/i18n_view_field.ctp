<table>
	<?php foreach ($data as $value): ?>
	<tr>
		<td width="1"><strong><?php echo $value['locale'] ?></strong></td>
		<td><?php echo $value['content'] ?></td>
	</tr>
	<?php endforeach ?>
</table>