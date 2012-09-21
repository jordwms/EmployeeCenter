<div class="cp_button"><a href="<?=$module_url?>&amp;method=edit_quiz"><?=lang('create_quiz_button')?></a></div>

<br />

<table class="mainTable">
	<thead>
		<th id='quiz_id_sort_btn' class='headerSortUp' onclick='viewQuizzes.ajaxSortQuizzes("quiz_id")'><span class='column_header_text'><?=lang('quiz_id')?></span></th>
		<th id='title_sort_btn' class='sorting' onclick='viewQuizzes.ajaxSortQuizzes("title")'><span class='column_header_text'><?=lang('quiz_title')?></span></th>
		<th class=''></th>
		<th class=''></th>
		<th class=''></th>
	</thead>
	
	<tbody id='quizzes_tbody'>
	<?php foreach ($quizzes as $k => $q) { ?>
		<tr class="<?= ($k%2 == 1) ? "odd" : "even" ?>">
			<td><?=$q['quiz_id']?></td>
			<td><a href='<?=$module_url?>&method=edit_quiz&quiz_id=<?=$q['quiz_id']?>'><?=$q['title']?></a></td>
			<td>
				<a class='duplicate_btn' href='<?=$module_url?>&method=view_answer_data&quiz_id=<?=$q['quiz_id']?>'>View Answer Data</a>
			</td>
			<td>
				<a class='duplicate_btn' href='javascript:void(0)' onclick='viewQuizzes.duplicateQuiz(<?=$q['quiz_id']?>);'>Duplicate</a>
			</td>
			<td>
				<a class='delete_btn' href='javascript:void(0)' onclick='eequiz.showConfirmation( viewQuizzes.delete_message.replace("{quiz_title}", "<?=htmlentities($q['title'], ENT_QUOTES)?>"), "viewQuizzes.deleteQuiz(<?=$q['quiz_id']?>)");'>Delete</a>
			</td>
		</tr>
	<?php } ?>
	</tbody>
</table>