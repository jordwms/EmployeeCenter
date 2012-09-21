

<?php foreach ($question_types as $qt) { ?>
	<div class="cp_button"><a href="<?=$module_url?>&amp;method=edit_question&amp;question_type=<?= $qt["classname"] ?>">Create <?=lang($qt["classname"])?></a></div>
<?php } ?>

<br />

<table class="mainTable">
	<thead>
		<th id='question_id_sort_btn' class='headerSortUp' onclick='viewQuestions.ajaxSortQuestions("question_id")'><div class='column_header_text'><?=lang('question_id')?></div></th>
		<th id='title_sort_btn' class='sorting' onclick='viewQuestions.ajaxSortQuestions("title")'><div class='column_header_text'><?=lang('question_title')?></div></th>
		<th id='question_shortname_sort_btn' class='sorting' onclick='viewQuestions.ajaxSortQuestions("question_shortname")'><div class='column_header_text'><?=lang('shortname')?></div></th>
		<th class=''><span class='column_header_text'><?=lang('contained_text')?></span></th>
		<th class=''></th>
		<th class=''></th>
	</thead>
	
	<tbody id='questions_tbody'>
	<?php foreach ($questions as $k => $q) { ?>
		<tr class="<?= ($k%2 == 1) ? "odd" : "even" ?>">
			<td><?=$q['question_id']?></td>
			<td><a href='<?=$module_url?>&method=edit_question&question_id=<?=$q['question_id']?>'><?=$q['title']?></a></td>
			<td><?=$q['question_shortname']?></td>
			<td>
				<?php foreach ($q['contained'] as $k => $in_quiz) { 
					$separator = ($k == count($q['contained'])-1) ? "" : ", "; ?>
					<a href='<?=$module_url?>&amp;method=edit_quiz&amp;quiz_id=<?=$in_quiz['quiz_id']?>'><?=$in_quiz['title']?></a><?=$separator?>
				<?php } ?>
			</td>
			<td>
				<a href='javascript:void(0)' onclick='viewQuestions.duplicateQuestion(<?=$q['question_id']?>);'>Duplicate</a>
			</td>
			<td>
				<a href='javascript:void(0)' onclick='eequiz.showConfirmation( viewQuestions.delete_message.replace("{question_title}", "<?= htmlentities($q['title'], ENT_QUOTES) ?>"), "viewQuestions.deleteQuestion(<?=$q['question_id']?>)");'>Delete</a>
			</td>
		</tr>
	<?php } ?>
	</tbody>
</table>