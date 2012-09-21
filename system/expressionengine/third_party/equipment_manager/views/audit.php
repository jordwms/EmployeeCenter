<? // echo '<pre>'; print_r($default_form); echo '</pre>';?>
<div id="audit" class="equipment_manager">
	<!-- Populate Report sheet-->
	<?=form_open($action_url, $attributes, $form_hidden)?>
		<h2 id="form_title"><? echo $default_form['name']; ?></h2>
		<? // create a set of divs, each holding a report forms which will be hidden/revealed when one is selected
			// for each section in the template
		    $this->table->set_template($cp_table_template);

			foreach($default_form['sections'] as $section) {
				echo '<ul>';
				// for each question in "sections"...
				for($i=0; $i< count($section['questions']); $i++) {	
					$question_num = $section['questions'][$i];

					// find the corresponding question in "questions"
					foreach ($default_form['questions'] as $question) {
    					$this->table->set_heading($section['header'],'');		
						
						// if question is found, make it True/False
						if(in_array($question_num, $question)){ 
							$this->table->add_row(
								$question['text'], 
								'<label for=id="Q'.$question_num.'" >'.$question['labels']['true'].'</label>'
								.' <input type="radio" id="Q'.$question_num.'" name="Q'.$question_num.'" value="'.$question['labels']['true'].'"/> '
								.'<label for=id="Q'.$question_num.'" >'.$question['labels']['false'].'</label>'
								.' <input type="radio" id="Q'.$question_num.'" name="Q'.$question_num.'" value="'.$question['labels']['false'].'"/> '
							);
						}
					}
				}
				echo $this->table->generate(); 
				echo '</ul>';				
			}
		?>
		<input type="submit"> </input>
	<?=form_close()?>
</div><!-- audit -->
<script type="text/javascript">
$(document).ready(function() {
	$('#audit_form').submit(function() {
		// on submit, validate.
		// if valid, json encode and submit
		// else return false
		console.log("js submit activated.");
	});

});
</script>