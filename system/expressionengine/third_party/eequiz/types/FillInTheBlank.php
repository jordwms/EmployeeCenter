<?php

require_once(QUIZ_ENGINE_PATH."Question.php");

class FillInTheBlank extends Question
{
	
	
	function FillInTheBlank()
	{
		parent::Question();
		
		$this->classname = "FillInTheBlank";
		$this->answer = "";
		$this->last_answer = "";
		$this->last_answer_formatted = "";
	}
	
	function initUserData($mapping_id, $member_id, $anonymous)
	{
		parent::initUserData($mapping_id, $member_id, $anonymous);
		
		$this->answer_section = 
			"<div class='answer_section'>".
			"<input type='text' name='mapping{$mapping_id}_user_answer' value='".$this->last_answer_formatted."' />".
			"<div class='answer_footer'></div>".
			"</div>";
		
		if ($this->attempts > 0)
		{
			$this->score = ($this->last_answer == $this->answer) ? $this->weight : 0;
			
			$this->correctness_class = "incorrect_mark";
			$correctness_message = "incorrect";
			if ((strcasecmp($this->last_answer, $this->answer) == 0) ||
				(is_numeric($this->last_answer) && is_numeric($this->answer) && $this->last_answer == $this->answer))
			{
				$this->correctness_class = "correct_mark";
				$correctness_message = "correct";
			}
			$this->correctness = "<div class='{$this->correctness_class}'><span class='mark_text'>{$correctness_message}</span></div>";
		}
	}
	
	function getEditData()
	{
		$result = parent::getEditData();
		
		$result['answer_settings'][] = array(
			"label"				=> "Answer",
			"description"		=> "User answers must match this answer exactly (case insensitive)",
			"content"			=> "<input type='text' name='answer' id='answer' value='".htmlspecialchars($this->answer, ENT_QUOTES)."' />"
		);
		
		return $result;
	}
	
}