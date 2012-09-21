<div id="edit_item" class="equipment_manager">
	<?=form_open($type_eq_action_url, $type_eq_form_attributes, $type_eq_form_hidden)?>
		<fieldset>
	 		<legend><h4><?=lang('eq_type')?></h4></legend>
			<select name="type_id" id="equipment_type"><? 
				foreach ($eq_types as $eq_type) {
					$selected = ($items['type_id'] == $eq_type['id']) ? ' selected="selected"' : '' ;
					echo '<option value="'.$eq_type['id'].'"'.$selected.'>'.$eq_type['type'].' model:'.$eq_type['model'].' ('.$eq_type['description'].')</option>';
			}?>
			</select>
		</fieldset>
	<?=form_close()?>

	<?=form_open($serial_form_action_url, $serial_form_attributes, $serial_form_hidden)?>
		 <fieldset>
	 		<legend><h4><?=lang('eq_serialnum')?></h4></legend>
			<input type="text" name="serialnum" value="<?=$items['serialnum']?>"/> 
			<p id="serial_error" style="color: red; visibility: hidden;">The number you entered is not valid. Enter only letters, numbers and dashes.</p>
		</fieldset>
	<?=form_close()?>

	<?=form_open($description_action_url, $description_form_attributes, $description_form_hidden)?>
		<fieldset>
			<legend><h4><?=lang('description')?>/<?=lang('notes')?></h4></legend>
			<input type="text" name="description" value="<?=$items['description']?>"/>
			<p id="descr_error" style="color: red; visibility: hidden;">The name you entered is not valid. Enter only letters, numbers, commas, apostrophes, and periods.</p>
		</fieldset>
	<?=form_close()?>

	<?=form_open($assigned_user_action_url,$assigned_user_form_attributes, $assigned_user_form_hidden)?>
		<fieldset>
	 		<legend><h4><?=lang('assigned_member')?></h4></legend>
			<select name="assigned_member" id="assigned_member">
				<option value=0>None</option>
				<? foreach ($members as $entry) {
					// if a user is assigned this equipment their option tag is set to "selected"
					$selected = ($items['assigned_member_id'] == $entry['member_id']) ? ' selected="selected"' : '' ;
					echo '<option value= '.$entry['member_id'].$selected.'>'.$entry['screen_name'].'</option>';
				}?>
			</select>
		</fieldset>
	<?=form_close()?>

	<?=form_open($current_user_action_url,$current_user_form_attributes, $current_user_form_hidden)?>
		<fieldset>
	 		<legend><h4><?=lang('current_member')?></h4></legend>
			<select name= "current_member_id" id="current_member">
				<option value=0>None</option>
				<? foreach ($members as $entry) {
						// if a user is assigned this equipment their option tag is set to "selected"
						$selected = ($items['current_member_id'] == $entry['member_id']) ? ' selected="selected"' : '' ;
						echo '<option value= '.$entry['member_id'].$selected.'>'.$entry['screen_name'].'</option>';
				}?>
			</select>
		</fieldset>
	<?=form_close()?>

	<?=form_open($location_action_url, $location_form_attributes,$location_form_hidden)?>
		<fieldset>
	 		<legend><h4><?=lang('storage_facility')?></h4></legend>
			<select name="facility_id">
				<? 
				// Populate with the locations table 
				foreach ($location as $entry) {
					$selected = ($items['assigned_facility_id'] == $entry['id']) ? ' selected="selected"' : '' ;
					echo '<option value='.$entry['id'].$selected.'>'.$entry['facility_name'].'</option>';
				}?>
			</select>
		</fieldset>
	<?=form_close()?>

	<?=form_open($parent_eq_action_url,$parent_eq_form_attributes,$parent_eq_form_hidden)?>
 		<fieldset>
	 		<legend><h4><?=lang('eq_parent')?></h4></legend>
			<select name="parent_id">
				<option value=0 <? if($items['parent_id'] == 0 || is_null($items['parent_id'])) echo'selected="selected"';?> >No parent</option>
				<? 	foreach ($parents as $entry) {
						if($entry['id'] != $items['id']){
							$selected = ($items['parent_id'] == $entry['id']) ? ' selected="selected"' : '' ;
							echo '<option value='.$entry['id'].$selected.'>'.$entry['serialnum'].'</option>'; 
				}}?>

			</select>
		</fieldset>
	<?=form_close()?>

	<?=form_open($child_eq_action_url, $child_eq_form_attributes, $child_eq_form_hidden)?>
		<fieldset>
	 		<legend><h4><?=lang('eq_child')?></h4></legend>
			<ul>
			<? 
			if(count($children) > 0){
				foreach($children as $entry) {
					echo '<li><a href='.$edit_link.$entry['child_id'].'>'.$entry['child_serialnum'].'</a></li>';
				}
			} else {
				echo "<li>No children</li>";
			}?>
			</ul>
		</fieldset>
	<?=form_close()?>

	<?=form_open($activity_value_action_url, $activity_value_form_attributes, $activity_value_form_hidden)?>
		<fieldset>
	 		<legend><h4><?=lang('activity')?></h4></legend>
	 		<p>Expects to be numeric values only</p>
			<input type="text" name="activity_value" value="<?=$items['activity_value']?>"/>
		</fieldset>
	<?=form_close()?>

	<?=form_open($activity_time_action_url, $activity_time_form_attributes, $activity_time_form_hidden)?>
		<fieldset>
	 		<legend><h4><?=lang('activity_date')?></h4></legend>
			<p>Enter the time associated with the activity value.</p>
			<p>Expects it to be in the format MM/DD/YYYY or DD-MM-YYYY</p>
			<input type="text" name="activity_time" value="<?=date("m-d-Y",$items['activity_time'])?>"/>
		</fieldset>
	<?=form_close()?>

	<?=form_open($dnu_form_action_url, $dnu_form_form_attributes, $dnu_form_form_hidden)?>
		<? 
		if($items['status_id'] == 3) {
			$selected_false = '';
			$selected_true 	= 'checked="checked"';
		}
		else {
			$selected_false = 'checked="checked"';
			$selected_true 	= '';	
		}
		?>
		<fieldset>
	 		<legend><h4><?=lang('status_code')?></h4></legend>
			<ul>
				<li><input type="radio" name="status" value="1" class="dnu_flag" <? echo $selected_false;?> >Safe to use</input></li>
				<li><input type="radio" name="status" value="3" class="dnu_flag" <? echo $selected_true;?> >Do NOT use</input></li>
			</ul>

			<!--
			<div id="dnu_change" hidden="hidden">
				<fieldset>
		 		<legend><h4>Reason for Changing Status:</h4></legend>
				<input type="text" name="note" id="dnu_text"> </input> 
				<p id="dnu_success" style="color: red;"></p>
			</div> 
			-->
		</fieldset>
	<?=form_close()?>
	<p class="results"></p>
	<div>
		<a href = "<? echo $audit_link?>"><button>Audit</button></a>
		<!-- <a href = "<? echo $maintenance_link?>"><button><?=lang('maintenance')?></button></a>		 -->
		<a href = "<? echo $log_link?>"><button>Show Log Entries</button></a>
	</div>
</div><!-- edit_item -->
<script type= "text/javascript">
	$(document).ready(function() {
		//var equipment_id = <?echo $items['id']; ?>;
		var dnu_error, status_id_val, dnu_text_val, this_form, method_name, msg, name, obj, regex, val, valid;
		var success = "<p style=\"color: red;\">Successfully updated in the database.</p>";
		var failure = "<p style=\"color: red;\">The input is invalid. Only commas, periods, a-z, 0-9 may be entered.</p>";

 		$('form').keydown(function (e) { if(e.which == 13) e.preventDefault(); });
 		
	    // $(".equipment_manager form > input").change(function(event) {
	    // 	event.preventDefault;
	    // 	console.log(this);
	    //     $.post($(this).attr('action'), $(this).serialize(), $(success).appendTo(this) );
	    // });

		// $(".equipment_manager form > input").change(function() {
		// 	this_form = this;
		// 	obj = $(this).find('select, input:text'); 
		// 	console.log(this_form);

		// 	$.ajax({
		// 		url: $(this_form).attr('action'),
		// 		type: "POST",
		// 		data: $(this_form).serialize(),
		// 		success: function() { $(success).appendTo(this_form); },
		// 		failure: function() { $(failure).appendTo(this_form);
		// 		}
		// 	});
		// });

		function isValid(str) {
			regex = /\w+\.*\,*\s*/g;
			valid = regex.test(str);
			return valid;
		}
	});
</script>