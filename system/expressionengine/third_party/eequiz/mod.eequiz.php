<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
=====================================================
File: mod.eequiz.php
-----------------------------------------------------
Purpose: eeQuiz Module Class
=====================================================
*/

require_once("common.php");

class Eequiz {
	
	var $front_end_messages = array();
	
    var $return_data = '';
	var $submit_answer_url = FALSE;
	var $get_question_url = FALSE;
	
	var $cookie_anon_member_id = 0;
	var $cookie_anon_member_key = 0;
	
    // -------------------------------------
    //  Constructor
    // -------------------------------------

    function Eequiz()
    {   
        $this->EE =& get_instance();
		
		require("front_end_messages.php");
    }
	
	
	
	// -------------------------------------
    //  quizzes
	//  Display quiz info
    // -------------------------------------
	
	function quizzes()
	{
		//$this->_clear_cache();
		
		$disabled = $this->EE->TMPL->fetch_param('disable');
		$quiz_id = $this->EE->TMPL->fetch_param('quiz_id');
		$url_title = $this->EE->TMPL->fetch_param('url_title');
		if (!$quiz_id && $url_title) $quiz_id = $this->_get_quiz_id($url_title);
		$forced_member_id = $this->EE->TMPL->fetch_param('member_id');
		$tags = $this->EE->TMPL->fetch_param('tags');
		
		$disable_grades = (strstr("grades", $disabled) !== FALSE);
		
		// build query
		
		$wheres = array();
		
		$tag_where = "";
		if ($tags) {
			$template_tags_array = explode("|", $tags);
			$tag_inner_wheres = array();
			foreach ($template_tags_array as $t) $tag_inner_wheres[] = " tags LIKE '%{$t}%' ";
			$tag_where = " (".implode(" OR ", $tag_inner_wheres).") ";
			$wheres[] = $tag_where;
		}
		
		if ($quiz_id) $wheres[] = "quiz_id={$quiz_id}";
		
		$quizzes = $this->EE->db->query("SELECT * FROM exp_eequiz_quizzes ".
			(count($wheres) > 0 ? "WHERE ".implode(" AND ", $wheres) : ""));
		
		// build tag data
		
		$repeat_tagdata = $this->EE->TMPL->tagdata;
		
		foreach ($quizzes->result_array() as $q_data)
		{
			$quiz = new Quiz();
			$quiz->initFromDB($q_data['quiz_id'], $q_data);
			
			if ($tags) {
				
				// first, double check tags, because there might be false positives (ex, if tag is "mathlete", it would've matched "math")
				$good_tag_match = FALSE;
				$template_tags_array = explode("|", $tags);
				$test_tags = explode(" ", $quiz->tags);
				foreach ($test_tags as $t) {
					if (in_array($t, $template_tags_array)) $good_tag_match = TRUE;
				}
				if (!$good_tag_match) continue;
			}
			
			$tagdata = $repeat_tagdata;
			
			$cond = array();
			$cond['quiz_title'] = $quiz->title;
			$cond['quiz_description'] = $quiz->description;
			$cond['passing_grade'] = $quiz->passing_grade;
			$cond['one_at_a_time'] = $quiz->one_at_a_time;
			$cond['all_at_once'] = !$quiz->one_at_a_time;
			$cond['anonymous'] = $quiz->anonymous;
			$cond['enabled'] = !$quiz->disabled;
			
			if (!$disable_grades)
			{
				$quiz->initUserData($forced_member_id ? $forced_member_id : $this->_get_active_member_id($quiz), $this->_do_anonymous());
				
				$cond['attempted_all'] = $quiz->attempted_all;
				$cond['attempted_all_mandatory'] = $quiz->attempted_all_mandatory;
				$cond['all_correct_or_no_more_attempts'] = $quiz->correct_or_no_more_attempts;
				$cond['passing'] = ($quiz->percent >= $quiz->passing_grade);
				$cond['failing'] = !$cond['passing'];
				$cond['grade_score'] = $quiz->score;
				$cond['grade_percent'] = $quiz->percent;
				$cond['max_score'] = $quiz->max_score;
			}
			
			$tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);
			
			foreach ($this->EE->TMPL->var_single as $key => $val)
			{
				switch ($key)
				{
					case 'quiz_id':				$tagdata = $this->EE->TMPL->swap_var_single($key, $quiz->quiz_id, $tagdata); break;
					case 'quiz_title':			$tagdata = $this->EE->TMPL->swap_var_single($key, $quiz->title, $tagdata); break;
					case 'quiz_description':	$tagdata = $this->EE->TMPL->swap_var_single($key, $quiz->description, $tagdata); break;
					case 'quiz_tags':			$tagdata = $this->EE->TMPL->swap_var_single($key, $quiz->tags, $tagdata); break;
					case 'passing_grade':		$tagdata = $this->EE->TMPL->swap_var_single($key, $quiz->passing_grade, $tagdata); break;
					
					case 'grade_percent':		if (!$disable_grades) $tagdata = $this->EE->TMPL->swap_var_single($key, $quiz->percent, $tagdata); break;
					case 'grade_score':			if (!$disable_grades) $tagdata = $this->EE->TMPL->swap_var_single($key, $quiz->score, $tagdata); break;
					case 'max_score':			if (!$disable_grades) $tagdata = $this->EE->TMPL->swap_var_single($key, $quiz->max_score, $tagdata); break;
					
					default:					break;
				}
			}
			
			if (preg_match_all("/".LD."last_answer_time\s+format=([\"'])([^\\1]*?)\\1".RD."/s", $tagdata, $matches)) {
				for ($j = 0; $j < count($matches[0]); $j++)
					$tagdata = str_replace($matches[0][$j], gmdate($matches[2][$j], $quiz->last_time), $tagdata);
			}
			
			$this->return_data .= $tagdata;
		}
		
		return $this->return_data;
	}
	
	
	
	
	// -------------------------------------
    //  questions
	//  Display the questions in a specified quiz
    // -------------------------------------
	
	function questions()
	{
		$quiz_id = $this->EE->TMPL->fetch_param('quiz_id');
		$url_title = $this->EE->TMPL->fetch_param('url_title');
		if (!$quiz_id && $url_title) $quiz_id = $this->_get_quiz_id($url_title);
		$retake = strtolower($this->EE->TMPL->fetch_param('retake'));
		$retake = ($retake == "yes" || $retake == "true" || $retake == "retake");
		$include_js = !(	strcasecmp($this->EE->TMPL->fetch_param('include_js'), "no") == 0 || 
							strcasecmp($this->EE->TMPL->fetch_param('include_js'), "false") == 0);
		$js_on_update = $this->EE->TMPL->fetch_param('js_on_update') ? $this->EE->TMPL->fetch_param('js_on_update') : "false";
		$js_on_load_start = $this->EE->TMPL->fetch_param('js_on_load_start') ? $this->EE->TMPL->fetch_param('js_on_load_start')."()" : "";
		$js_on_load_end = $this->EE->TMPL->fetch_param('js_on_load_end') ? $this->EE->TMPL->fetch_param('js_on_load_end')."()" : "";
		$section = $this->EE->TMPL->fetch_param('section');
		$continue = strtolower($this->EE->TMPL->fetch_param('continue'));
		$continue = ($continue == "yes" || $continue == "true" || $continue == "retake");
		
		// make sure quiz is valid
		
		$quiz = new Quiz();
		$quiz->initFromDB($quiz_id);
		if (!$quiz->quiz_id) return "Error, invalid quiz_id/url_title!";
		
		$active_member_id = $this->_get_active_member_id($quiz);
		
		// make sure member is valid (false if invalid)
		if (!$active_member_id) return "Error: you must be logged in to take this quiz.";
		
		if ($retake) {
		
			$retake_mappings = array();
			foreach ($quiz->mappings as $qm) $retake_mappings[] = $qm["mapping_id"];
			
			if ($this->_do_anonymous())
				$this->EE->db->query("DELETE FROM exp_eequiz_anonymous_progress WHERE anonymous_member_id={$active_member_id} AND mapping_id IN (".implode(", ", $retake_mappings).")"); 
			else 
				$this->EE->db->query("DELETE FROM exp_eequiz_progress WHERE member_id={$active_member_id} AND mapping_id IN (".implode(", ", $retake_mappings).")");
		}
		
		
		
		// Set up start and end info
		
		$start = 0;
		$end = 1000;
		
		if (!$quiz->randomize)
		{
			if ($section)
			{
				if (preg_match('/^(\d+)\-(\d+)$/', $section, $matches))
				{
					if (count($matches) == 3)
					{
						if ($matches['1'] > $start) $start = $matches['1']-1;
						if ($matches['2'] < $end) $end = $matches['2']-1;
					}
				}
			}
			
			$limit = ($end - $start + 1)*1;
			if ($quiz->one_at_a_time) $limit = 1;
		}
		
		// Obtain all question information
		
		$active_member_id = $this->_get_active_member_id($quiz);
		
		$num_questions = $quiz->num_questions;
		
		if ($continue && $quiz->one_at_a_time) {
		
			$prefix = $this->_do_anonymous() ? "anonymous_" : "";
			$last_answered = $this->EE->db->query("
				SELECT p.*, m.*
				FROM exp_eequiz_{$prefix}progress AS p INNER JOIN exp_eequiz_mappings AS m ON p.mapping_id=m.mapping_id
				WHERE m.quiz_id={$quiz->quiz_id} AND p.{$prefix}member_id={$active_member_id}
				ORDER BY m.`order` DESC LIMIT 1
			");
			
			if ($last_answered->num_rows() > 0) {
				
				$last_answered = $last_answered->row_array();
				
				$start = $last_answered["order"];
				if ($start >= $quiz->num_questions) $start = $quiz->num_questions-1;
				$limit = 1;
			}
		}
		
		$questions = $this->EE->db->query("SELECT m.*, q.* FROM exp_eequiz_mappings AS m INNER JOIN exp_eequiz_questions AS q ON m.question_id=q.question_id
										WHERE m.quiz_id={$quiz->quiz_id} 
										ORDER BY m.`order` LIMIT {$start},{$limit}");
		
		//if ($quiz->randomize) $questions->result = $this->_randomize_mappings($questions->result, $quiz_id);
		
		// --------------------------------
		//  Tag Parsing
		// --------------------------------
		
		$r = "";
		
		if ($include_js) $r .= $this->javascript($js_on_update, $js_on_load_start, $js_on_load_end);
		
		$quiz->initUserData($active_member_id, $this->_do_anonymous());
		
		foreach ($questions->result_array() as $q)
		{
			require_once(QUESTION_TYPES_PATH.$q['classname'].".php");
			$question = new $q['classname']();
			$question->initFromDB($q['question_id'], $q);
			$question->initUserData($q['mapping_id'], $active_member_id, $this->_do_anonymous());
			
			$r .= $this->_construct_question_html($quiz, $question);
		}
		
		if (!$quiz->one_at_a_time && $quiz->show_submit_all) {
			
			$r .= "<input class='eequiz_submit_all_button' type='button' value='Submit All Answers' onclick='eequiz.submitAllQuestions()' />";
		}
		
		return $this->return_data = $r;
	}
	
	
	function javascript($js_on_update="", $js_on_load_start="", $js_on_load_end="")
	{
		if ($js_on_update==="")
			$js_on_update = $this->EE->TMPL->fetch_param('js_on_update') ? $this->EE->TMPL->fetch_param('js_on_update') : "false";
		if ($js_on_load_start==="")
			$js_on_load_start = $this->EE->TMPL->fetch_param('js_on_load_start') ? $this->EE->TMPL->fetch_param('js_on_load_start')."()" : "";
		if ($js_on_load_end==="")
			$js_on_load_end = $this->EE->TMPL->fetch_param('js_on_load_end') ? $this->EE->TMPL->fetch_param('js_on_load_end')."()" : "";
		
		$this->_set_action_urls();
		
		$r = <<<EOT
<script type="text/javascript">
//<![CDATA[

var eequiz = {

	getQuestionURL : "{$this->get_question_url}",
	submitQuestionURL : "{$this->submit_answer_url}",
	replacedQuestionBlock : "",
	isLoading : false,
	onQuestionUpdate : {$js_on_update},

	submitQuestion : function(form) {
		
		if (eequiz.isLoading) return false;
		
		$("#eequiz_"+form.mapping_id.value).addClass("eequiz_loading");
		eequiz.onLoadStart();
		
		$.post($(form).attr('action'), $(form).serialize(), eequiz.submitQuestionCallback, "json");
		return false;
	},
	
	submitQuestionCallback : function(json) {
		
		eequiz.onLoadEnd();
		
		if (!json.success) {
			$("#eequiz_"+json.mapping_id).removeClass("eequiz_loading");
			alert(json.message);
			return;
		}
		
		if (json.auto_advance) $('#eequiz_'+json.last_mapping_id).replaceWith(json.assets);
		else $('#eequiz_'+json.mapping_id).replaceWith(json.assets);
		
		if (eequiz.onQuestionUpdate) eequiz.onQuestionUpdate(json);
	},
	
	gotoQuestion : function(currentMappingID, newNumber) {
		
		if (eequiz.isLoading) return false;
		
		$("#eequiz_"+currentMappingID).addClass("eequiz_loading");
		eequiz.onLoadStart();
		
		eequiz.replacedQuestionBlock = "eequiz_"+currentMappingID;
		$.post(eequiz.getQuestionURL, "mapping_id="+currentMappingID+"&new_number="+newNumber, eequiz.gotoQuestionCallback, "json");
	},
	
	gotoQuestionCallback : function(json) {
		
		eequiz.onLoadEnd();
		
		if (!json.success) {
			$('#'+eequiz.replacedQuestionBlock).removeClass("eequiz_loading");
			alert(json.message);
			return;
		}
		$('#'+eequiz.replacedQuestionBlock).replaceWith(json.assets);
		if (eequiz.onQuestionUpdate) eequiz.onQuestionUpdate(json);
	},
	
	submitAllQuestions : function() {
		
		if (eequiz.isLoading) return false;
		
		var mappings = [];
		var vars = [];
		var quiz_id = 0;
		
		for (var form_i = 0; form_i < document.forms.length; form_i++) {
			
			var f = document.forms[form_i];
			if (!($(f).attr("id"))) continue;
			if (($(f).attr("id")).match(/^question_form_\d+$/)) {
				
				if (quiz_id <= 0) quiz_id = f.quiz_id.value;
				
				mappings.push(f.mapping_id.value);
				
				var outerObj = $(f).serializeArray();
				for (var i = 0; i < outerObj.length; i++) {
				
					var innerObj = outerObj[i];
					
					if (innerObj.name == "mapping_id" || innerObj.name == "quiz_id") continue;
					
					var name = encodeURI(innerObj.name);
					
					var val = encodeURI(innerObj.value);
					val = val.replace(/\+/g, "%2B");
					val = val.replace(/&/g, "%26");
					val = val.replace(/\?/g, "%3F");
					val = val.replace(/=/g, "%3D");
					val = val.replace(/;/g, "%3B");
					val = val.replace(/:/g, "%3A");
					val = val.replace(/\//g, "%2F");
					val = val.replace(/,/g, "%2C");
					val = val.replace(/@/g, "%40");
					val = val.replace(/#/g, "%23");
					val = val.replace(/[\$]/g, "%24");
					val = val.replace(/%20/g, "+");
					
					vars.push(name+"="+val);
				}
			}
		}
		
		vars.push("all_mappings="+mappings.join("_"));
		vars.push("quiz_id="+quiz_id);
		
		//alert(vars.join("&"));
		
		eequiz.onLoadStart();
		$("div.eequiz").addClass("eequiz_loading");

		$.post(eequiz.submitQuestionURL, vars.join("&"), eequiz.submitAllQuestionsCallback, "json");
	},
	
	submitAllQuestionsCallback : function(json) {
		
		eequiz.onLoadEnd();
		$("div.eequiz").removeClass("eequiz_loading");
		
		if (!json.success) {
			alert(json.message);
			return;
		}
		
		for (var i in json.assets) $('#eequiz_'+i).replaceWith(json.assets[i])
		
		if (eequiz.onQuestionUpdate) eequiz.onQuestionUpdate(json);
	},
	
	
	
	onLoadStart : function() {
		eequiz.isLoading = true;
		{$js_on_load_start};
	},
	onLoadEnd : function() {
		eequiz.isLoading = false;
		{$js_on_load_end};
	}
};

//]]>
</script>
EOT;

		return $this->return_data = $r;
	}
	
	
	
	// -------------------------------------
	// results
	// -------------------------------------
	
	function results()
	{
		$this->return_data = "";
		$quiz_id = $this->EE->TMPL->fetch_param('quiz_id');
		$url_title = $this->EE->TMPL->fetch_param('url_title');
		if (!$quiz_id && $url_title) $quiz_id = $this->_get_quiz_id($url_title);
		$unrolled = strtolower($this->EE->TMPL->fetch_param('unrolled'));
		$unrolled = ($unrolled == "yes" || $unrolled == "true" || $unrolled == "on");
		
		if (!$quiz_id) return $this->return_data;
		
		$base_tagdata = $tagdata = $this->EE->TMPL->tagdata;
		
		$quiz = new Quiz();
		$quiz->initFromDB($quiz_id);
		
		$member_id = $this->_get_active_member_id($quiz);
		
		$quiz->initUserData($member_id, $this->_do_anonymous());
		
		$cond = array();
		
		foreach ($quiz->questions as $question)
		{
			$order = $quiz->mappings_to_orders[$question->mapping_id];
			$prefix = $unrolled ? "q{$order}_" : "";
			$correctness = preg_replace("/_mark/", "", $question->correctness_class);
			$correctness = preg_replace("/_/", " ", $correctness);
			$last_answer = $question->last_answer;
			if ($question->classname == "FillInTheBlank" || $question->classname == "Essay") $last_answer = $question->last_answer_formatted;
			if ($question->classname == "MultipleChoice" && isset($question->settings["allow_write_in"]))
				if ($question->settings["allow_write_in"])
					$last_answer = $question->last_answer_formatted;
					
			if (!$unrolled) $tagdata = $base_tagdata;
			
			// parse out options tag pair
					
			if ($question->classname == "MultipleChoice") {
			
				preg_match_all("/".LD."{$prefix}options".RD."(.*?)".LD."\/{$prefix}options".RD."/sm", $tagdata, $matches);
					
				$option_answers = explode(" ", $question->answer); $user_answers = explode(" ", $question->last_answer);
				if ($question->allow_write_in) { $option_answers = array(); $user_answers = array(); }
				
				foreach ($matches[0] as $k => $full_match) {
				
					$tagpair_base_data = $matches[1][$k];
					$tagpair_result = "";
					
					foreach ($question->options as $o_number => $o) {
						
						$tagpair_cond = array(
							"option_number"			=> $o_number+1,
							"option_is_selected"	=> in_array($o_number+1, $user_answers),
							"option_is_answer"		=> in_array($o_number+1, $option_answers)
						);
						$tagpair_vars = array(
							"option_number"		=> $o_number+1,
							"option_text"		=> $o["text"],
							"option_feedback"	=> $o["feedback"],
							"option_weight"		=> $o["weight"]
						);
						$tagpair_result .= $this->_standalone_parse_tagdata($tagpair_base_data, $tagpair_cond, $tagpair_vars);
					}
					
					$tagdata = str_replace($full_match, $tagpair_result, $tagdata);
				}
			}
			else $tagdata = preg_replace("/".LD."{$prefix}options".RD."(.*?)".LD."\/{$prefix}options".RD."/sm", "", $tagdata);
			
			// parse out options matching_problems and matching_choices
			
			if ($question->classname == "Matching") {
			
				preg_match_all("/".LD."{$prefix}matching_problems".RD."(.*?)".LD."\/{$prefix}matching_problems".RD."/sm", $tagdata, $matches);
					
				$problem_answers = explode(" ", $question->answer);
				$user_answers = explode(" ", $question->last_answer);
				
				foreach ($matches[0] as $k => $full_match) {
				
					$tagpair_base_data = $matches[1][$k];
					$tagpair_result = "";
					
					foreach ($question->problems as $p_number => $p) {
						
						$tagpair_vars = array(
							"problem_number"		=> $p_number+1,
							"problem_text"			=> $p["text"],
							"problem_answer"		=> $p["answer_index"],
							"problem_selection"		=> (count($user_answers) > $p_number) ? $user_answers[$p_number] : ""
						);
						$tagpair_cond = array(
							"problem_number"		=> $tagpair_vars["problem_number"],
							"problem_answer"		=> $tagpair_vars["problem_answer"],
							"problem_selection"		=> $tagpair_vars["problem_selection"]
						);
						$tagpair_result .= $this->_standalone_parse_tagdata($tagpair_base_data, $tagpair_cond, $tagpair_vars);
					}
					
					$tagdata = str_replace($full_match, $tagpair_result, $tagdata);
				}
				
				preg_match_all("/".LD."{$prefix}matching_choices".RD."(.*?)".LD."\/{$prefix}matching_choices".RD."/sm", $tagdata, $matches);
				
				foreach ($matches[0] as $k => $full_match) {
				
					$tagpair_base_data = $matches[1][$k];
					$tagpair_result = "";
					
					foreach ($question->choices as $c_number => $c) {
						
						$tagpair_vars = array(
							"choice_number"		=> $c_number+1,
							"choice_text"		=> $c
						);
						$tagpair_cond = array(
							"choice_number"			=> $tagpair_vars["choice_number"]
						);
						$tagpair_result .= $this->_standalone_parse_tagdata($tagpair_base_data, $tagpair_cond, $tagpair_vars);
					}
					
					$tagdata = str_replace($full_match, $tagpair_result, $tagdata);
				}
			}
			else {
				$tagdata = preg_replace("/".LD."{$prefix}matching_problems".RD."(.*?)".LD."\/{$prefix}matching_problems".RD."/sm", "", $tagdata);
				$tagdata = preg_replace("/".LD."{$prefix}matching_choices".RD."(.*?)".LD."\/{$prefix}matching_choices".RD."/sm", "", $tagdata);
			}
			
			$cond["{$prefix}number"] = $order;
			$cond["{$prefix}type"] = substr(strtolower(preg_replace("/([A-Z])/", '_\1', $question->classname)), 1);
			$cond["{$prefix}user_answer"] = $question->last_answer;
			$cond["{$prefix}correct_answer"] = $question->answer;
			$cond["{$prefix}score"] = $question->score;
			$cond["{$prefix}max_score"] = $question->max_weight;
			$cond["{$prefix}num_attempts"] = $question->attempts;
			$cond["{$prefix}max_attempts"] = $question->max_attempts;
			$cond["{$prefix}correct"] = $question->correctness_class == "correct_mark";
			$cond["{$prefix}incorrect"] = $question->correctness_class == "incorrect_mark";
			$cond["{$prefix}partially_correct"] = $question->correctness_class == "partially_correct_mark";
			
			if (!$unrolled) $tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);
			
			foreach ($this->EE->TMPL->var_single as $key => $val)
			{
				switch ($key)
				{
					case "{$prefix}number":				$tagdata = $this->EE->TMPL->swap_var_single($key, $order, $tagdata); break;
					case "{$prefix}title":				$tagdata = $this->EE->TMPL->swap_var_single($key, $question->title, $tagdata); break;
					case "{$prefix}text":				$tagdata = $this->EE->TMPL->swap_var_single($key, $question->text, $tagdata); break;
					case "{$prefix}explanation":		$tagdata = $this->EE->TMPL->swap_var_single($key, $question->explanation, $tagdata); break;
					
					case "{$prefix}correct_answer":			$tagdata = $this->EE->TMPL->swap_var_single($key, $question->answer, $tagdata); break;
					case "{$prefix}user_answer":			$tagdata = $this->EE->TMPL->swap_var_single($key, $last_answer, $tagdata); break;
					case "{$prefix}user_answer_formatted":	$tagdata = $this->EE->TMPL->swap_var_single($key, $question->last_answer_formatted, $tagdata); break;
					case "{$prefix}score":					$tagdata = $this->EE->TMPL->swap_var_single($key, $question->score, $tagdata); break;
					case "{$prefix}max_score":				$tagdata = $this->EE->TMPL->swap_var_single($key, $question->max_weight, $tagdata); break;
					case "{$prefix}num_attempts":			$tagdata = $this->EE->TMPL->swap_var_single($key, $question->attempts, $tagdata); break;
					case "{$prefix}max_attempts":			$tagdata = $this->EE->TMPL->swap_var_single($key, $question->max_attempts, $tagdata); break;
					case "{$prefix}correctness":			$tagdata = $this->EE->TMPL->swap_var_single($key, $correctness, $tagdata); break;
					
					default:						break;
				}
			}
			
			if (!$unrolled) $this->return_data .= $tagdata;
		}
		
		if ($unrolled) $this->return_data = $this->EE->functions->prep_conditionals($tagdata, $cond);
		
		return $this->return_data;
	}
	
	
	
	
	
	// -------------------------------------
	// answer_data
	// -------------------------------------
	
	function answer_data()
	{
		$this->return_data = "";
		$quiz_id = $this->EE->TMPL->fetch_param('quiz_id'); // pipe separated
		
		$base_tagdata = $tagdata = $this->EE->TMPL->tagdata;
		
		// query database
		
		$quiz_id_where = "";
		if ($quiz_id) {
			$quiz_id_where = "WHERE quiz_id ";
			if (preg_match("/^not /i", $quiz_id)) $quiz_id_where .= "NOT ";
			$quiz_id_where .= "IN ";
			$quiz_id_where .= "(".implode(", ", explode("|", $quiz_id)).")";
		}
		
		$user_score = 0;
		$max_score = 0;
		$avg_scores = array();
		$passing_all = TRUE;
		
		$quizzes = $this->EE->db->query("SELECT * FROM exp_eequiz_quizzes {$quiz_id_where}");
		foreach ($quizzes->result_array() as $q_data)
		{
			$quiz = new Quiz();
			$quiz->initFromDB($q_data['quiz_id'], $q_data);
			$quiz->initUserData($this->_get_active_member_id($quiz), $this->_do_anonymous());
			
			if ($quiz->percent < $quiz->passing_grade) $passing_all = FALSE;
			
			if (!$quiz->attempted_any) continue;
			
			$max_score += $quiz->max_score;
			$user_score += $quiz->score;
		}
		
		$answer_data = $this->EE->db->query("
				SELECT member_id, SUM(score) AS member_score, AVG(percent) AS member_percent 
				FROM exp_eequiz_cached_scores 
				{$quiz_id_where} 
				GROUP BY member_id"
			);
		$num_before = 0;
		$avg_score = 0;
		$avg_percent = 0;
		foreach ($answer_data->result_array() as $a_data)
		{
			if ($a_data["member_score"] <= $user_score) $num_before++;
			$avg_score += $a_data["member_score"];
			$avg_percent += $a_data["member_percent"];
		}
		if ($answer_data->num_rows() > 0) {
			$avg_score /= $answer_data->num_rows();
			$avg_percent /= $answer_data->num_rows();
		}
			
		// create tagdata
		
		$tagdata = $base_tagdata;
		
		$vars = array(
			"max_score"			=> $max_score,
			"user_score"		=> $user_score,
			"user_percent"		=> ($max_score > 0) ? number_format(100*$user_score/$max_score, 0, ".", "") : 0,
			"user_percentile"	=> ($answer_data->num_rows > 0) ? number_format(100*$num_before/$answer_data->num_rows(), 0, ".", "") : 0,
			
			"num_users"			=> $answer_data->num_rows(),
			"average_percent"	=> number_format(100*$avg_percent, 0, ".", ""),
			"average_score"		=> number_format($avg_score, 1, ".", "")
		);
		
		$conds = $vars;
		$conds["passing_all"] = $passing_all;
		
		$tagdata = $this->_standalone_parse_tagdata($base_tagdata, $conds, $vars);
		
		// done
		
		$this->return_data .= $tagdata;
		
		return $this->return_data;
	}
	
	
	
	// -------------------------------------
    //  ajax_submit_question, ajax_get_question
	//  Ajax methods for submitting or getting a question
    // -------------------------------------
	
	function ajax_submit_question()
	{
		$auto_advance = $this->EE->input->get_post("auto_advance");
		$mapping_id = $this->EE->input->get_post('mapping_id');
		$answer = $this->EE->input->get_post('user_answer');
		
		$json = array(
			'success'		=> FALSE,
			'message'		=> 'An unknown error occurred.',
			'mapping_id'	=> $mapping_id
		);
		
		if ($this->EE->input->get_post('all_mappings')) {
			
			$this->_submit_all();
			exit();
		}
		
		$question_info = $this->EE->db->query("SELECT * FROM exp_eequiz_mappings AS m 
										INNER JOIN exp_eequiz_questions AS q ON m.question_id=q.question_id
										WHERE m.mapping_id={$mapping_id}");
		
		// make sure valid question exists for this mapping
		if ($question_info->num_rows() == 0) {
			$json['message'] = "Error: invalid question-quiz mapping.";
			echo json_encode($json);
			exit();
		}
		$question_info = $question_info->row_array();
		
		// make sure valid quiz exists for this mapping
		$quiz = new Quiz();
		$quiz->initFromDB($question_info['quiz_id']);
		if (!$quiz->quiz_id) {
			$json['message'] = "Error: invalid quiz for the provided quiz-question mapping.";
			echo json_encode($json);
			exit();
		}
		
		$member_id = $this->_get_active_member_id($quiz);
		
		// make sure quiz is enabled
		if ($quiz->disabled) {
			$json['message'] = $this->front_end_messages["error_disabled"];//"Error: this quiz is currently disabled.";
			echo json_encode($json);
			exit();
		}
		
		// make sure member is logged in if tracking answers
		if (!$member_id) {
			$json['message'] = $this->front_end_messages["error_logged_out"];//"Error: you must be logged in to take this quiz.";
			echo json_encode($json);
			exit();
		}
		
		require_once(QUESTION_TYPES_PATH.$question_info['classname'].".php");
		$question = new $question_info['classname']();
		$question->initFromDB($question_info['question_id'], $question_info);
		$answer = $question->get_answer_from_post($mapping_id);
		
		// make sure user has entered an answer
		if ($answer === NULL) {
			$json['message'] = $this->front_end_messages["error_need_answer"];//"Error: you must enter an answer before submitting.";
			echo json_encode($json);
			exit();
		}
		
		$question->initUserData($mapping_id, $member_id, $this->_do_anonymous());
		
		// make sure user has attempts left
		if ($question->attempts >= $question->max_attempts && $question->max_attempts > 0) {
			$json['message'] = $this->front_end_messages["error_no_attempts_left"];//"Error, you have no attempts left for that question.";
			echo json_encode($json);
			exit();
		}
		
		// cancel if same as last submitted answer, only if not auto-advancing
		if (!$auto_advance && $answer === $question->last_answer) {
			$json['message'] = $this->front_end_messages["error_same_answer"];//"Error, please enter a different answer before submitting.";
			echo json_encode($json);
			exit();
		}
		
		// store the user's old status
		
		$quiz->initUserData($member_id, $this->_do_anonymous());
		$previous_passing = ($quiz->percent >= $quiz->passing_grade);
		$previous_completed = ($quiz->attempted_all_mandatory);
		$previous_correct_or_no_more_attempts = ($quiz->correct_or_no_more_attempts);
		
		// finally, insert answer into db or cookie
		
		$prefix = ($this->_do_anonymous()) ? "anonymous_" : "";
		$data = array(
			"{$prefix}progress_id"	=> '',
			"{$prefix}member_id"	=> $member_id,
			'mapping_id'			=> $mapping_id,
			'user_answer'			=> $answer,
			'time'					=> $this->EE->localize->now
		);
		$this->EE->db->insert("eequiz_{$prefix}progress", $data);
		
		
		//$question->cookie_data[] = $answer;
		//$this->EE->functions->set_cookie("4eeQuiz_{$mapping_id}", serialize($question->cookie_data), 60*60*24);
		
		// refresh member answer data
		$question->initUserData($mapping_id, $member_id, $this->_do_anonymous());
		
		// init quiz user data
		$quiz->initUserData($member_id, $this->_do_anonymous());
		
		// check if email notifications should be sent
		$new_passing = ($quiz->percent >= $quiz->passing_grade);
		$new_completed = ($quiz->attempted_all_mandatory);
		$new_correct_or_no_more_attempts = ($quiz->correct_or_no_more_attempts);
		if (($quiz->email_mode == QUIZ_EMAIL_ON_PASS && !$previous_passing && $new_passing) || 
			 ($quiz->email_mode == QUIZ_EMAIL_ON_COMPLETE && !$previous_completed && $new_completed) ||
			 ($quiz->email_mode == QUIZ_EMAIL_ON_CORRECT_OR_NO_MORE_ATTEMPTS && !$previous_correct_or_no_more_attempts && $new_correct_or_no_more_attempts)) 
			 $this->_send_email($quiz);
		
		// refresh cached answer data
		ModUtil::refresh_cached_answer_data($quiz, $member_id, $this->_do_anonymous());
		
		if ($auto_advance) {
			
			$last_mapping_id = $question->mapping_id;
			
			$temp_question_number = $quiz->mappings_to_orders[$question->mapping_id];
			$temp_num_questions = $quiz->num_questions;
			if ($temp_question_number < $temp_num_questions) {
				
				$question_info = $this->EE->db->query("SELECT * FROM exp_eequiz_mappings AS m 
										INNER JOIN exp_eequiz_questions AS q ON m.question_id=q.question_id
										WHERE m.quiz_id={$quiz->quiz_id} AND m.`order`=".($temp_question_number+1));
				$question_info = $question_info->row_array();
				require_once(QUESTION_TYPES_PATH.$question_info['classname'].".php");
				$question = new $question_info['classname']();
				$question->initFromDB($question_info['question_id'], $question_info);
				$question->initUserData($question_info['mapping_id'], $member_id, $this->_do_anonymous());
				
				$json = $this->_get_question_json($quiz, $question);
			} 
			else $json = $this->_get_question_json($quiz, $question);
			
			$json['success'] = TRUE;
			$json['message'] = "Successfully recorded answer.";
			$json['updated_answer'] = FALSE;
			$json['auto_advance'] = TRUE;
			$json['last_mapping_id'] = $last_mapping_id;
		}
		else {
			
			$json = $this->_get_question_json($quiz, $question);
			$json['success'] = TRUE;
			$json['message'] = "Successfully recorded answer.";
			$json['updated_answer'] = TRUE;
		}
		
		echo json_encode($json);
		exit();
	}
	
	
	function ajax_get_question()
	{
		$mapping_id = $this->EE->input->get_post('mapping_id');
		$new_number = $this->EE->input->get_post('new_number');
		
		$json = array(
			'success'		=> FALSE,
			'message'		=> 'An unknown error occurred.',
			'mapping_id'	=> $mapping_id
		);
		
		$question_info = $this->EE->db->query("SELECT * FROM exp_eequiz_mappings AS m 
										INNER JOIN exp_eequiz_questions AS q ON m.question_id=q.question_id
										WHERE m.mapping_id={$mapping_id}");
		
		// make sure valid question exists for this mapping
		if ($question_info->num_rows() == 0) {
			$json['message'] = "Error, invalid question-quiz mapping.";
			echo json_encode($json);
			exit();
		}
		$question_info = $question_info->row_array();
		
		// make sure valid quiz exists for this mapping
		$quiz = new Quiz();
		$quiz->initFromDB($question_info['quiz_id']);
		if (!$quiz->quiz_id) {
			$json['message'] = "Error: invalid quiz for the provided quiz-question mapping.";
			echo json_encode($json);
			exit();
		}
		
		$member_id = $this->_get_active_member_id($quiz);
		$quiz->initUserData($member_id, $this->_do_anonymous());
		
		// make sure quiz is enabled
		if ($quiz->disabled) {
			$json['message'] = $this->front_end_messages["error_disabled"];//"Error: this quiz is currently disabled.";
			echo json_encode($json);
			exit();
		}
		
		// make sure member is logged in if tracking answers
		if (!$member_id) {
			$json['message'] = $this->front_end_messages["error_logged_out"];//"Error: you must be logged in to take this quiz.";
			echo json_encode($json);
			exit();
		}
		
		// make sure valid question exists for the requested order
		$question_info = $this->EE->db->query("SELECT * FROM exp_eequiz_mappings AS m 
										INNER JOIN exp_eequiz_questions AS q ON m.question_id=q.question_id
										WHERE m.quiz_id={$quiz->quiz_id} AND m.`order`={$new_number}");
		if ($question_info->num_rows() == 0) {
			$json['message'] = "Error, this quiz does not have question by that number.";
			echo json_encode($json);
			exit();
		}
		$question_info = $question_info->row_array();
		$mapping_id = $question_info['mapping_id'];
		
		require_once(QUESTION_TYPES_PATH.$question_info['classname'].".php");
		$question = new $question_info['classname']();
		$question->initFromDB($question_info['question_id'], $question_info);
		$question->initUserData($mapping_id, $member_id, $this->_do_anonymous());
		
		
		$json = $this->_get_question_json($quiz, $question);
		$json['success'] = TRUE;
		$json['message'] = "Successfully retrieved question.";
		$json['updated_answer'] = FALSE;
		
		echo json_encode($json);
		exit();
	}
	
	
	
	
	
	
	// -------------------------------------
    //  _submit_all
	//  Private function submitting all questions
    // -------------------------------------
	
	function _submit_all()
	{
		$json = array(
			'success'	=> FALSE,
			'message'	=> 'An unknown error occurred.',
			'assets'	=> array()
		);
		
		$messages = array();
		
		// make sure quiz is legit
		$quiz = new Quiz();
		$quiz->initFromDB($this->EE->input->get_post('quiz_id'));
		if (!$quiz->quiz_id) {
			$json['message'] = "Error: invalid quiz for the provided quiz-question mapping.";
			echo json_encode($json);
			exit();
		}
		
		$member_id = $this->_get_active_member_id($quiz);
		
		// make sure member is logged in if tracking answers
		if (!$member_id) {
			$json['message'] = $this->front_end_messages["error_logged_out"];//"Error: nobody is logged in.";
			echo json_encode($json);
			exit();
		}
		
		// make sure quiz is enabled
		if ($quiz->disabled) {
			$json['message'] = $this->front_end_messages["error_disabled"];//"Error: this quiz is currently disabled.";
			echo json_encode($json);
			exit();
		}
		
		$quiz->initUserData($member_id, $this->_do_anonymous());
		$previous_passing = ($quiz->percent >= $quiz->passing_grade);
		$previous_completed = ($quiz->attempted_all_mandatory);
		$previous_correct_or_no_more_attempts = ($quiz->correct_or_no_more_attempts);
		
		$questions_array = array();
		$mappings = explode("_", $this->EE->input->get_post('all_mappings'));
		$quiz_mappings = array();
		foreach ($quiz->mappings as $m) $quiz_mappings[] = $m['mapping_id'];
		foreach ($mappings as $mapping_id)
		{
			if (!in_array($mapping_id, $quiz_mappings))
			{
				$json['message'] = "Error: mapping ({$mapping_id}) not found in quiz (".implode(",",$quiz_mappings).").";
				echo json_encode($json);
				exit();
			}
			
			$question_info = $this->EE->db->query("SELECT * FROM exp_eequiz_mappings AS m 
											INNER JOIN exp_eequiz_questions AS q ON m.question_id=q.question_id
											WHERE m.mapping_id={$mapping_id}");
			
			// make sure valid question exists for this mapping
			if ($question_info->num_rows() == 0) {
				$json['message'] = "Error: invalid question-quiz mapping.";
				echo json_encode($json);
				exit();
			}
			$question_info = $question_info->row_array();
			
			require_once(QUESTION_TYPES_PATH.$question_info['classname'].".php");
			$question = new $question_info['classname']();
			$question->initFromDB($question_info['question_id'], $question_info);
			$question->initUserData($mapping_id, $member_id, $this->_do_anonymous());
			$answer = $question->get_answer_from_post($mapping_id);
			
			$questions_array[$mapping_id] = $question;
			
			// make sure user has entered an answer
			if ($answer === NULL) {
				$json['assets'][$mapping_id] = $this->_construct_question_html($quiz, $question);
				continue;
			}
			
			// make sure user has attempts left
			if ($question->attempts >= $question->max_attempts && $question->max_attempts > 0) {
				$json['assets'][$mapping_id] = $this->_construct_question_html($quiz, $question);
				continue;
				//$json['message'] = "Error, you have no attempts left for that question.";
				//echo json_encode($json);
				//exit();
			}
			
			// cancel if same as last submitted answer
			if ($answer === $question->last_answer) {
				$json['assets'][$mapping_id] = $this->_construct_question_html($quiz, $question);
				continue;
			}
			
			// finally, insert answer into db or cookie
			$prefix = ($this->_do_anonymous()) ? "anonymous_" : "";
			$data = array(
				"{$prefix}progress_id"	=> '',
				"{$prefix}member_id"	=> $member_id,
				'mapping_id'			=> $mapping_id,
				'user_answer'			=> $answer,
				'time'					=> $this->EE->localize->now
			);
			$this->EE->db->insert("eequiz_{$prefix}progress", $data);
			//$question->cookie_data[] = $answer;
			//$this->EE->functions->set_cookie("4eeQuiz_{$mapping_id}", serialize($question->cookie_data), 60*60*24);
			
			// refresh member answer data
			$question->initUserData($mapping_id, $member_id, $this->_do_anonymous());
			
			//$json['assets'][$mapping_id] = $this->_construct_question_html($quiz, $question);
		}
		
		// init quiz user data
		$quiz->initUserData($member_id, $this->_do_anonymous());
		
		// check if email notifications should be sent
		$new_passing = ($quiz->percent >= $quiz->passing_grade);
		$new_completed = ($quiz->attempted_all_mandatory);
		$new_correct_or_no_more_attempts = ($quiz->correct_or_no_more_attempts);
		if (($quiz->email_mode == QUIZ_EMAIL_ON_PASS && !$previous_passing && $new_passing) || 
			 ($quiz->email_mode == QUIZ_EMAIL_ON_COMPLETE && !$previous_completed && $new_completed) ||
			 ($quiz->email_mode == QUIZ_EMAIL_ON_CORRECT_OR_NO_MORE_ATTEMPTS && !$previous_correct_or_no_more_attempts && $new_correct_or_no_more_attempts)) 
			 $this->_send_email($quiz);
		
		foreach ($questions_array as $k => $v) $json['assets'][$k] = $this->_construct_question_html($quiz, $v);
		
		ModUtil::refresh_cached_answer_data($quiz, $member_id, $this->_do_anonymous());
		
		$json['success'] = TRUE;
		$json['message'] = "Success.";
		$json['attempted_all'] = $quiz->attempted_all;
		$json['attempted_all_mandatory'] = $quiz->attempted_all_mandatory;
		$json['all_correct_or_no_more_attempts'] = $quiz->correct_or_no_more_attempts;
		$json['quiz_id'] = $quiz->quiz_id;
		$json['quiz_max_score'] = $quiz->max_score;
		$json['quiz_score'] = $quiz->score;
		$json['quiz_percent'] = $quiz->percent;
		$json['quiz_passing_grade'] = $quiz->passing_grade;
		$json['num_questions'] = $quiz->num_questions;
		$json['submitted_all'] = TRUE;
		echo json_encode($json);
		exit();
	}
	
	
	
	// -------------------------------------
    //  _get_question_json
	//  Private function for building json results from a given quiz and question
    // -------------------------------------
	
	function _get_question_json(&$quiz, &$question)
	{
		$json = array();
		
		$json['success'] = TRUE;
		$json['message'] = "Success.";
		$json['updated_answer'] = FALSE;
		$json['mapping_id'] = $question->mapping_id;
		$json['assets'] = $this->_construct_question_html($quiz, $question);
		$json['attempted_all'] = $quiz->attempted_all;
		$json['attempted_all_mandatory'] = $quiz->attempted_all_mandatory;
		$json['all_correct_or_no_more_attempts'] = $quiz->correct_or_no_more_attempts;
		$json['quiz_id'] = $quiz->quiz_id;
		$json['quiz_max_score'] = $quiz->max_score;
		$json['quiz_score'] = $quiz->score;
		$json['quiz_percent'] = $quiz->percent;
		$json['quiz_passing_grade'] = $quiz->passing_grade;
		
		$json['question_number'] = $quiz->mappings_to_orders[$question->mapping_id];
		$json['num_questions'] = $quiz->num_questions;
		$json['last_answer'] = $question->last_answer_formatted;
		$json['correctness'] = str_replace("_mark", "", $question->correctness_class);
		$json['attempts'] = $question->attempts;
		$json['max_attempts'] = $question->max_attempts;
		$json['weight'] = $question->max_weight;
		$json['score'] = $question->score;
	
		return $json;
	}
	
	
	
	// -------------------------------------
    //  _construct_question_html
	//  Private function for getting the html for a question
    // -------------------------------------
	
	function _construct_question_html(&$quiz, &$question)
	{
		//$this->_clear_cache();
		
		// set up some data
		
		$order = $quiz->mappings_to_orders[$question->mapping_id];
		
		$tagdata = "";
		$query = $this->EE->db->get_where("eequiz_quiz_templates", array('quiz_template_id'=>$quiz->quiz_template_id), 1);
		
		if ($query->num_rows() == 0) return $tagdata;
		else {
			$tagdata = $query->row_array();
			$tagdata = $tagdata['template'];
		}
		
		$show_feedback = FALSE;
		switch ($quiz->feedback_mode)
		{
			case QUIZ_FEEDBACK_HIDE:
				break;
			case QUIZ_FEEDBACK_SHOW:
				$show_feedback = TRUE;
				break;
			case QUIZ_FEEDBACK_SHOW_IF_ATTEMPTED:
				if ($question->attempts > 0)
					$show_feedback = TRUE; 
				break;
			case QUIZ_FEEDBACK_SHOW_IF_DONE:
				if (($question->attempts > 0 && $question->attempts >= $question->max_attempts) || $question->last_answer === $question->answer) 
					$show_feedback = TRUE; 
				break;
			case QUIZ_FEEDBACK_SHOW_IF_WRONG:
				if ($question->attempts > 0 && $question->correctness_class != "correct_mark") 
					$show_feedback = TRUE; 
				break;
		}
		
		// set up conditionals
		
		$cond = array(
			'num_questions'			=> $quiz->num_questions,
			"quiz_id"				=> $quiz->quiz_id,
			'question_id'			=> $question->question_id,
			'question_number'		=> $order,
			'question_title'		=> $question->title,
			'question_shortname'	=> $question->question_shortname,
			'question_tags'			=> $question->tags,
			'explanation'			=> $question->explanation,
			'type'					=> $question->classname,
			'max_attempts'			=> ($question->max_attempts <= 0) ? 999999 : $question->max_attempts,
			'weight'				=> $question->max_weight,
			
			'answer_time'			=> $question->last_time,
			'attempts'				=> $question->attempts,
			'remaining_attempts'	=> ($question->max_attempts == 0) ? 999999 : $question->max_attempts - $question->attempts,
			
			'show_feedback'			=> $show_feedback,
			
			'correctness'			=> preg_replace('/_mark$/', '', $question->correctness_class),
			
			'passing'					=> ($quiz->percent >= $quiz->passing_grade),
			'failing'					=> !($quiz->percent >= $quiz->passing_grade),
			'attempted_all'				=> $quiz->attempted_all,
			'attempted_all_mandatory'	=> $quiz->attempted_all_mandatory,
			'quiz_score'				=> $quiz->score,
			'quiz_percent'				=> $quiz->percent,
			'quiz_max_score'			=> $quiz->max_score
		);
		
		// set up variables
		
		$vars = array(
			"num_questions" 		=> $quiz->num_questions,
			"quiz_id"				=> $quiz->quiz_id,
			"question_id"			=> $question->question_id,
			"question_number"		=> $order,
			"question_title"		=> $question->title,
			"question_shortname"	=> $question->question_shortname,
			"question_tags"			=> $question->tags,
			"type"					=> $question->classname,
			"text"					=> $question->text,
			"max_attempts"			=> $question->max_attempts > 0 ? $question->max_attempts : "infinite",
			"weight"				=> $question->max_weight,
			"answer_section"		=> $question->answer_section,
			"feedback_section"		=> $show_feedback ? $question->feedback_section : "",
			"feedback_explanation"	=> $show_feedback ? $question->explanation : "",
			"feedback_extra"		=> $show_feedback ? $question->explanation_extra : "",
			
			"answer_time"			=> ($question->attempts == 0) ? "not answered yet" : $question->last_time_formatted,
			"attempts"				=> $question->attempts,
			"remaining_attempts"	=> ($question->max_attempts == 0) ? "infinite" : $question->max_attempts-$question->attempts,
			"score"					=> $question->score,
			"correctness"			=> $question->correctness,
			
			"quiz_title"			=> $quiz->title,
			"quiz_description"		=> $quiz->description,
			"quiz_passing_score"	=> $quiz->passing_grade,
			"quiz_score"			=> $quiz->score,
			"quiz_percent"			=> $quiz->percent,
			"quiz_max_score"		=> $quiz->max_score,
		);
		
		$start = 1;
		$end = $quiz->num_questions;
		
		$class = ($order <= $start) ? "class='previous_link disabled' " : "class='previous_link' ";
		$onclick = ($order <= $start) ? "" : "onclick='eequiz.gotoQuestion({$question->mapping_id}, ".max($start, $order-1).")' ";
		$vars["previous"] = ($quiz->one_at_a_time ? "<a {$class} href='javascript:void(0)' {$onclick}>".$this->front_end_messages["previous"]."</a>":"");
		
		$class = ($order >= $end || ($question->attempts <= 0 && !$question->optional)) ? "class='next_link disabled' " : "class='next_link' ";
		$onclick = ($order >= $end || ($question->attempts <= 0 && !$question->optional)) ? "" : "onclick='eequiz.gotoQuestion({$question->mapping_id}, ".min($end, $order+1).")' ";
		$vars["next"] = ($quiz->one_at_a_time ? "<a {$class} href='javascript:void(0)' {$onclick}>".$this->front_end_messages["next"]."</a>":"");
		
		$submit_disabled = ($quiz->disabled || ($question->max_attempts > 0 && $question->attempts >= $question->max_attempts));
		$submit_btn_html = "<input id='eequiz_submit_{$question->mapping_id}' class='submit_answer_button' type='submit' ".($submit_disabled ? "disabled='disabled' " : "")."value='".$this->front_end_messages["submit_answer"]."' />";
		$vars["submit"] = $submit_btn_html;
		
		if ($quiz->one_at_a_time) {
			$vars["submit_and_advance"] = $submit_btn_html."<input type='hidden' name='auto_advance' value='1' />";
		}
		else {
			$vars["submit_and_advance"] = $submit_btn_html; // normal submit button if all-at-once
		}
		
		// parse data
		
		$tagdata = $this->_standalone_parse_tagdata($tagdata, $cond, $vars);
		
		// parse parameterized vars
		
		if (preg_match_all("/".LD."answer_time\s+format=([\"'])([^\\1]*?)\\1".RD."/s", $tagdata, $matches)) {
			for ($j = 0; $j < count($matches[0]); $j++)
				$tagdata = str_replace($matches[0][$j], ($question->attempts == 0) ? "not answered yet" : gmdate($matches[2][$j], $question->last_time), $tagdata);
		}
		
		
		
		
		
		
		
		
		
		
		
		/*$tagdata = $this->EE->functions->prep_conditionals($tagdata, $cond);
		$tagdata = str_replace(array(LD.'/if'.RD, LD.'if:else'.RD), array('<?php endif; ?'.'>','<?php else : ?'.'>'), $tagdata);
		$tagdata = preg_replace("/".preg_quote(LD)."((if:(else))*if)\s+(.*?)".preg_quote(RD)."/s", '<?php \\3if(\\4) : ?'.'>', $tagdata);
		
		ob_start();
		echo $this->EE->functions->evaluate($tagdata);
		$tagdata = ob_get_contents();
		ob_end_clean(); */
		
		
		/*
		$tagdata = str_replace("{num_questions}", $quiz->num_questions, $tagdata);
		
		$tagdata = str_replace("{question_id}", $question->question_id, $tagdata);
		$tagdata = str_replace("{question_number}", $order, $tagdata);
		$tagdata = str_replace("{question_title}", $question->title, $tagdata);
		$tagdata = str_replace("{question_shortname}", $question->question_shortname, $tagdata);
		$tagdata = str_replace("{type}", $question->classname, $tagdata);
		$tagdata = str_replace("{text}", $question->text, $tagdata);
		$tagdata = str_replace("{max_attempts}", ($question->max_attempts > 0) ? $question->max_attempts : "infinite", $tagdata);
		$tagdata = str_replace("{weight}", $question->max_weight, $tagdata);
		
		$tagdata = str_replace("{answer_section}", $question->answer_section, $tagdata);
		$tagdata = str_replace("{feedback_section}", $show_feedback ? $question->feedback_section : "", $tagdata);
		
		$tagdata = str_replace("{answer_time}", ($question->attempts == 0) ? "not answered yet" : $question->last_time_formatted, $tagdata);
		if (preg_match_all("/".LD."answer_time\s+format=([\"'])([^\\1]*?)\\1".RD."/s", $tagdata, $matches)) {
			for ($j = 0; $j < count($matches[0]); $j++)
				$tagdata = str_replace($matches[0][$j], ($question->attempts == 0) ? "not answered yet" : gmdate($matches[2][$j], $question->last_time), $tagdata);
		}
		
		$tagdata = str_replace("{attempts}", $question->attempts, $tagdata);
		$tagdata = str_replace("{remaining_attempts}", ($question->max_attempts == 0) ? "infinite" : $question->max_attempts-$question->attempts, $tagdata);
		$tagdata = str_replace("{score}", $question->score, $tagdata);
		$tagdata = str_replace("{correctness}", $question->correctness, $tagdata);
		
		$tagdata = str_replace("{quiz_passing_score}", $quiz->passing_grade, $tagdata);
		$tagdata = str_replace("{quiz_score}", $quiz->score, $tagdata);
		$tagdata = str_replace("{quiz_percent}", $quiz->percent, $tagdata);
		$tagdata = str_replace("{quiz_max_score}", $quiz->max_score, $tagdata);
		
		// previous/next buttons
		
		$start = 1;
		$end = $quiz->num_questions;
		
		$class = ($order <= $start) ? "class='previous_link disabled' " : "class='previous_link' ";
		$onclick = ($order <= $start) ? "" : "onclick='eequiz.gotoQuestion({$question->mapping_id}, ".max($start, $order-1).")' ";
		$tagdata = str_replace("{previous}", ($quiz->one_at_a_time ? "<a {$class} href='javascript:void(0)' {$onclick}>Previous</a>":""), $tagdata);
		
		$class = ($order >= $end || ($question->attempts <= 0 && !$question->optional)) ? "class='next_link disabled' " : "class='next_link' ";
		$onclick = ($order >= $end || ($question->attempts <= 0 && !$question->optional)) ? "" : "onclick='eequiz.gotoQuestion({$question->mapping_id}, ".min($end, $order+1).")' ";
		$tagdata = str_replace("{next}", ($quiz->one_at_a_time ? "<a {$class} href='javascript:void(0)' {$onclick}>Next</a>":""), $tagdata);
		
		$submit_disabled = ($quiz->disabled || ($question->max_attempts > 0 && $question->attempts >= $question->max_attempts));
		$submit_btn_html = "<input id='eequiz_submit_{$question->mapping_id}' class='submit_answer_button' type='submit' ".($submit_disabled ? "disabled='disabled' " : "")."value='Submit Answer' />";
		$tagdata = str_replace("{submit}", $submit_btn_html, $tagdata);
		
		if ($quiz->one_at_a_time) {
			$tagdata = str_replace("{submit_and_advance}", $submit_btn_html."<input type='hidden' name='auto_advance' value='1' />", $tagdata);
		}
		else {
			// normal submit button if all-at-once
			$tagdata = str_replace("{submit_and_advance}", $submit_btn_html, $tagdata);
		}*/
		
		// form and div wrapper
		
		$this->_set_action_urls();
		
		$tagdata = <<<EOT
<div id='eequiz_{$question->mapping_id}' class='eequiz'>
<form name='question_form_{$question->mapping_id}' id='question_form_{$question->mapping_id}' action='{$this->submit_answer_url}' method='post' onsubmit='return eequiz.submitQuestion(this);' data-ajax='false'>
<div style="display: none;">
<input type='hidden' name='mapping_id' value='{$question->mapping_id}' />
<input type='hidden' name='quiz_id' value='{$quiz->quiz_id}' />
</div>
{$tagdata}
</form>
</div>
EOT;
		
		return $tagdata;
	}
	
	
	
	// -------------------------------------
    //  _get_active_member_id
	//  Private function for getting the active member_id (taking into account quiz settings)
    // -------------------------------------
	
	function _get_active_member_id(&$quiz)
	{
		// check if logged in, use that first
		
		if ($this->EE->session->userdata['member_id']) return $this->EE->session->userdata['member_id'];
		
		// not logged in, if quiz is anonymous, create anonymous member
		
		else if ($quiz->anonymous) {
			
			// anonymous answers
			
			if (!$this->cookie_anon_member_id) {
				
				// read from cookie
				
				$anon_member_id = $this->EE->input->cookie("4eeQuiz_anonymous_member_id");
				$anon_key = $this->EE->input->cookie("4eeQuiz_anonymous_member_id_key");
				
				if (!$anon_member_id || !$anon_key) {
					
					// no cookie data... create new
					
					return $this->_create_anonymous_member();
				}
				else {
					
					// existing cookie data... validate
					
					$this->EE->db->where(array('anonymous_member_id' => $anon_member_id));
					$row = $this->EE->db->get('eequiz_anonymous_members');
					
					if ($row->num_rows() == 0) return $this->_create_anonymous_member();
					
					$row = $row->row_array();
					
					if (!preg_match("/\d+/", $anon_member_id)) return $this->_create_anonymous_member();
					else if ($row["anonymous_key"] != $anon_key) return $this->_create_anonymous_member();
					else return $anon_member_id;
				}
			}
			else {
				
				// already initialized
				
				return $this->cookie_anon_member_id;
			}
		}
		
		// not logged in and quiz is not anonymous enabled, return 0
		
		else return 0;
	}
	
	function _create_anonymous_member()
	{
		$new_anon_key = "";
		$possibles = "1234567890abcdefghijklmnopqrstuvwxyz";
		for ($i = 0; $i < 10; $i++) $new_anon_key .= $possibles[rand(1,strlen($possibles))-1];
		
		$data = array(
			"anonymous_member_id"	=> "",
			"anonymous_key"			=> $new_anon_key,
			"create_time"			=> $this->EE->localize->now
		);
		$this->EE->db->insert('eequiz_anonymous_members', $data);
		
		$anon_member_id = $this->EE->db->insert_id();
		
		$this->EE->functions->set_cookie("4eeQuiz_anonymous_member_id", $anon_member_id, 60*60*24);
		$this->EE->functions->set_cookie("4eeQuiz_anonymous_member_id_key", $new_anon_key, 60*60*24);
		$this->cookie_anon_member_id = $anon_member_id;
		$this->cookie_anon_member_key = $new_anon_key;
		
		return $anon_member_id;
	}
	
	function _do_anonymous()
	{
		if ($this->EE->session->userdata['member_id']) return FALSE;
		else return TRUE;
	}
	
	// -------------------------------------
    //  _set_action_urls
	//  Private function for making sure the instance's action urls are set
    // -------------------------------------
	
	function _set_action_urls()
	{
		$site_index = $this->EE->functions->fetch_site_index(FALSE);
		//if (substr($site_index, -1) == '/') $site_index = substr($site_index, 0, strlen($site_index)-1);
		
		if ($this->submit_answer_url === FALSE) {
			$action_id_query = $this->EE->db->query("SELECT * FROM exp_actions WHERE class='Eequiz' AND method='ajax_submit_question' LIMIT 1");
			$action_id_query = $action_id_query->row_array();
			$this->submit_answer_url = $site_index."?ACT=".$action_id_query['action_id'];
		}
		
		if ($this->get_question_url === FALSE) {
			$action_id_query = $this->EE->db->query("SELECT * FROM exp_actions WHERE class='Eequiz' AND method='ajax_get_question' LIMIT 1");
			$action_id_query = $action_id_query->row_array();
			$this->get_question_url = $site_index."?ACT=".$action_id_query['action_id'];
		}
	}
	
	// -------------------------------------
    //  _standalone_parse_tagdata
	//  Private function for parsing standalone tagdata
    // -------------------------------------
	
	function _standalone_parse_tagdata(&$tagdata, &$conds, &$vars) {
		
		$result = $tagdata;
		
		// conditionals
		
		$result = $this->EE->functions->prep_conditionals($result, $conds);
		
		// vars
		
		foreach ($vars as $k => $v) $result = str_replace(LD.$k.RD, $v, $result);
		
		// parse template
		
		if (!class_exists('EE_Template')) require(APPPATH.'libraries/Template.php');  
		
		$old = null;
		if (isset($this->EE->TMPL)) $old = $this->EE->TMPL;
		
		$this->EE->TMPL = new EE_Template();
		$this->EE->TMPL->parse($result);
		$result = $this->EE->TMPL->final_template;
		unset($this->EE->TMPL);
		
		if ($old !== null) $this->EE->TMPL = $old;
		
		return $result;
		
		/*$result = str_replace(array(LD.'/if'.RD, LD.'if:else'.RD), array('<?php endif; ?'.'>','<?php else : ?'.'>'), $result);
		$result = preg_replace("/".preg_quote(LD)."((if:(else))*if)\s+(.*?)".preg_quote(RD)."/s", '<?php \\3if(\\4) : ?'.'>', $result);
		ob_start();
		echo $this->EE->functions->evaluate($result);
		$result = ob_get_contents();
		ob_end_clean();*/
	}
	
	
	
	function _get_quiz_id($url_title) {
	
		$result = $this->EE->db->query("SELECT quiz_id FROM exp_eequiz_quizzes WHERE url_title='{$url_title}'");
		
		if ($result->num_rows() == 0) return 0;
		
		$result = $result->row_array();
		return $result["quiz_id"];
	}
	
	
	
	function _send_email(&$quiz) {
	
		$this->EE->load->library('email');
		$this->EE->load->helper('text');
		
		$is_anonymous = $this->_do_anonymous();
		$member_id = $this->_get_active_member_id($quiz);
		
		// set up to, from, subject
		
		$recipients = $quiz->email_recipients;
		if (!$is_anonymous) $recipients = str_replace("{user_email}", $this->EE->session->userdata['email'], $recipients);
		else $recipients = str_replace("{user_email}", "", $recipients);
		$recipients = preg_replace('/, *,/', ", ", $recipients); // clean up 
	
		$from = $quiz->email_from;
		
		$subject = $quiz->email_subject;
		$subject = str_replace("{quiz_title}", $quiz->title, $subject);
		$subject = str_replace("{screen_name}", $is_anonymous ? "anonymous member" : $this->EE->session->userdata['screen_name'], $subject);
		$subject = str_replace("{username}", $is_anonymous ? "anonymous member" : $this->EE->session->userdata['username'], $subject);
		
		// set up body
		
		$message = $quiz->email_message;
		
		$conds = array(
			"quiz_id"	=> $quiz->quiz_id,
			"member_id"	=> $member_id,
			"anonymous"	=> $is_anonymous,
			"screen_name"	=> $is_anonymous ? "anonymous member" : $this->EE->session->userdata['screen_name'],
			"username"		=> $is_anonymous ? "anonymous member" : $this->EE->session->userdata['username']
		);
		$vars = array(
			"quiz_id"	=> $quiz->quiz_id,
			"member_id"	=> $member_id,
			"anonymous"	=> $is_anonymous,
			"screen_name"	=> $is_anonymous ? "anonymous member" : $this->EE->session->userdata['screen_name'],
			"username"		=> $is_anonymous ? "anonymous member" : $this->EE->session->userdata['username']
		);
		
		$message = $this->_standalone_parse_tagdata($message, $conds, $vars);
		
		// send the email
		
		$this->EE->email->wordwrap = true;
		$this->EE->email->mailtype = 'text';
		$this->EE->email->from($from);
		$this->EE->email->to($recipients);
		$this->EE->email->subject($subject);
		$this->EE->email->message(entities_to_ascii($message));
		$this->EE->email->Send();
	}
	
	
	
	/*function _clear_cache()
	{
		// get segments
		
		$seg_string = str_replace($this->EE->functions->fetch_site_index(), '', $this->EE->functions->fetch_current_uri());
		if (substr($seg_string, -1) == '/') $seg_string = substr($seg_string, 0, -1);
		if (substr($seg_string, 0, 1) == '/') $seg_string = substr($seg_string, 1);
		$segs = explode('/', $seg_string);
		
		// construct cache sub folder
		
		$cache_sub_folder = "";
		if (count($segs) == 0) return;
		elseif (count($segs) == 1) $cache_sub_folder = $segs[0]."+index";
		elseif (count($segs) >= 2) $cache_sub_folder = $segs[0]."+".$segs[1];
		
		// delete the cache sub folder
		
		echo $cache_sub_folder."<br />";
		$this->EE->functions->delete_directory(APPPATH.'cache/db_cache_'.$this->EE->config->item('site_id')."/".$cache_sub_folder, TRUE);  
	}*/
	
}


// END CLASS