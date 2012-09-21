<?php

require_once(QUIZ_ENGINE_PATH."Question.php");

define("MC_ONE_ANSWER", 1);
define("MC_MULTIPLE_ANSWERS", 2);
define("MC_NO_ANSWER", 3);
define("MC_WEIGHTED_ANSWER", 4);

class MultipleChoice extends Question
{
	
	var $options					= array();
			// value
			// text
			// feedback
			// weight
	var $answer_type				= MC_ONE_ANSWER;
	var $allow_write_in				= FALSE;
	var $randomize_option_order		= FALSE;
	var $feedback_prefix			= "option {#} feedback: ";
	
	
	function MultipleChoice()
	{
		parent::Question();
		
		$this->classname = "MultipleChoice";
	}
	
	
	function initFromDB($question_id, $data = 0)
	{
		if (!$question_id) return;
		
		parent::initFromDB($question_id, $data);
		
		$this->answer_type = (isset($this->settings["answer_type"])) ? $this->settings["answer_type"] : MC_ONE_ANSWER;
		$this->allow_write_in = (isset($this->settings["allow_write_in"])) ? $this->settings["allow_write_in"] : FALSE;
		$this->randomize_option_order = (isset($this->settings["randomize_option_order"])) ? $this->settings["randomize_option_order"] : FALSE;
		$this->feedback_prefix = (isset($this->settings["feedback_prefix"])) ? $this->settings["feedback_prefix"] : FALSE;
		
		$this->options = (isset($this->settings["options"])) ? $this->settings["options"] : array();
		
		if ($this->answer_type == MC_WEIGHTED_ANSWER) {
			
			$this->weight = 0;
			$this->max_weight = -1;
			foreach ($this->options as $o) {
				if ($o["weight"] > $this->max_weight) $this->max_weight = $o["weight"];
			}
		}
	}
	
	
	function initFromPost()
	{
		parent::initFromPost();
		
		$this->answer_type = $this->EE->input->get_post('answer_type');
		$this->allow_write_in = ($this->EE->input->get_post('allow_write_in') == 1);
		$this->randomize_option_order = ($this->EE->input->get_post('randomize_option_order') == 1);
		$this->feedback_prefix = $this->EE->input->get_post('feedback_prefix');
		
		$added_options = explode(" ", $this->EE->input->get_post('added_options'));
		
		foreach ($added_options as $option_index)
		{
			$this->options[] = array(
				'text'		=> $this->EE->input->get_post("option_{$option_index}_text"),
				'feedback'	=> $this->EE->input->get_post("option_{$option_index}_feedback"),
				'weight'	=> (!$this->EE->input->get_post("option_{$option_index}_weight")) ? 0 : $this->EE->input->get_post("option_{$option_index}_weight")
			);
		}
		
		switch ($this->answer_type)
		{
			case MC_ONE_ANSWER:
				$this->answer = $this->EE->input->get_post("answer_radio");
				break;
			case MC_MULTIPLE_ANSWERS:
				$checked = array();
				foreach ($added_options as $k => $option_index)
				{
					if ($this->EE->input->get_post("answer_checkbox_".$option_index)) $checked[] = ($k+1);
				}
				$this->answer = implode(" ", $checked);
				break;
			case MC_NO_ANSWER:
				$this->answer = "";
				break;
			case MC_WEIGHTED_ANSWER:
				$this->answer = "";
				$curr_weight = -1;
				foreach ($this->options as $k => $o)
				{
					if ($o['weight'] > $curr_weight) {
						$curr_weight = $o['weight'];
						$this->answer = $k+1;
					}
				}
				break;
		}
	}
	
	
	function initUserData($mapping_id, $member_id, $anonymous)
	{
		parent::initUserData($mapping_id, $member_id, $anonymous);
		
		if ($this->attempts > 0)
		{
			$answers = explode(" ", $this->last_answer);
			if (count($answers > 1) && $this->answer_type == MC_MULTIPLE_ANSWERS) 
			{
				$comma_sep = implode(", ", $answers);
				$this->last_answer_formatted = "options {$comma_sep}";
			}
			elseif ($this->allow_write_in && (strpos($this->last_answer, "WRITE-IN:") === 0)) $this->last_answer_formatted = "write-in: ".substr($this->last_answer,9);
			else $this->last_answer_formatted = "option {$this->last_answer}";
			
			$this->last_answer_formatted = nl2br(htmlspecialchars($this->last_answer, ENT_QUOTES));
		}
		
		// construct answer section
		
		$i = 1;
		$choices_array = array();
		$this->answer_section = "<div class='answer_section'><ol class='multiple_choice_options'>";
		foreach ($this->options as $o)
		{
			$type = $name = $checked_text = "";
			if ($this->answer_type == MC_ONE_ANSWER || $this->answer_type == MC_NO_ANSWER || $this->answer_type == MC_WEIGHTED_ANSWER)
			{
				$type = "radio";
				$name = "mapping{$mapping_id}_user_answer";
				$checked_text = ($this->last_answer == $i && $this->attempts > 0) ? "checked='checked' " : "";
			}
			else
			{
				$type = "checkbox";
				$name = "mapping{$mapping_id}_user_answer[]";
				$checked_text = (preg_match('/(^| )'.$i.'( |$)/', $this->last_answer) && $this->attempts > 0) ? "checked='checked' " : "";
			}
			$choices_array[] = "<li><input type='{$type}' name='{$name}' id='mapping{$mapping_id}_user_answer_{$i}' value='{$i}' {$checked_text} /><label for='mapping{$mapping_id}_user_answer_{$i}'> {$o['text']}</label></li>";
			$i++;
		}
		if ($this->randomize_option_order) shuffle($choices_array);
		
		$option_orders = array();
		foreach ($choices_array as $k => $v) {
			preg_match('/id=[\'"]mapping'.$mapping_id.'_user_answer_(\d+)[\'"]/', $v, $matches);
			$option_orders[] = $matches[1]-1;
		}
		
		if ($this->allow_write_in) {
			$written_in = (strpos($this->last_answer, "WRITE-IN:") === 0);
			$choices_array[] = "<li>".
					"<input type='radio' name='mapping{$mapping_id}_user_answer' value='write-in' ".($written_in ? "checked='checked' ":"")." />".
					"<input type='text' name='mapping{$mapping_id}_user_write_in' id='user_write_in' value='".($written_in ? substr($this->last_answer_formatted, 9):"")."' /></li>";
		}
		
		$this->answer_section .= implode("", $choices_array)."</ol>";
		$this->answer_section .= "<div class='answer_footer'></div>";
		$this->answer_section .= "</div>";
		
		// feedback
		$first_feedback = $this->explanation;
		if ($first_feedback != "") $first_feedback .= "<br />";
		
		$second_feedback = "";
		foreach ($option_orders as $displayed_order => $index)
		//foreach ($this->options as $k => $o)
		{
			$o = $this->options[$index];
			$selected = FALSE;
			if ($this->answer_type == MC_ONE_ANSWER || $this->answer_type == MC_NO_ANSWER) $selected = ($this->last_answer == ($index+1) && $this->attempts > 0);
			else $selected = (preg_match('/(^| )'.($index+1).'( |$)/', $this->last_answer) && $this->attempts > 0);
			
			$prefix = str_replace("{#}", $displayed_order+1, $this->feedback_prefix);
			$prefix = str_replace("{a}", chr(97+$displayed_order), $prefix);
			$prefix = str_replace("{A}", chr(65+$displayed_order), $prefix);
			if ($selected && strlen($o['feedback']) > 0) {
				$second_feedback .= "<li>{$prefix}{$o['feedback']}</li>";
				if ($this->explanation_extra != "") $this->explanation_extra .= ", ";
				$this->explanation_extra .= "{$prefix}{$o['feedback']}";
			}
		}
		if ($second_feedback != "") $second_feedback = "<ul>{$second_feedback}</ul>";
		
		$this->feedback_section = "";
		if ($first_feedback != "" || $second_feedback != "") $this->feedback_section = "<div class='feedback_section'>{$first_feedback}{$second_feedback}</div>";
		
		
		if ($this->attempts > 0)
		{
			// score and mark
			if ($this->answer_type == MC_WEIGHTED_ANSWER) {
				
				if (isset($this->options[$this->last_answer-1])) $this->score = $this->options[$this->last_answer-1]['weight'];
				else $this->score = 0;
				
				if ($this->score == $this->max_weight) {
					
					$this->correctness_class = "correct_mark";
					$correctness_message = $this->EE->lang->line("correct");
				}
				elseif ($this->score > 0) {
					
					$this->correctness_class = "partially_correct_mark";
					$correctness_message = $this->EE->lang->line("partially_correct");
				}
				else {
					
					$this->correctness_class = "incorrect_mark";
					$correctness_message = $this->EE->lang->line("incorrect");
				}
			}
			else {
				
				$this->score = ($this->last_answer == $this->answer || $this->answer_type == MC_NO_ANSWER) ? $this->weight : 0;
				
				// correctness
				$this->correctness_class = "incorrect_mark";
				$correctness_message = $this->EE->lang->line("incorrect");
				
				if ($this->answer == $this->last_answer || $this->answer_type == MC_NO_ANSWER) {
					$this->correctness_class = "correct_mark";
					$correctness_message = $this->EE->lang->line("correct");
				}
				elseif ($this->answer_type == MC_MULTIPLE_ANSWERS) {
					//check for partially correct
					$user_selections = explode(" ", $this->last_answer);
					$num_correct = 0;
					foreach ($user_selections as $u) {
						if (preg_match('/(^| )'.$u.'( |$)/', $this->answer)) $num_correct++;
					}
					if ($num_correct > 0) {
						$this->correctness_class = "partially_correct_mark";
						$correctness_message = "{$num_correct} ".$this->EE->lang->line("correct");
					}
				}
			}
			
			$this->correctness = "<div class='{$this->correctness_class}'><span class='mark_text'>{$correctness_message}</span></div>";
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	function getEditData()
	{
		if (count($this->options) == 0)
		{
			$this->options[] = array(
				'text'		=> "",
				'feedback'	=> "",
				'weight'	=> ""
			);
			$this->options[] = array(
				'text'		=> "",
				'feedback'	=> "",
				'weight'	=> ""
			);
		}
		
		$result = parent::getEditData();
		
		$result['settings'][] = array(
			"label"				=> "Randomize Option Order",
			"description"		=> "",
			"content"			=> "<input class='lightswitch' type='checkbox' name='randomize_option_order' value='1' ".($this->randomize_option_order ? "checked='checked' ": "")." />"
			//"content"			=> "<input type='radio' name='randomize_option_order' value='0' ".((!$this->randomize_option_order) ? "checked='checked' ": "")." /> Sequential".
			//					   "<input type='radio' name='randomize_option_order' value='1' ".(($this->randomize_option_order) ? "checked='checked' ": "")." /> Randomized"
		);
		$result['settings'][] = array(
			"label"				=> "Feedback Prefix",
			"description"		=> "If option-specific feedback is presented to the user, this will prefix every option feedback that is displayed. Use {a} for lowercase letters, {A} for uppercase letters, and {#} for numbers.",
			"content"			=> "<input type='text' name='feedback_prefix' value='{$this->feedback_prefix}' />"
		);
		
		
		
		
		$result['answer_settings'][] = array(
			"label"				=> "Answer Type",
			"description"		=> "",
			"content"			=> "<select name='answer_type' id='answer_type' onchange='qEE_MultipleChoice.updateAnswerType()'>".
								   "<option value='".(MC_ONE_ANSWER)."' ".($this->answer_type == MC_ONE_ANSWER ? "selected='selected' " : "").">One Answer</option>".
								   "<option value='".(MC_MULTIPLE_ANSWERS)."' ".($this->answer_type == MC_MULTIPLE_ANSWERS ? "selected='selected' " : "").">Multiple Answers</option>".
								   "<option value='".(MC_WEIGHTED_ANSWER)."' ".($this->answer_type == MC_WEIGHTED_ANSWER ? "selected='selected' " : "").">Weighted By Answer</option>".
								   "<option value='".(MC_NO_ANSWER)."' ".($this->answer_type == MC_NO_ANSWER ? "selected='selected' " : "").">No Incorrect Answer</option>".
								   "</select>"
		);
		$result['answer_settings'][] = array(
			"label"				=> "Allow Write-In",
			"description"		=> "Allow the user to select a radio where they can type in a freeform answer. Answer type must be set to 'No Incorrect Answer' for this option to be enabled.",
			"content"			=> "<input class='lightswitch' type='checkbox' name='allow_write_in' id='allow_write_in_yes' value='1' ".($this->allow_write_in ? "checked='checked' ": "")." />"
			//"content"			=> "<input type='radio' name='allow_write_in' id='allow_write_in_yes' value='1' ".(($this->allow_write_in) ? "checked='checked' ": "")." /> Yes".
			//					   "<input type='radio' name='allow_write_in' id='allow_write_in_no' value='0' ".((!$this->allow_write_in) ? "checked='checked' ": "")." /> No"
		);
		
		
		
		
		$new_table = array(
			'title'		=> "Answer Choices",
			'id'		=> "mc_answer_choices",
			'columns'	=> array("Option Number", "Text", "Feedback"),
			'rows'		=> array()
		);
		
		$new_table['rows'][] = array(
			"<a href='javascript:void(0)' onclick='qEE_MultipleChoice.addOption()'>Add An Option</a>",
		);
		
		$options_list = "";
		foreach ($this->options as $k => $o)
		{
			$radio_checked = ($this->answer_type == MC_ONE_ANSWER && $this->answer == ($k+1)) ? "checked='checked' " : "";
			$checkbox_checked = ($this->answer_type == MC_MULTIPLE_ANSWERS && preg_match('/(^| )'.($k+1).'( |$)/', $this->answer)) ? "checked='checked' " : "";
			$options_list .= "<li id='options_list_".($k+1)."'><div class='sortable_handle'></div>".
								"<div style='margin-right: 10px; float: left;'>".
								"<input name='option_".($k+1)."_weight' class='v_positive_integer option_weight' type='text' value='{$o['weight']}' />".
								"<input name='answer_radio' type='radio' value='".($k+1)."' {$radio_checked} />".
								"<input name='answer_checkbox_".($k+1)."' type='checkbox' value='1' {$checkbox_checked} />".
								"</div>".
								"<textarea name='option_".($k+1)."_text'>{$o['text']}</textarea>".
								"<textarea name='option_".($k+1)."_feedback'>{$o['feedback']}</textarea>".
								"<div class='remove_button' onclick='qEE_MultipleChoice.removeOption(".($k+1).")'></div>".
								"</li>";
		}
		$new_table['rows'][] = array(
			"<ul id='options_list'>{$options_list}</ul>"
		);
		
		$result['other_tables'][] = $new_table;
		
		
		$num_options = count($this->options);
		$last_option = $num_options+1;
		
		$no_answer = MC_NO_ANSWER;
		$one_answer = MC_ONE_ANSWER;
		$multiple_answers = MC_MULTIPLE_ANSWERS;
		$weighted_answer = MC_WEIGHTED_ANSWER;
		
		$result['extra'] = <<<EOT
<script type="text/javascript">
//<![CDATA[

var qEE_MultipleChoice = {

	numOptions : {$num_options},
	lastOption : {$last_option},
	
	updateAnswerType : function() {
		
		var answerType = $('#answer_type').val();
		
		if ($('input[name=weight]').attr('disabled'))
			$('input[name=weight]').removeAttr('disabled').val('0').addClass('v_positive_integer');
		
		switch (answerType) {
			case '{$no_answer}':
				$('#options_list input:text').hide();
				$('#options_list input:checkbox').hide();
				$('#options_list input:radio').hide();
				$('input[name=allow_write_in]').removeAttr('disabled');
				$('input.option_weight').clearGhostText().val('').removeClass('v_positive_integer');
				break;
			case '{$weighted_answer}':
				$('#options_list input:text').show();
				$('#options_list input:checkbox').hide();
				$('#options_list input:radio').hide();
				$('input[name=allow_write_in]').attr('disabled', 'disabled').removeAttr('checked');
				$('input[name=weight]').attr('disabled', 'disabled').val('weighted by answer').removeClass('v_positive_integer');
				$('input.option_weight').clearGhostText().ghostText("weight").addClass('v_positive_integer');
				break;
			case '{$one_answer}':
				$('#options_list input:text').hide();
				$('#options_list input:checkbox').hide();
				$('#options_list input:radio').show();
				$('input[name=allow_write_in]').attr('disabled', 'disabled').removeAttr('checked');
				$('input.option_weight').clearGhostText().val('').removeClass('v_positive_integer');
				break;
			case '{$multiple_answers}':
				$('#options_list input:text').hide();
				$('#options_list input:checkbox').show();
				$('#options_list input:radio').hide();
				$('input[name=allow_write_in]').attr('disabled', 'disabled').removeAttr('checked');
				$('input.option_weight').clearGhostText().val('').removeClass('v_positive_integer');
				break;
		}
		
		$('.lightswitch').lightSwitch();
	},
	
	addOption : function() {
	
		var id = qEE_MultipleChoice.lastOption;
		
		var html = "<li id='options_list_"+id+"'> "+
					"<div class='sortable_handle'></div> "+
					"<div style='margin-right: 10px; float: left;'>"+
					"<input name='option_"+id+"_weight' class='v_positive_integer option_weight' type='text' value=''/> "+
					"<input name='answer_radio' type='radio' value='"+id+"'/> "+
					"<input name='answer_checkbox_"+id+"' type='checkbox' value='1'/> "+
					"</div> "+
					"<textarea name='option_"+id+"_text'></textarea> "+
					"<textarea name='option_"+id+"_feedback'></textarea> "+
					"<div class='remove_button' onclick='qEE_MultipleChoice.removeOption("+id+")'></div> "+
					"</li>";
		
		$('#options_list').append(html);
		qEE_MultipleChoice.numOptions++;
		qEE_MultipleChoice.lastOption++;
		
		if (qEE_MultipleChoice.numOptions > 2) $("ul#options_list .remove_button").show();
		
		qEE_MultipleChoice.updateAnswerType();
		qEE_MultipleChoice.initGhostText();
	},
	removeOption : function(id) {
		
		$('#options_list_'+id).remove();
		qEE_MultipleChoice.numOptions--;
		qEE_MultipleChoice.lastOption--;
		
		if (qEE_MultipleChoice.numOptions <= 2) $("ul#options_list .remove_button").hide()
	},
	onPreSubmit : function() {
	
		// check for answer
		var answerType = $('#answer_type').val();
		var radioAnswer = $('input[name=answer_radio]:checked').val();
		
		if (answerType == {$one_answer} && !radioAnswer) {
			eequiz.showPopup(false, "Error, you must select an answer.");
			return false;
		}
		
		// create the added options field
		
		var addedOptions = $('#options_list').sortable('toArray');
		for (var i = 0; i < addedOptions.length; i++) addedOptions[i] = addedOptions[i].replace("options_list_", "");
		
		$("#question_form").append("<input type='hidden' name='added_options' value='"+addedOptions.join(" ")+"' />");
		
		$("#options_list textarea[name$=text]").clearGhostText();
		$("#options_list textarea[name$=feedback]").clearGhostText();
		$("#options_list input[name$=weight]").clearGhostText();
		
		return true; // false for testing
	},
	onPostSubmit : function() {
		
		qEE_MultipleChoice.initGhostText();
	},
	initGhostText : function() {
		
		$("#options_list input:text[name$=weight]").clearGhostText().ghostText("weight");
		$("#options_list textarea[name$=text]").clearGhostText().ghostText("option text");
		$("#options_list textarea[name$=feedback]").clearGhostText().ghostText("option feedback");
	}
};

$(document).ready(function(){

	$("#options_list").sortable({ 
		handle : '.sortable_handle',
		stop: function(event, ui){ 
			var i = 1;
			$("#options_list input:radio").each( function() {
				$(this).val(i);
				i++;
			});
			$("#options_list li").each( function() {
				
				var oldDisplay = $(this).css('display');
				$(this).css('display', 'none');
				var redrawFix = $(this).attr('offsetHeight');
				$(this).css('display', oldDisplay);
			});
		}
	});

	editQuestion.preSubmitFunction = qEE_MultipleChoice.onPreSubmit;
	editQuestion.postSubmitFunction = qEE_MultipleChoice.onPostSubmit;

	qEE_MultipleChoice.initGhostText();
	qEE_MultipleChoice.updateAnswerType();

	if (qEE_MultipleChoice.numOptions <= 2) $("ul#options_list .remove_button").hide()

});

//]]>
</script>
EOT;
		
		return $result;
	}
	
	
	function dbSync()
	{
		$this->settings['answer_type'] = $this->answer_type;
		$this->settings['allow_write_in'] = $this->allow_write_in;
		$this->settings['randomize_option_order'] = $this->randomize_option_order;
		$this->settings['feedback_prefix'] = $this->feedback_prefix;
		$this->settings['options'] = $this->options;
		
		return parent::dbSync();
	}
	
	
	
	
	
	
	
	
	
	
	function get_answer_from_post($mapping_id)
	{
		switch ($this->answer_type)
		{
			case MC_NO_ANSWER:
				
				if ($this->allow_write_in && $this->EE->input->get_post("mapping{$mapping_id}_user_answer") == 'write-in') {
					if (strlen($this->EE->input->get_post("mapping{$mapping_id}_user_write_in")) == 0) return NULL;
					else return "WRITE-IN:".$this->EE->input->get_post("mapping{$mapping_id}_user_write_in");
				}
				elseif (strlen($this->EE->input->get_post("mapping{$mapping_id}_user_answer")) > 0) return $this->EE->input->get_post("mapping{$mapping_id}_user_answer");
				else return NULL;
				break;
			case MC_ONE_ANSWER:
				if ($this->EE->input->get_post("mapping{$mapping_id}_user_answer") === FALSE) return NULL;
				else return $this->EE->input->get_post("mapping{$mapping_id}_user_answer");
				break;
			case MC_WEIGHTED_ANSWER:
				if ($this->EE->input->get_post("mapping{$mapping_id}_user_answer") === FALSE) return NULL;
				else return $this->EE->input->get_post("mapping{$mapping_id}_user_answer");
				break;
			case MC_MULTIPLE_ANSWERS:
				if ($this->EE->input->get_post("mapping{$mapping_id}_user_answer") === FALSE) return "";
				$answers = $this->EE->input->get_post("mapping{$mapping_id}_user_answer");
				sort($answers);
				return implode(" ", $answers);
				break;
		}
	}
	
}