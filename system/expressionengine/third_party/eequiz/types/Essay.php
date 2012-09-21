<?php

require_once(QUIZ_ENGINE_PATH."Question.php");

class Essay extends Question
{
	
	function Essay()
	{
		parent::Question();
		
		$this->classname = "Essay";
		$this->answer = "";
		$this->last_answer = "";
		$this->last_answer_formatted = "";
	}
	
	function initUserData($mapping_id, $member_id, $anonymous)
	{
		parent::initUserData($mapping_id, $member_id, $anonymous);
		
		if ($this->attempts > 0) $this->score = $this->weight;
		
		$this->correctness_class = "";
		$this->correctness = "";
		
		$this->answer_section = 
			"<div class='answer_section'>".
			"<textarea name='mapping{$mapping_id}_user_answer'>".htmlspecialchars($this->last_answer, ENT_QUOTES)."</textarea>".
			"<div class='answer_footer'></div>".
			"</div>";
	}
}