<div id="manage_quiz_groups" class="eequiz">
	<?	$this->table->set_template($cp_table_template);
		$this->table->set_heading('Quiz Group');
		foreach($quiz_groups as $g) {
			$this->table->add_row( '<a href="'.$g['id'].'">'.$g['name'].'</a>' );
		}

		$this->table->add_row(
			form_open($action_url, $form_attributes, $form_hidden).
			'<input type=text name="new_quiz_group_name" size="50%"/>'.
			'<button>Add</button>'
			);
		echo $this->table->generate();	
	?>
</div>