<div id="log_maintenance" class="equipment_manager">
	<!-- Populate Report sheet-->
	<?=form_open($action_url, $attributes, $form_hidden)?>
		<h2 id="form_title"><? echo $default_form['name']; ?></h2>
		<? // create a set of divs, each holding a report forms which will be hidden/revealed when one is selected
			// for each section in the template
			foreach($default_form['sections'] as $section) {
				echo '<ul>
						<li><h3>'.$section['header'].'</h3></li>';

				// for each question in "sections"...
				for($i=0; $i< count($section['questions']); $i++) {	
					$question_num = $section['questions'][$i];

					// find the corresponding question in "questions"
					foreach ($default_form['questions'] as $question) {		
						// if found, make it a True/False Question
						if(in_array($question_num, $question)){
							echo 
							'<li>'
								.$question['text']
								.$question['labels']['true']
								.'<input type="radio" name="Q'.$question_num.'" value="'.$question['labels']['true'].'"/>'
								.$question['labels']['false']
								.'<input type="radio" name="Q'.$question_num.'" value="'.$question['labels']['false'].'"/>'
							.'</li>';
						}
					}
				}
				echo '</ul>';
			}
		?>
		<input type="submit"> </input>
	<?=form_close()?>
</div><!--log_maintenance -->
<script type="text/javascript">
$(document).ready(function() {
	$('#maintenance_form').submit(function() {
		// on submit, validate.
		// if valid, json encode and submit
		// else return false
		console.log("js submit activated.");
	});

});
</script>