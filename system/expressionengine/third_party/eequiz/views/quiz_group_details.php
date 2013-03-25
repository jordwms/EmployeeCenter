<div id="quiz_group_details" class="eequiz">
<?
	$this->table->set_heading(
		'<input type="checkbox" name="ALL" value="ALL">',
		lang('quizzes_for_group').': '.$group_name
	);

	echo form_open($action_url_delete, $form_attributes, $form_hidden);

	foreach($quizzes as $g) {
		$this->table->add_row(
			'<input type="checkbox" name="'.$g['id'].'" value="'.$g['id'].'">'.
			'</td>'.
			'<td>'.
			'<a href="'.$quiz_details_uri.$g['id'].'">'.$g['title'].'</a>'.
			'</td>'
		);
	}


	$this->table->add_row(
		form_submit(array('value' => lang('delete'), 'class' => 'submit')).
		form_close().
		'</td>'.
		'<td>'.
		form_open($action_url_add, $form_attributes, $form_hidden).
		'<select name="new_quiz" style="margin-right:5px">'.
		$quiz_dropdown.
		'</select>'.
		form_submit(array('value' => lang('add'), 'class' => 'submit')).
		form_close().
		'</td>'
	);
	echo $this->table->generate();	
?>
</div>