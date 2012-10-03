<div id="report" class="workreports"> 
<?php if (count($reports) > 0): ?>
	<p><?=$help_message ?></p>
	<?
		// CompanyInfo
	    $this->table->set_template($cp_table_template);
	    $this->table->set_template($company_info_header); // adding a table name
	    $this->table->set_columns(array(
	    	'submission_date'     => array('header' => lang('submission_date')),
	    	'submitter_name'     => array('header' => lang('submitter_name')),
	    	'customer_name'       => array('header' => lang('customer_name')),
	    	'order'               => array('header' => lang('order')),
	    	'customer_reference'  => array('header' => lang('customer_reference')),
	    	'rtd_reference'       => array('header' => lang('rtd_reference')),
	    	'work_location_name'  => array('header' => lang('work_location_name')),
	    	'contact_person'      => array('header' => lang('contact_person')),
	    	'object_description'  => array('header' => lang('object_description')),
	    	'execution_date'      => array('header' => lang('execution_date'))
	    ));

	    $this->table->set_data($reports);

	    echo $this->table->generate();
	?>
</div>
<script type="text/javascript">
	$(document).ready(function(){
		$('table').tablesorter({sortList: [[0,1]]});
	});
</script>
<?php else: ?>
<?=lang('no_matching_results')?> <br /> <br />
<?php endif; ?>