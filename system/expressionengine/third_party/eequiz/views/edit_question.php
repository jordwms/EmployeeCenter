<h1><?=lang('general_information')?></h1>
<table class="mainTable">
<tbody>
		<?php foreach ($information as $k => $row) { ?>
			<tr class="<?= $k%2==1 ? "odd" : "even" ?>">
				<td style='width: 320px;'><label><?=$row['label']?><span><?=$row['description']?></span></label></td>
				<td><?=$row['content']?></td>
			</tr>
		<?php } ?>
</tbody>
</table>


<h1><?=lang('general_settings')?></h1>
<table class="mainTable">
<tbody>
		<?php foreach ($settings as $k => $row) { ?>
			<tr class="<?= $k%2==1 ? "odd" : "even" ?>">
				<td style='width: 320px;'><label><?=$row['label']?><span><?=$row['description']?></span></label></td>
				<td><?=$row['content']?></td>
			</tr>
		<?php } ?>
</tbody>
</table>


<h1><?=lang('answer_settings')?></h1>
<table class="mainTable">
<tbody>
		<?php foreach ($answer_settings as $k => $row) { ?>
			<tr class="<?= $k%2==1 ? "odd" : "even" ?>">
				<td style='width: 320px;'><label><?=$row['label']?><span><?=$row['description']?></span></label></td>
				<td><?=$row['content']?></td>
			</tr>
		<?php } ?>
</tbody>
</table>


<?php foreach ($other_tables as $t) { ?>
<h1><?=$t['title']?></h1>
<table class="mainTable" id='<?=$t['id']?>'><tbody>
	<?php foreach ($t['rows'] as $k => $row) { ?>
	<tr class="<?= $k%2==1 ? "odd" : "even" ?>">
		<?php foreach ($row as $cell) echo "<td>{$cell}</td>\n"; ?>
	</tr>
	<?php } ?>
</tbody>
</table>
<?php } ?>


<?=$extra?>


<input type='submit' value='submit' />
