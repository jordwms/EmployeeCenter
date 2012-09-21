<?php

require_once(QUIZ_ENGINE_PATH."Question.php");

class Matching extends Question
{

	//text
	//answer_index
	var $problems					= array();
	
	//array of texts
	var $choices					= array();
	
	
	function Matching()
	{
		parent::Question();
		
		$this->classname = "Matching";
		$this->answer = "";
		$this->last_answer = "";
	}
	
	function initFromDB($question_id, $data = 0)
	{
		if (!$question_id) return;
		
		parent::initFromDB($question_id, $data);
		
		$this->problems = (isset($this->settings["problems"])) ? $this->settings["problems"] : array();
		$this->choices = (isset($this->settings["choices"])) ? $this->settings["choices"] : array();
		$this->max_weight = $this->weight * count($this->problems);
	}
	
	function initFromPost()
	{
		parent::initFromPost();
		
		$answers = array();
		
		$added_problems = explode(" ", $this->EE->input->get_post('added_problems'));
		foreach ($added_problems as $p)
		{
			$this->problems[] = array(
				'text'			=> $this->EE->input->get_post("problem_{$p}"),
				'answer_index'	=> $this->EE->input->get_post("problem_answer_{$p}")
			);
			$answers[] = $this->EE->input->get_post("problem_answer_{$p}");
		}
		
		$this->answer = implode(" ", $answers);
		
		$added_choices = explode(" ", $this->EE->input->get_post('added_choices'));
		foreach ($added_choices as $c) $this->choices[] = $this->EE->input->get_post("choice_{$c}");
		
		$this->max_weight = $this->weight * count($this->problems);
	}
	
	function initUserData($mapping_id, $member_id, $anonymous)
	{
		parent::initUserData($mapping_id, $member_id, $anonymous);
		
		$answers = explode(" ", $this->last_answer);
		for ($i = count($answers); $i < count($this->problems); $i++) $answers[$i] = "0";
		
		$this->last_answer_formatted = "";
		foreach ($answers as $k => $v) $this->last_answer_formatted .= "problem ".($k+1)." - option {$v}<br />";
		
		//answer section
		$this->answer_section = "<div class='answer_section'>";
		$this->answer_section .= "<ol class='matching_problems'>";
		foreach ($this->problems as $k => $p)
		{
			$options = "";
			for ($i = 1; $i <= count($this->choices); $i++) $options .= "<option value='{$i}' ".($answers[$k] == $i ? "selected='selected' " : "").">{$i}</option>";
			
			$this->answer_section .= "<li><select name='mapping{$mapping_id}_user_answer_{$k}'>{$options}</select><span>{$p['text']}</span></li>";
		}
		$this->answer_section .= "</ol>";
		$this->answer_section .= "<ol class='matching_choices'>";
		foreach ($this->choices as $c) $this->answer_section .= "<li>{$c}</li>";
		$this->answer_section .= "</ol>";
		$this->answer_section .= "<div class='answer_footer'></div>";
		$this->answer_section .= "</div>";
		
		// feedback section
		$this->feedback_section = "<div class='feedback_section'>{$this->explanation}</div>";
		
		if ($this->attempts > 0)
		{
			$num_correct = 0;
			foreach ($this->problems as $k => $p) {
				if ($p['answer_index'] == $answers[$k]) $num_correct++;
			}
			
			// score
			$this->score = $num_correct * $this->weight;
			
			// correctness
			$this->correctness_class = "incorrect_mark";
			$correctness_message = "incorrect";
			if ($num_correct > 0 && $num_correct < count($this->problems)) {
				$this->correctness_class = "partially_correct_mark";
				$correctness_message = "{$num_correct} of ".count($this->problems)." correct";
			}
			elseif ($num_correct == count($this->problems)) {
				$this->correctness_class = "correct_mark";
				$correctness_message = "correct";
			}
			
			$this->correctness = "<div class='{$this->correctness_class}'><span class='mark_text'>{$correctness_message}</span></div>";
		}
	}
	
	
	
	
	
	// CONTROL PANEL FUNCTIONS
	
	function getEditData()
	{
		$result = parent::getEditData();
		
		// change weight descriptions
		$result['answer_settings'][0]['label'] = "Weight Per Problem";
		$result['answer_settings'][0]['description'] = "Each matching problem counts for this weight. For example, a matching question with 6 problems with a weight of 1 each will count for a total of 6 points (1 x 6).";
		$result['answer_settings'][0]['content'] .= "<span id='matching_weight_equation'>&nbsp;&nbsp;x ".count($this->problems)."</span>";
		
		
		$new_table = array(
			'title'		=> "Problems and Answers",
			'id'		=> "problems_and_answers",
			'columns'	=> array("Problems", "Choices"),
			'rows'		=> array()
		);
		
		$new_table['rows'][] = array(
			"<a href='javascript:void(0)' onclick='qEE_Matching.addProblem()'>Add A Problem</a>",
			"<a href='javascript:void(0)' onclick='qEE_Matching.addChoice()'>Add A Choice</a>",
		);
		
		
		$problem_index = 1;
		$problem_list = "<ul id='problem_list'>";
		foreach ($this->problems as $p)
		{
			$choice_options = "";
			foreach ($this->choices as $k => $c) $choice_options .= "<option value='".($k+1)."' ".($k+1 == $p['answer_index'] ? "selected='selected' " : "").">choice ".($k+1)."</option>";
			
			$problem_list .= "<li id='problem_list_{$problem_index}'>".
									"<div class='sortable_handle'></div>".
									"<select id='problem_answer_{$problem_index}' name='problem_answer_{$problem_index}'>".$choice_options."</select>".
									"<textarea name='problem_{$problem_index}'>{$p['text']}</textarea>".
									"<div class='remove_button' onclick='qEE_Matching.removeProblem({$problem_index})'></div>".
								"</li>";
			$problem_index++;
		}
		$problem_list .= "</ul>";
		
		
		$choice_index = 1;
		$choice_list = "<ol id='choice_list'>";
		foreach ($this->choices as $c)
		{
			$choice_list .= "<li id='choice_list_{$choice_index}'><div class='match_wrap'>".
								"<div class='sortable_handle'></div>".
								"<textarea name='choice_{$choice_index}'>{$c}</textarea>".
								"<div class='remove_button' onclick='qEE_Matching.removeChoice({$choice_index})'></div></div>".
							"</li>";
			$choice_index++;
		}
		$choice_list .= "</ol>";
		
		
		$new_table['rows'][] = array(
			$problem_list, 
			$choice_list
		);
		$result['other_tables'][] = $new_table;
		
		
		$num_problems = count($this->problems);
		$num_choices = count($this->choices);
		
		$result['extra'] .= <<<EOT

<input type='hidden' name='num_options' value='2' />

<script type="text/javascript">
//<![CDATA[

var qEE_Matching = {

	numProblems : {$num_problems},
	numChoices : {$num_choices},
	
	lastProblem : {$problem_index},
	lastChoice : {$choice_index},

	addProblem : function() {
	
		var options = "";
		for (var i = 1; i <= qEE_Matching.numChoices; i++) options += "<option value='"+i+"'>choice "+i+"</option>";
		
		var new_id = qEE_Matching.lastProblem;
		var html = "<li id='problem_list_"+new_id+"'>"+
						"<div class='sortable_handle'></div>"+
						"<select id='problem_answer_"+new_id+"' name='problem_answer_"+new_id+"'>"+options+"</select>"+
						"<textarea name='problem_"+new_id+"'></textarea>"+
						"<div class='remove_button' onclick='qEE_Matching.removeProblem("+new_id+")'></div>"+
					"</li>";
		
		$('#problem_list').append(html);
		qEE_Matching.numProblems++;
		qEE_Matching.lastProblem++;
		
		$("#matching_weight_equation").html("&nbsp;&nbsp;x "+qEE_Matching.numProblems);
		
		qEE_Matching.initGhostText();
	},
	removeProblem : function(id) {
		
		$("#problem_list_"+id).remove();
		qEE_Matching.numProblems--;
		
		$("#matching_weight_equation").html("&nbsp;&nbsp;x "+qEE_Matching.numProblems);
	},
	addChoice : function() {
		
		// add to answer dropdowns
		$("#problem_list select").append("<option value='"+(qEE_Matching.numChoices+1)+"'>choice "+(qEE_Matching.numChoices+1)+"</option>");
		
		// add choice html
		var new_id = qEE_Matching.lastChoice;
		var html = "<li id='choice_list_"+new_id+"'><div class='match_wrap'>"+
						"<div class='sortable_handle'></div>"+
						"<textarea name='choice_"+new_id+"'></textarea>"+
						"<div class='remove_button' onclick='qEE_Matching.removeChoice("+new_id+")'></div></div>"+
					"</li>";
		
		$('#choice_list').append(html);
		qEE_Matching.numChoices++;
		qEE_Matching.lastChoice++;
		
		qEE_Matching.initGhostText();
	},
	removeChoice : function(id) {
		
		$("#choice_list_"+id).remove();
		qEE_Matching.numChoices--;
		$("#problem_list select option:last-child").remove();
	},
	onPreSubmit : function() {
		
		// fabricate the added problem and choice id fields
		
		var addedProblems = $('#problem_list').sortable('toArray');
		for (var i = 0; i < addedProblems.length; i++) addedProblems[i] = addedProblems[i].replace("problem_list_", "");
		var addedChoices = $('#choice_list').sortable('toArray');
		for (var i = 0; i < addedChoices.length; i++) addedChoices[i] = addedChoices[i].replace("choice_list_", "");
		
		$("#question_form").append("<input type='hidden' name='added_problems' value='"+addedProblems.join(" ")+"' />");
		$("#question_form").append("<input type='hidden' name='added_choices' value='"+addedChoices.join(" ")+"' />")
		
		$("#problem_list textarea[name^=problem_]").clearGhostText();
		$("#choice_list textarea[name^=choice_]").clearGhostText();
		
		return true; // false for testing
	},
	onPostSubmit : function() {
		
		qEE_Matching.initGhostText();
	},
	initGhostText : function() {
		
		$("#problem_list textarea[name^=problem_]").clearGhostText();
		$("#choice_list textarea[name^=choice_]").clearGhostText();
		$("#problem_list textarea[name^=problem_]").ghostText("problem text");
		$("#choice_list textarea[name^=choice_]").ghostText("choice text");
	}
};

$(document).ready(function(){

	$("#problem_list").sortable({ 
		handle : '.sortable_handle',
		stop: function(event, ui){ 
			$("#problem_list li").each( function() {
				var oldDisplay = $(this).css('display');
				$(this).css('display', 'none');
				var redrawFix = $(this).attr('offsetHeight');
				$(this).css('display', oldDisplay);
			});
		}
	});
	$("#choice_list").sortable({ 
		handle : '.sortable_handle',
		stop: function(event, ui){ 
			$("#choice_list li").each( function() {
				var oldDisplay = $(this).css('display');
				$(this).css('display', 'none');
				var redrawFix = $(this).attr('offsetHeight');
				$(this).css('display', oldDisplay);
			});
		}
	});

	qEE_Matching.initGhostText();

	editQuestion.preSubmitFunction = qEE_Matching.onPreSubmit;
	editQuestion.postSubmitFunction = qEE_Matching.onPostSubmit;

});

//]]>
</script>
EOT;
		
		return $result;
	}
	
	function dbSync()
	{
		$this->settings['problems'] = $this->problems;
		$this->settings['choices'] = $this->choices;
		
		return parent::dbSync();
	}
	
	
	
	
	
	
	
	
	// TEMPLATE FUNCTIONS
	
	function get_answer_from_post($mapping_id)
	{
		$answers = array();
		
		for ($i = 0; $i < count($this->problems); $i++) $answers[] = $this->EE->input->get_post("mapping{$mapping_id}_user_answer_{$i}");
		
		return implode(" ", $answers);
	}
}