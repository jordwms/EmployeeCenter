<div id="quiz_group_details" class="eequiz">
	<?	$this->table->set_template($cp_table_template);
	$this->table->set_heading('Quizzes of this quiz group');
	foreach($quizzes as $g) {
		$this->table->add_row( '<a href="'.$quiz_details_uri.$g['id'].'">'.$g['title'].'</a>' );
	}
// method=edit_quiz&quiz_id=1
	$this->table->add_row(
		form_open($action_url, $form_attributes, $form_hidden).
		'<select name="new_quiz">'.
		$quiz_dropdown.
		'</select>'.
		'<button>Add</button>'
		);
	echo $this->table->generate();	
?>
</div>