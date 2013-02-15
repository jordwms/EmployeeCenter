<?php

class Question
{
	var $db_data				= array();
	
	var $question_id			= 0;
	var $question_shortname		= "";
	var $classname				= "Question";
	
	var $title					= "";
	var $text					= "";
	var $explanation			= "";
	var $explanation_extra		= "";
	
	var $optional				= FALSE;
	var $settings				= array();
	var $tags					= "";
	
	var $weight					= 0;
	var $max_weight				= 0;
	var $max_attempts			= 0;
	var $answer					= 0;
	
	// user data
	var $member_id				= 0;
	var $mapping_id				= 0;
	var $attempts				= 0;
	var $score					= 0;
	var $last_time				= 0;
	var $last_time_formatted	= 0;
	var $last_answer			= 0;
	var $last_answer_formatted	= 0;
	var $correctness			= "";
	var $correctness_class		= "";
	
	
	function Question()
	{
		$this->EE =& get_instance();
	}
	
	function initFromDB($question_id, $data = 0)
	{
		if (!$question_id) return;
		
		if ($data) $this->db_data = $data;
		else {
			$this->EE->db->where("question_id", $question_id);
			$this->db_data = $this->EE->db->get("eequiz_questions", 1);
			
			if ($this->db_data->num_rows() == 0) return;
			else $this->db_data = $this->db_data->row_array();
		}
		
		$this->question_id = $question_id;
		$this->question_shortname = $this->db_data["question_shortname"];
		
		$this->title = $this->db_data["title"];
		$this->text = $this->db_data["text"];
		$this->explanation = $this->db_data["explanation"];
		
		$this->optional = $this->db_data["optional"] == 1;
		$this->settings = special_unserialize($this->db_data["settings"]);
		$this->tags = $this->db_data["tags"];
		
		$this->weight = $this->db_data["weight"];
		$this->max_weight = $this->weight;
		$this->max_attempts = $this->db_data["max_attempts"];
		$this->answer = $this->db_data["answer"];
	}
	
	function initFromPost()
	{
		$this->question_id = ($this->EE->input->get_post('question_id')) ? $this->EE->input->get_post('question_id') : 0;
		$this->question_shortname = $this->EE->input->get_post("question_shortname");
		
		$this->title = $this->EE->input->get_post("title");
		$this->text = $this->EE->input->get_post("text");
		$this->explanation = $this->EE->input->get_post("explanation");
		
		$this->optional = $this->EE->input->get_post("optional") == 1;
		$this->settings = array();
		$this->tags = $this->EE->input->get_post("tags");
		
		$this->weight = $this->EE->input->get_post("weight");
		$this->max_attempts = $this->EE->input->get_post("max_attempts");
		$this->answer = $this->EE->input->get_post("answer");
	}
	
	// override this and change:
	// answer_section, feedback_section, correctness, score
	function initUserData($mapping_id, $member_id, $anonymous)
	{
		$this->member_id = $member_id;
		$this->mapping_id = $mapping_id;
		
		$prefix = $anonymous ? "anonymous_" : "";
		
		$this->EE->db->where(array("mapping_id" => $mapping_id, "{$prefix}member_id" => $member_id));
		$this->EE->db->order_by("time", "desc");
		$query = $this->EE->db->get("eequiz_{$prefix}progress");
		
		$this->attempts = $query->num_rows();
		
		if ($this->attempts > 0) {
			$rows = $query->result_array();
			$this->last_answer = $rows[0]['user_answer'];
			$this->last_time = $rows[0]['time'];
			$this->last_time_formatted = $this->EE->localize->set_human_time($this->last_time);
		}
		
		$this->answer_section = "<div class='answer_section'><div>";
		
		//$this->feedback_section = "<div class='".str_replace("mark", "feedback", $correctness_class)."'>{$this->explanation}</div>";
		$this->feedback_section = ($this->explanation == "") ? "" : "<div class='feedback_section'>{$this->explanation}</div>";
		
		if ($this->attempts > 0)
		{
			$this->last_answer_formatted = nl2br(htmlspecialchars($this->last_answer, ENT_QUOTES));
			$this->score = ($this->last_answer == $this->answer) ? $this->weight : 0;
			
			$this->correctness_class = "incorrect_mark";
			$correctness_message = $this->EE->lang->line("incorrect");
			if ($this->last_answer === $this->answer)
			{
				$this->correctness_class = "correct_mark";
				$correctness_message = $this->EE->lang->line("correct");
			}
			$this->correctness = "<div class='{$this->correctness_class}'><span class='mark_text'>{$correctness_message}</span></div>";
		}
	}
	
	
	
	
	
	
	// CONTROL PANEL FUNCTIONS
	
	function getEditData()
	{
		$information = array();
		
		$information[] = array(
			"label"			=> $this->EE->lang->line('question_title_lbl'),
			"description"	=> $this->EE->lang->line('question_title_desc'),
			"content"		=> "<input class='v_required text_input_long' name='title' id='title' type='text' value='{$this->title}' />"
		);
		$information[] = array(
			"label"			=> $this->EE->lang->line('question_shortname_lbl'),
			"description"	=> $this->EE->lang->line('question_shortname_desc'),
			"content"		=> "<input class='v_word_characters_hyphen text_input_long' name='question_shortname' id='question_shortname' type='text' value='{$this->question_shortname}' />"
		);
		$information[] = array(
			"label"			=> "Tags",
			"description"	=> "Enter space separated tags for your question here. These will be used to organize your questions as you add them to quizzes.",
			"content"		=> "<input class='text_input_long' name='tags' id='tags' type='text' value='{$this->tags}' />"
		);
		$information[] = array(
			"label"			=> $this->EE->lang->line('question_type_lbl'),
			"description"	=> "",
			"content"		=> $this->EE->lang->line($this->classname)
		);
		$information[] = array(
			"label"			=> $this->EE->lang->line('question_text_lbl'),
			"description"	=> $this->EE->lang->line('question_text_desc'),
			"content"		=> "<textarea class='v_required textarea_input' name='text' id='text'>{$this->text}</textarea>"
		);
		$information[] = array(
			"label"			=> $this->EE->lang->line('question_explanation_lbl'),
			"description"	=> $this->EE->lang->line('question_explanation_desc'),
			"content"		=> "<textarea class='textarea_input' name='explanation' id='explanation'>{$this->explanation}</textarea>"
		);
		
		
		
		
		$settings = array();
		
		$settings[] = array(
			"label"				=> $this->EE->lang->line('question_optional_lbl'),
			"description"		=> $this->EE->lang->line('question_optional_desc'),
			"content"			=> "<input class='lightswitch' type='checkbox' name='optional' value='1' ".(($this->optional) ? "checked='checked' ": "")." />"
			//"content"			=> "<input type='radio' name='optional' value='1' ".(($this->optional) ? "checked='checked' ": "")." /> Yes".
			//					   "<input type='radio' name='optional' value='0' ".((!$this->optional) ? "checked='checked' ": "")." /> No"
		);
		
		
		
		
		
		$answer_settings = array();
		
		$answer_settings[] = array(
			"label"				=> $this->EE->lang->line('question_weight_lbl'),
			"description"		=> $this->EE->lang->line('question_weight_desc'),
			"content"			=> "<input class='v_positive_integer text_input_short' name='weight' id='weight' type='text' value='{$this->weight}' />"
		);
		$answer_settings[] = array(
			"label"				=> $this->EE->lang->line('question_max_attempts_lbl'),
			"description"		=> $this->EE->lang->line('question_max_attempts_desc'),
			"content"			=> "<input class='v_positive_integer text_input_short' name='max_attempts' id='max_attempts' type='text' value='{$this->max_attempts}' />"
		);
		
		
		
		
		
		
		$result = array(
			'information'		=> $information,
			'settings'			=> $settings,
			'answer_settings'	=> $answer_settings,
			'other_tables'		=> array(),
			'extra'				=> ""
		);
		
		return $result;
	}
	
	function dbSync()
	{
		// make sure shortname is unique
		$this->EE->db->select("question_id");
		$this->EE->db->where(array('question_shortname =' => $this->question_shortname, 'question_id !=' => $this->question_id));
		$query = $this->EE->db->get("eequiz_questions");
		if ($query->num_rows() > 0) return FALSE;
		
		$data = array(
			'question_shortname'	=> $this->question_shortname,
			'classname'				=> $this->classname,
			
			'title'					=> $this->title,
			'text'					=> $this->text,
			'explanation'			=> $this->explanation,
			
			'optional'				=> $this->optional,
			'settings'				=> special_serialize($this->settings),
			'tags'					=> $this->tags,
			
			'weight'				=> $this->weight,
			'max_attempts'			=> $this->max_attempts,
			'answer'				=> $this->answer
		);
		
		if ($this->question_id)
		{
			$this->EE->db->where('question_id', $this->question_id);
			$this->EE->db->update('eequiz_questions', $data); 
		}
		else
		{
			$data['question_id'] = '';
			
			$this->EE->db->insert('eequiz_questions', $data);
			$this->question_id = $this->EE->db->insert_id();
		}
		
		return TRUE;
	}

	
	
	
	
	
	
	// TEMPLATE FUNCTIONS
	
	function get_answer_from_post($mapping_id)
	{
		if ($this->EE->input->get_post("mapping{$mapping_id}_user_answer") === FALSE)
			return NULL;
		else
			return $this->EE->input->get_post("mapping{$mapping_id}_user_answer");
	}
	
}