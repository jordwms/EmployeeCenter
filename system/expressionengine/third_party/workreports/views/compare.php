<div id="compare" class="workreports">
	
	<div style="float:left">
		<p>Saved file:</p>
		<?	// CompanyInfo
		    $this->table->set_template($cp_table_template);
		    $this->table->set_heading(
		    	'Company',
		    	'CustomerAccount',
		    	'Order',
		    	'RTDReference',
		    	'WorkLocationName',
		    	'ContactPerson',
		    	'ObjectDescription',
		    	'ExecutionDate',
		    	'EmplId'
		    );

		    // echo $file->CompanyInfo->children();

		    $this->table->add_row(
		    	$file->CompanyInfo->Company,
		    	$file->CompanyInfo->CustomerAccount,
		    	$file->CompanyInfo->Order,
		    	$file->CompanyInfo->RTDReference,
		    	$file->CompanyInfo->WorkLocationName,
		    	$file->CompanyInfo->ContactPerson,
		    	$file->CompanyInfo->ObjectDescription,
		    	$file->CompanyInfo->ExecutionDate,
		    	$file->CompanyInfo->EmplId
		    );

		    echo $this->table->generate();	
		?>
		<pre><?=print_r($file)?></pre>
	</div>
	<div style="float:left">
		<p>Your report:</p>
		<pre><?=print_r($new_xml)?></pre>
	</div>
	<p>The report you are trying to send already has already been sent. What would you like to do?</p>
	<input type="button" name="overwrite" value="<?=lang('overwrite')?>" />
	<input type="button" name="cancel" value="<?=lang('cancel')?>" />
	<input type="button" name="unique" value="<?=lang('unique')?>"/>
</div>
