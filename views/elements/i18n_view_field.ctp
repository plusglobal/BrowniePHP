<table>
	<?php foreach ($data as $value): ?>
	<tr>
		<td class="locale locale_<?php echo $value['locale'] ?>">
			<?php echo $this->i18n->humanize($value['locale']); ?>
		</td>
		<td class="fcktxt">
			<?php echo $value['content'] ?>
		</td>
	</tr>
	<?php endforeach ?>
</table>