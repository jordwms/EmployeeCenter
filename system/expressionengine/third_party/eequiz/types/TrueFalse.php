<?php

require_once(QUIZ_ENGINE_PATH."Question.php");

class TrueFalse extends Question
{
	
	
	function TrueFalse()
	{
		parent::Question();
		
		$this->classname = "TrueFalse";
	}
	
	function initUserData($mapping_id, $member_id, $anonymous)
	{
		parent::initUserData($mapping_id, $member_id, $anonymous);
		
		if ($this->attempts > 0) $this->last_answer_formatted = ($this->last_answer == 1) ? "true" : "false";
		
		$this->answer_section = 
			"<div class='answer_section'>".
			"<input type='radio' id='mapping{$mapping_id}_user_answer_1' name='mapping{$mapping_id}_user_answer' value='1' ".(($this->last_answer == 1 && $this->attempts > 0) ? "checked='checked' " : "")."/><label for='mapping{$mapping_id}_user_answer_1'> True</label> ".
			"<input type='radio' id='mapping{$mapping_id}_user_answer_0' name='mapping{$mapping_id}_user_answer' value='0' ".(($this->last_answer == 0 && $this->attempts > 0) ? "checked='checked' " : "")."/><label for='mapping{$mapping_id}_user_answer_0'> False</label>".
			"<div class='answer_footer'></div>".
			"</div>";
	}
	
	
	
	
	
	
	
	
	
	
	function getEditData()
	{
		$result = parent::getEditData();
		
		$result['answer_settings'][] = array(
			"label"				=> "Answer",
			"description"		=> "",
			"content"			=> "<input type='radio' name='answer' id='answer' value='1' ".(($this->answer) ? "checked='checked' ": "")." /> True&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".
								   "<input type='radio' name='answer' id='answer' value='0' ".((!$this->answer) ? "checked='checked' ": "")." /> False"
		);
		
		return $result;
	}
	
}