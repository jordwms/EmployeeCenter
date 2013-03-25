<div id="manage_quiz_groups" class="eequiz">
<?=lang('manage_quiz_groups_tip')?>
<?
	$this->table->set_heading(
		'<input type="checkbox" name="ALL" value="ALL">',
		lang('quiz_groups')
	);

	echo form_open($action_url_delete, $form_attributes, $form_hidden);

	foreach($quiz_groups as $g) {
		$this->table->add_row(
			'<input type="checkbox" name="'.$g['id'].'" value="'.$g['id'].'">'.
			'</td>'.
			'<td>'.
			'<a href="'.$group_url.$g['id'].'">'.$g['name'].'</a>'.
			'</td>'
			);
	}

	$this->table->add_row(
		form_submit(array('name'=>'submit', 'value' => lang('delete'), 'class' => 'submit')).
		form_close().
		'</td>'.
		'<td>'.
		form_open($action_url_create, $form_attributes, $form_hidden).
		'<input type=text name="new_quiz_group_name" />'.
		form_submit(array('value' => lang('add'), 'class' => 'submit'))
		);
	echo $this->table->generate();	
?>
</div>