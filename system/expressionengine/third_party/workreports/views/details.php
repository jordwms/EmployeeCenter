<style type="text/css">
	#remarks table th{
		width: 33.3333%;
	}
</style>

<div id="details" class="workreports"> 
	<?= form_open($action_url,$form_attributes,$form_hidden);
		// CompanyInfo
	    $this->table->set_template($cp_table_template);
	    $this->table->set_template($company_info_header); // adding a table name
	    $this->table->set_heading(
	    	lang('customer_name'),
	    	lang('customer_account'),
	    	lang('order'),
	    	lang('customer_reference'),
	    	lang('rtd_reference'),
	    	lang('work_location_name'),
	    	lang('contact_person'),
	    	lang('execution_date'),
	    	''

	    );

	    $this->table->add_row(
	    	$report['customer_name'],
	    	$report['customer_account'],
	    	$report['order'].'/'.$report['work_order'].'/'.$report['work_report'],
	    	'<input type="text" name="customer_reference" value="'.$report['customer_reference'].'" />',
	    	'<input type="text" name="rtd_reference" value="'.$report['rtd_reference'].'" />',
	    	'<input type="text" name="work_location_name" value="'.$report['work_location_name'].'" />',
	    	'<input type="text" name="contact_person" value="'.$report['contact_person'].'" />',
	    	date('m-d-Y',$report['execution_date']),
	    	'<input type="submit" value="'.lang('update').'" />'
	    );

	    echo $this->table->generate();	
	    echo form_close();
	?>
	<p>Resources:</p>
	<?  $this->table->set_heading(
	    	lang('qty'),
	    	lang('name'),
	    	lang('resource_id')
	    );
	    foreach($resources as $item){
    	    $this->table->add_row(
    	    	$item['qty'],
    	    	$item['name'],
    	    	$item['resource_id']
    	    );
	    }
	    echo $this->table->generate();
	?>
	<p>Sales items:</p>
	<?  $this->table->set_heading(
	    	lang('qty'),
	    	lang('unit'),
	    	lang('item_name'),
	    	lang('item_id')
	    );
	    foreach($items as $item){
    	    $this->table->add_row(
    	    	$item['qty'],
    	    	$item['unit'],
    	    	$item['name'],
    	    	$item['item_id']
    	    );
	    }
	    echo $this->table->generate();
	?>
	<p>Materials:</p>
	<?  $this->table->set_heading(
	    	lang('qty'),
	    	lang('name'),
	    	lang('item_id')
	    );
	    foreach($mats as $item){
    	    $this->table->add_row(
    	    	$item['qty'],
    	    	$item['name'],
    	    	$item['item_id'].'/'.$item['dimension_id']
       	    );
	    }
	    echo $this->table->generate();
	?>
	<div id="remarks">
	<?	$this->table->set_heading( lang('remarks'),  lang('object_description'), lang('order_description'));    
	    $this->table->add_row( $report['remarks'], $report['object_description'], $report['order_description']  );
	    echo $this->table->generate();
	?>
	</div>
	<div>
		<?=$report['status']?>
		<?=$delete_button?>
		<?=$reject_button?>
	</div>
</div>
<script type="text/javascript">	
$(document).ready(function() {	
    var retVal;
    $('#delete > input[type=button]').click(function() {
        retVal = confirm("<?=lang('delete_conformation')?>");
        if(retVal) {
        	window.location= $(this).attr('href');
        } else {
        	return false;
        }
    });
    $('#reject > input[type=button]').click(function() {
        retVal = confirm("<?=lang('reject_conformation')?>");
        if(retVal) {
        	window.location= $(this).attr('href');
        } else {
        	return false;
        }
    });
});
</script> <!-- END JS --> 