<div id="score_card" class="eequiz">
<?
	$this->table->set_columns($headers);
	$data = $this->table->datasource('score_card_datasource', $defaults, array('group_id' => $group_id, 'member_id' => $member_id) );

    echo $data['table_html'];
?>
</div>