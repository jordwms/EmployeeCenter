<div id="overview" class="eequiz">
<?	// CompanyInfo
    $this->table->set_template($cp_table_template);
    $this->table->set_heading(
    	'Employee Name'
    );

    $this->table->add_row(
    	'Jordan Williams'
    );

    echo $this->table->generate();	
    echo form_close();
?>
</div>