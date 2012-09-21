<div class="cp_button"><a href="<?=$module_url?>&amp;method=edit_quiz_template"><?=lang('create_quiz_template_button')?></a></div>

<br />

<table class="mainTable">
	<thead>
		<th id='quiz_template_id_sort_btn' class='headerSortUp' onclick='viewTemplates.ajaxSort("quiz_template_id")'><span class='column_header_text'><?=lang('template_id')?></span></th>
		<th id='title_sort_btn' class='sorting' onclick='viewTemplates.ajaxSort("title")'><span class='column_header_text'><?=lang('template_title')?></span></th>
		<th class=''></th>
	</thead>
	
	<tbody id='templates_tbody'>
	<?php foreach ($templates as $k => $q) { ?>
		<tr class="<?= ($k%2 == 1) ? "odd" : "even" ?>">
			<td><?=$q['quiz_template_id']?></td>
			<td><a href='<?=$module_url?>&method=edit_quiz_template&quiz_template_id=<?=$q['quiz_template_id']?>'><?=$q['title']?></a></td>
			<td><a class='delete_btn' href='javascript:void(0)' onclick='eequiz.showConfirmation( viewTemplates.delete_message.replace("{template_title}", "<?= htmlentities($q['title'], ENT_QUOTES) ?>"), "viewTemplates.deleteTemplate(<?=$q['quiz_template_id']?>)");'>Delete</a></td>
		</tr>
	<?php } ?>
	</tbody>
</table>