<?=form_open($action_url, $attributes, $form_hidden)?>
	<fieldset>
		<legend><h4><?=lang('eq_type')?></h4></legend>
		<select name="type_id">
			<? 	foreach ($eq_types as $eq_type) {
				echo '<option value='.$eq_type['id'].'>'.$eq_type['type'].' model:'.$eq_type['model'].' ('.$eq_type['description'].')</option>';
			}?>
		</select>
	</fieldset>
	<fieldset>
		<legend><h4><?=lang('eq_serialnum')?></h4></legend>
		<input type="text" name="serialnum" value=""/>
		<p id="serial_error" style="color: red; visibility: hidden;">The number you entered is not valid. Enter only letters, numbers and dashes.</p>
	</fieldset>
	<fieldset>
		<legend><h4><?=lang('eq_description')?>/<?=lang('notes')?></h4></legend>
		<input type="text" name="description" value=""/>
		<p id="descr_error" style="color: red; visibility: hidden;">The name you entered is not valid. Enter only letters, numbers, commas, apostrophes, and periods.</p>
	</fieldset>
	<fieldset>
		<legend><h4><?=lang('assigned_member')?></h4></legend>
		<select name="assigned_member_id">
			<option value=0>No user</option>
			<? foreach ($members as $entry) {
				echo '<option value= '.$entry['member_id'].'>'.$entry['screen_name'].'</option>';
			}?>
		</select>
	</fieldset>
	<fieldset>
	 		<legend><h4><?=lang('storage_facility')?></h4></legend>
		<select name="assigned_facility_id" id="assigned_facility_id">
			<? foreach ($facilities as $entry) {
				echo '<option value='.$entry['id'].'>'.$entry['facility_name'].'</option>';
			}?>
		</select>
	</fieldset>
	<fieldset>
		<legend><h4><?=lang('eq_parent')?></h4></legend>
		<select name="parent_id">
			<option value=0>No parent</option>
			<? 	foreach ($parents as $entry) {
				echo '<option value='.$entry['id'].'>'.$entry['serialnum'].'</option>'; 
			}?>
		</select>
	</fieldset>
	<fieldset>
		<legend><h4><?=lang('eq_child')?></h4></legend> No children
	</fieldset>
	<fieldset>
		<legend><h4><?=lang('activity')?></h4></legend>
		<p>Expects to be numeric values only</p>
		<input type="text" name="activity_value" value=""/>
		<p id="descr_error" style="color: red; visibility: hidden;">The name you entered is not valid. Enter only letters, numbers, commas, apostrophes, and periods.</p>
	</fieldset>
	<fieldset>
		<legend><h4><?=lang('activity_date')?></h4></legend>
		<p>Enter the time associated with the activity value</p>
		<p>Expects it to be in the format MM/DD/YYYY or DD-MM-YYYY</p>
		<input type="text" name="activity_time" value=""/>
		<p id="descr_error" style="color: red; visibility: hidden;">The name you entered is not valid. Enter only letters, numbers, commas, apostrophes, and periods.</p>
	</fieldset>
	<fieldset>
		<legend><h4><?=lang('status_code')?></h4></legend>
		<ul>
			<li><input type="radio" name="status_id" value="1" class="dnu_flag" checked="checked"><?=lang('status_code_1')?></input></li>
			<li><input type="radio" name="status_id" value="3" class="dnu_flag"><?=lang('status_code_3')?></input></li>
		</ul>
	</fieldset>
	<button><?=lang('add_new_eq')?></button>
<?=form_close()?><!--new_equipment -->
<p class="results"></p>
<script type= "text/javascript">
	$(document).ready(function() {
		var this_form, input_array, name, regex, valid_serial, valid_descr, serialnum, rso_dnu_flag, assigned_member_id, assigned_facility_id, type_id, parent_id;
		var success = "<p style=\"color: red;\">Successfully added to the database!</p>";
		var invalid_error = "<p style=\"color: red;\">The input is invalid. Only commas, dashes, periods, a-z, 0-9 may be entered.</p>";
		var incomplete_error = "<p style=\"color: red;\">Failure: Please fill out both the \"Serial Number\" and \"Equipment model\" fields as completely as possible.</p>";

		$('form').keydown(function (e) { if(e.which == 13) e.preventDefault(); });

		$('#new_equipment').submit(function (e) {
			e.preventDefault();

			serialnum = $('input[name = "serialnum"]').val();
			description = $('input[name |= "description"]').val();

		// Clear so only the most recent feedback is posted.
		$('.results').empty();

		// Either text field is blank
		if(serialnum== ''|| description=='') { 
			$(incomplete_error).appendTo('.results'); 
		} else {
			if (isValid(serialnum) && isValid(description)) {
				$.ajax({
					url:$(this).attr('action'),
					type: 'POST',
					data: $(this).serialize(),
					success: function() { 
						$(success).appendTo('.results');
						console.log(this);
						// window.location = $('[title |= "Equipment List"]').attr('href');
					},
					error: function() {
						$(invalid_error).appendTo('.results');
					}
				});
			}else {
				// Tell user to put in valid values
				$(invalid_error).appendTo('.results');
			}
		}
	});

	function isValid(str) {
		regex = /\w+\.*\,*\s*\-*/g;
		valid = regex.test(str);
			//console.log(valid);
			return valid;
		}
	});
</script>