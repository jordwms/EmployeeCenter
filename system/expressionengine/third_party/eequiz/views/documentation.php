<div id="doc_container">

	<h3>Quick Start</h3>
	<div class="doc_section">
		<ol class="quick_start">
			<li>Create some questions.</li>
			<li>Create a quiz, select 'Full' as its template, and add questions as needed. Submit the quiz.</li>
			<li>Create a template group called eequiz and enter the following in its index:
				<p class='doc_example'>Select a quiz:<br />{exp:eequiz:quizzes}<br />&lt;a href="{path=eequiz/take_quiz/{quiz_id}}"&gt;{quiz_title}&lt;/a&gt;&lt;br /&gt;<br />{/exp:eequiz:quizzes}</p>

			<li>Create a template called 'take_quiz', and put the following tag in it. Make sure to include jquery on this page, or refer to the exp:eequiz:questions documentation to use your own javascript:<p class='doc_example'>{exp:eequiz:questions quiz_id="{segment_3}"}</p></li>
			</li>
			<li>Refer to the CSS section of this documentation to style your questions. You may use the quick start CSS found on that page as a starting point.</li>
			<li>Make sure query caching is disabled (admin->system administration->database settings->"Enable SQL Query Caching" set to "No").</li>
			<li>Log in to your site and visit http://www.yoursite.com/index.php/eequiz to start taking quizzes!</li>
		</ol>
	</div>
	
	
	
	
	
	
	<h3>Quizzes Tag</h3>
	<div class="doc_section">
	
		<div class="subsection">
			<h2>Overview:</h2>
			<p>This is a tag pair. It outputs overview information pertaining to quizzes (titles, descriptions, the logged-in user's score, etc).</p>
		</div>
		
		<div class="subsection">
			<h2>Options:</h2>
			<dl>
				<dt>quiz_id</dt>
				<dd>Optional. The id of a specific quiz to get data for. Will iterate through all quizzes if not specified.</dd>
				<dt>url_title</dt>
				<dd>Optional. The url_title of a specific quiz to get data for. Will iterate through all quizzes if not specified.</dd>
				<dt>tags</dt> 
				<dd>Optional. Use this to only show quizzes of certain tags, separated by "|". ex: math|science</dd>
				<dt>member_id</dt>
				<dd>Optional. If specified, the quiz will use results for that member, instead of the currently logged in member (currently logged in member is default).</dd>
				<dt>disable="grades"</dt>
				<dd>Optional. Will speed up the tag by disabling any variables and conditionals relating to user scores and progress.</dd>
			</dl>
		</div>
		
		<div class="subsection">
			<h2>Variables:</h2>
			<dl>
				<dt>quiz_title</dt> 
				<dd>The title of this quiz.</dd>
				<dt>quiz_description</dt> 
				<dd>The description of this quiz.</dd>
				<dt>tags</dt> 
				<dd>The tags for this quiz.</dd>
				<dt>passing_grade</dt> 
				<dd>The passing grade for this quiz.</dd>
				<dt><b>--- grades tag option must NOT be disabled for the following conditionals ---</b></dt>
				<dd></dd>
				<dt>grade_score</dt> 
				<dd>The raw score of the currently logged in member.</dd>
				<dt>grade_percent</dt> 
				<dd>A percentage score (earned score divided by max possible score) of the currently logged in member.</dd>
				<dt>max_score</dt>
				<dd>The max possible score that can be obtained on this quiz.</dd>
				<dt>last_answer_time format="m/d/Y"</dt>
				<dd>The time of the user's last answered question. If this quiz has been completed, this can be used to show when the user completed the quiz. The format parameter is mandatory; use the example here or see php's <a href="http://php.net/manual/en/function.date.php">date documentation</a> for more control. Ex: {last_answer_time format="m/d/Y"}</dd>
			</dl>
		</div>
		
		<div class="subsection">
			<h2>Conditionals:</h2>
			<dl>
				<dt>quiz_title</dt>
				<dd>The title of this quiz.</dd>
				<dt>quiz_description</dt>
				<dd>The description of this quiz.</dd>
				<dt>enabled</dt>
				<dd>TRUE if the quiz is enabled.</dd>
				<dt>passing_grade</dt>
				<dd>The passing grade for this quiz.</dd>
				<dt>one_at_a_time</dt>
				<dd>TRUE if the quiz displays questions one at a time.</dd>
				<dt>all_at_once</dt>
				<dd>TRUE if the quiz displays all questions at the same time.</dd>
				<dt>anonymous</dt>
				<dd>TRUE if the quiz is set to anonymous.</dd>
				<dt><b>--- grades tag option must NOT be disabled for the following conditionals ---</b></dt>
				<dd></dd>
				<dt>attempted_all</dt>
				<dd>TRUE if the currently logged in member has attempted every question.</dd>
				<dt>attempted_all_mandatory</dt>
				<dd>TRUE if the currently logged in member has attempted all mandatory questions.</dd>
				<dt>passing</dt>
				<dd>TRUE if the currently logged in member is passing the quiz.</dd>
				<dt>failing</dt>
				<dd>TRUE if the currently logged in member is failing the quiz.</dd>
				<dt>grade_score</dt>
				<dd>The raw score of the currently logged in member.</dd>
				<dt>grade_percent</dt>
				<dd>A percentage score of the currently logged in member.</dd>
				<dt>max_score</dt>
				<dd>The max possible score that can be obtained on this quiz.</dd>
			</dl>
		</div>
		
		<div class="subsection">
			<h2>Template Example:</h2>
			<p class='doc_example'>Select a Quiz:<br />
				{exp:eequiz:quizzes}<br />
				&nbsp;&nbsp;&nbsp;&lt;h1&gt;&lt;a href=&quot;{path=eequiz/{quiz_id}}&quot;&gt;{quiz_title}&lt;/a&gt;&lt;/h1&gt;<br />
				&nbsp;&nbsp;&nbsp;&lt;p&gt;<br />

				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;description: {quiz_description}&lt;br /&gt;<br />
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;score: {grade_score} out of {max_score}&lt;br /&gt;<br />
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;percent: {grade_percent}%&lt;br /&gt;<br />
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{if passing}you are currently passing this quiz{/if}&lt;br /&gt;<br />
				&nbsp;&nbsp;&nbsp;&lt;/p&gt;<br />
				{/exp:eequiz:quizzes}
			</p>
		</div>
	</div>

	
	<h3>Questions Tag</h3>
	<div class="doc_section">
		
		<div class="subsection">
			<h2>Overview:</h2>
			<p>This is a single tag. It outputs the question form(s) corresponding to the provided quiz_id. * NOTE: jQuery is required with this tag, and only one of these tags are allowed per page *</p>
		</div>
		
		<div class="subsection">
			<h2>Options:</h2>
			<dl>
				<dt>quiz_id</dt>
				<dd>Required. The id the quiz for which to get questions for.</dd>
				<dt>url_title</dt>
				<dd>Required if quiz_id is not set. The url_title of a specific quiz to get data for.</dd>
				<dt>continue</dt>
				<dd>Optional. If this is set to "yes","true", or "retake" and the quiz is sequential, then the quiz will start after the user's last answered question.</dd>
				<dt>include_js</dt>
				<dd>Optional. Defaults to "yes". If set to "no", you must include the {exp:eequiz:javascript} tag in your page.</dd>
				<dt>retake</dt>
				<dd>Optional. If this is set to "yes","true", or "retake", the quiz answers for the current user will be erased when starting the quiz. Use this when you want to automate user retakes. WARNING: if you have the questions tag with retake="yes" anywhere on the page, even if guarded with conditional template logic, it will still parse and therefore clear the current user's answers. See the following example for correct way to use this tag.
					<p class='doc_example'>
						<i>good example (eequiz module will determine whether to clear answers):</i><br /><br />
						{exp:eequiz:questions quiz_id="{segment_2}" retake="{segment_3}"}<br /><br />
						<i>bad example (both tags will execute behind the scenes, therefore clearing the current user's answers):</i><br /><br />
						{if segment_3=="retake"}<br />
						&nbsp;&nbsp;&nbsp;{exp:eequiz:questions quiz_id="{segment_2}" retake="yes"}<br />
						{if:else}<br />
						&nbsp;&nbsp;&nbsp;{exp:eequiz:questions quiz_id="{segment_2}"}<br />
						{/if}<br />
					</p>
				</dd>
				<dt>js_on_load_start</dt>
				<dd>Optional. Provide the name of a javascript function to add custom behavior for when loading begins.</dd>
				<dt>js_on_load_end</dt>
				<dd>Optional. Provide the name of a javascript function to add custom behavior for when loading ends.</dd>
				<dt>js_on_update</dt>
				<dd>Optional. An optional javascript callback when a question is updated on the page. Must receive one parameter (ex: onQuestionUpdate(json){} ).<br />
				The json sent to the callback has the following properties:<br />
					<ul>
					<li><span>json.updated_answer :</span> true if the user submitted an answer, false if the user changed to a different question</li>
					<li><span>json.quiz_id :</span> the quiz id</li>
					<li><span>json.question_number :</span> the question number</li>
					<li><span>json.num_questions :</span> the number of questions in this quiz</li>
					<li><span>json.attempted_all :</span> true if the user has attempted all questions in the quiz</li>
					<li><span>json.attempted_all_mandatory :</span> true if the user has attempted all mandatory questions in the quiz</li>
					<li><span>json.all_correct_or_no_more_attempts :</span> true if the user has gotten every question right and/or has no more attempts</li>
					<li><span>json.quiz_passing_grade :</span> the passing grade for this quiz</li>
					<li><span>json.quiz_max_score :</span> the max possible score possible for this quiz</li>
					<li><span>json.quiz_score :</span> the user's current quiz score, as the number of earned points</li>
					<li><span>json.quiz_percent :</span> the user's current quiz score, as a number between 0 and 100</li>
					<li><span>json.last_answer :</span> the user's last answer to the question</li>
					<li><span>json.correctness :</span> the correctness of the user's answer. Can be correct, incorrect, partially_correct, or blank (if there is no answer).</li>
					<li><span>json.attempts :</span> the amount of attempts the user used</li>
					<li><span>json.max_attempts :</span> the max amount of attempts allowed for this question</li>
					<li><span>json.weight :</span> the question's total weight</li>
					<li><span>json.score :</span> the user's earned score for the question</li>
					<li><span>json.submitted_all :</span> will be true if the user has just pressed submitted all</li>
					</ul>
				</dd>
			</dl>
		</div>
		
		<div class="subsection">
			<h2>Examples:</h2>
			<dl>
				<dt>standard quiz form</dt>
				<dd><p class='doc_example'>{exp:eequiz:questions quiz_id="1"}</p></dd>
				<dt>quiz form with extra javascript behavior</dt>
				<dd>
					<p class='doc_example'>
						<i>javascript code:</i><br />
						function questionCallback(results) {<br />

						&nbsp;&nbsp;&nbsp;if (results.updated_answer) {<br />
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;alert("Your answer is: "+results.correctness);<br />
						&nbsp;&nbsp;&nbsp;}<br />
						}<br />
						function loadStart() {<br />
						&nbsp;&nbsp;&nbsp;$("#loading_gif").show();<br />
						}<br />
						function loadEnd() {<br />
						&nbsp;&nbsp;&nbsp;$("#loading_gif").hide();<br />
						}<br />
						<br />
						<i>template code:</i><br />
						{exp:eequiz:questions quiz_id="1" js_on_update="questionCallback" js_on_load_start="loadStart" js_on_load_end="loadEnd"}
					</p>
				</dd>
			</dl>
		</div>
	</div>
	
	
	
	<h3>Answer Data Tag</h3>
	<div class="doc_section">
		
		<div class="subsection">
			<h2>Overview:</h2>
			<p>This is a tag pair. It gives access to both user and global quiz score variables. This can be used to show some overall data to the user (percentile, average scores, etc).</p>
		</div>
		
		<div class="subsection">
			<h2>Options:</h2>
			<dl>
				<dt>quiz_id</dt>
				<dd>Optional. Similar to channel:entries tag, this can be one id, a pipe separated list, or "not". If omitted, default will be all quizzes. Ex: quiz_id="1", quiz_id="1|2|3", quiz_id="not 1|2|3". </dd>
			</dl>
		</div>
		
		<div class="subsection">
			<h2>Variables:</h2>
			<dl>
				<dt>max_score</dt>
				<dd>The max possible total score of the specified quizzes.</dd>
				<dt>user_score</dt>
				<dd>The current user's total score for the specified quizzes.</dd>
				<dt>user_percent</dt>
				<dd>The current user's percentage for the specified quizzes.</dd>
				<dt>user_percentile</dt>
				<dd>The user's percentile compared to other users for the specified quizzes.</dd>
				<dt>num_users</dt>
				<dd>The number of people who have attempted the specified quizzes.</dd>
				<dt>average_percent</dt>
				<dd>The average percent of all people who have attempted the specified quizzes.</dd>
				<dt>average_score</dt>
				<dd>The average cumulative score of all people who have attempted the specified quizzes.</dd>
			</dl>
		</div>
		
		<div class="subsection">
			<h2>Conditionals:</h2>
			<dl>
				<dt>***</dt>
				<dd>All the variables listed above can be used with conditionals.</dd>
				<dt>passing_all</dt>
				<dd>TRUE if the current user is passing all the quizzes specified with the quiz_id parameter.</dd>
			</dl>
		</div>
		
		<div class="subsection">
			<h2>Examples:</h2>
			<dl>
				<dt>a simple results page</dt>
				<dd>
					<p class='doc_example'>
&lt;h1&gt;THIS QUIZ&lt;/h1&gt;<br />
{exp:eequiz:answer_data quiz_id="{segment_3}"}<br />
score: {user_percent}%&lt;br /&gt;<br />
percentile: {user_percentile}%&lt;br /&gt;<br />
{/exp:eequiz:answer_data}<br />
<br />
&lt;h1&gt;ALL QUIZZES&lt;/h1&gt;<br />
{exp:eequiz:answer_data}<br />
score: {user_percent}%&lt;br /&gt;<br />
percentile: {user_percentile}%&lt;br /&gt;<br />
{if passing_all}You are passing every quiz! Congrats!&lt;br /&gt;<br />
{if:else}You are not yet passing every quiz... keep trying!&lt;br /&gt;{/if}<br />
{/exp:eequiz:answer_data}<br />
					</p>
				</dd>
			</dl>
		</div>
		
		
		

	</div>
	
	
	<h3>Results Tag</h3>
	<div class="doc_section">
		
		<div class="subsection">
			<h2>Overview:</h2>
			<p>This is a tag pair. It gives access to question variables, including user data (score, last answer, number of attempts, etc). This can be used to create a custom results page.</p>
		</div>
		
		<div class="subsection">
			<h2>Options:</h2>
			<dl>
				<dt>quiz_id</dt>
				<dd>Required. The id of the quiz.</dd>
				<dt>url_title</dt>
				<dd>Required if quiz_id is not set. The url_title of the quiz.</dd>
				<dt>unrolled</dt>
				<dd>Optional. If this is set to "yes" or "true", the tag will no longer loop through each question. Instead, all variables will be available on the spot, but with prefixes of q#_, where # is the question number. (ex: q1_title is the first question's title). This is useful if you want to test for advanced conditionals (for example, if user answered A to the first question, and C to the second question, etc).</dd>
			</dl>
		</div>
		
		<div class="subsection">
			<h2>Variables:</h2>
			<p>* If "unrolled" is being used, these variables will be prefixed by "q#_", where # is the question number.</p>
			<dl>
				<dt>number</dt> 
				<dd>The current question number.</dd>
				<dt>title</dt> 
				<dd>The title of the question.</dd>
				<dt>text</dt> 
				<dd>The text of the question.</dd>
				<dt>explanation</dt> 
				<dd>The explanation of the question.</dd>
				<dt>user_answer</dt> 
				<dd>The user's last answer for this question, in its raw form. See "Using Answer Tags" below for more detail on this tag.</dd>
				<dt>user_answer_formatted</dt> 
				<dd>The user's last answer for this question, formatted to be more readable.</dd>
				<dt>correct_answer</dt> 
				<dd>The correct answer for this question. See "Using Answer Tags" below for more detail on this tag.</dd>
				<dt>score</dt> 
				<dd>The user's current score for this question.</dd>
				<dt>max_score</dt> 
				<dd>The max possible score for this question.</dd>
				<dt>num_attempts</dt> 
				<dd>The number of times the user has attempted this question.</dd>
				<dt>max_attempts</dt> 
				<dd>The max number of attempts this question allows.</dd>
				<dt>correctness</dt> 
				<dd>Can be either "correct", "incorrect", "partially correct", or empty (if they have not attempted).</dd>
				<dt>options</dt> 
				<dd>* Only applies to multiple choice questions; use conditional "type" to verify *<br />Tag pair. Contains variables: option_number, option_text, option_feedback, option_weight. Contains conditionals: option_number, option_is_selected, and option_is_answer.</dd>
				<dt>matching_problems</dt> 
				<dd>* Only applies to matching questions; use conditional "type" to verify *<br />Tag pair. Contains variables: problem_number, problem_text, problem_answer, problem_selection. Contains conditionals: problem_number, problem_answer, problem_selection.</dd>
				<dt>matching_choices</dt> 
				<dd>* Only applies to matching questions; use conditional "type" to verify *<br />Tag pair. Contains variables: choice_number, choice_text. Contains conditionals: choice_number.</dd>
				
				
			</dl>
		</div>
		
		<div class="subsection">
			<h2>Conditionals:</h2>
			<p>* If "unrolled" is being used, these conditionals will be prefixed by "q#_", where # is the question number.</p>
			<dl>
				<dt>number</dt> 
				<dd>The current question number.</dd>
				<dt>type</dt> 
				<dd>The current question type. Possible values are: "multiple_choice", "true_false", "fill_in_the_blank", "essay", or "matching".</dd>
				<dt>user_answer</dt> 
				<dd>The user's last answer for this question, in its raw form. See "Using Answer Tags" below for more detail on this tag.</dd>
				<dt>correct_answer</dt> 
				<dd>The correct answer for this question. See "Using Answer Tags" below for more detail on this tag.</dd>
				<dt>score</dt> 
				<dd>The user's current score for this question.</dd>
				<dt>max_score</dt> 
				<dd>The max possible score for this question.</dd>
				<dt>num_attempts</dt> 
				<dd>The number of times the user has attempted this question.</dd>
				<dt>max_attempts</dt> 
				<dd>The max number of attempts this question allows.</dd>
				<dt>correct</dt> 
				<dd>True if the user's answer is correct.</dd>	
				<dt>incorrect</dt> 
				<dd>True if the user's answer is incorrect.</dd>	
				<dt>partially_correct</dt> 
				<dd>True if the user's answer is partially correct.</dd>	
			</dl>
		</div>
		
		<div class="subsection">
			<h2>Using Answer Tags:</h2>
			<p>Here is a breakdown of how answers are output, so you will have a better idea how to use them with conditionals.</p>
			<dl>
				<dt>multiple choice - single answer</dt> 
				<dd>Outputs the number of the option the user selected. For example, it outputs "1" if the user selects the first option. If randomized, it uses the option order from when you submitted the question.</dd>
				<dt>multiple choice - multiple answer</dt> 
				<dd>Outputs the number of the options the user selected, separated by spaces. For example, it outputs "1 3" if the user selects the first and third options. This is sorted in order.</dd>
				<dt>multiple choice - write-in</dt>
				<dd>If the user does a write-in, it outputs "WRITE-IN:answer". For example, if the user wrote-in "4-ee", it outputs "WRITE-IN:4-ee".</dd>
				<dt>true/false</dt>
				<dd>Outputs "1" for true, "0" for false.</dd>
				<dt>matching</dt>
				<dd>Outputs each problem selection, separated by spaces, in problem-order. For example, if the user matched 1->3, 2->2, and 3->1, the output would be "3 2 1".</dd>
				<dt>fill in the blank</dt>
				<dd>Outputs exactly as the user entered, but with html characters escaped and new lines to break tags.</dd>
				<dt>essay</dt>
				<dd>Outputs exactly as the user entered, but with html characters escaped and new lines to break tags.</dd>
			</dl>
		</div>
		
		<div class="subsection">
			<h2>Examples:</h2>
			<dl>
				<dt>a simple results page</dt>
				<dd>
					<p class='doc_example'>
						{exp:eequiz:results quiz_id="1"}<br />
						&nbsp;&nbsp;&nbsp;{number}. {text}&lt;br /&gt;<br />
						&nbsp;&nbsp;&nbsp;Your answer is {correctness}.&lt;br /&gt;<br />
						&nbsp;&nbsp;&nbsp;Your answer: {user_answer_formatted}&lt;br /&gt;<br />
						&nbsp;&nbsp;&nbsp;Correct answer: {correct_answer}&lt;br /&gt;<br />
						&nbsp;&nbsp;&nbsp;&lt;hr /&gt;<br />
						{/exp:eequiz:results}<br />
					</p>
				</dd>
				<dt>an in-depth results page using the "unrolled" parameter, with custom logic based on user answers</dt>
				<dd>
					<p class='doc_example'>
						{exp:eequiz:results quiz_id="1" unrolled="yes"}<br />
						&nbsp;&nbsp;&nbsp;{if q1_user_answer==1 &amp;&amp; q2_user_answer==2}You answered A to the first question and B to the second, so that means you are...{/if}<br />
						&nbsp;&nbsp;&nbsp;{if q1_user_answer==2 &amp;&amp; q2_user_answer==2}You answered B to the first question and B to the second, which means you should...{/if}<br />
						{/exp:eequiz:results}<br />
					</p>
				</dd>
			</dl>
		</div>
		
	</div>
	
	
	<h3>Javascript Tag</h3>
	<div class="doc_section">
		
		<div class="subsection">
			<h2>Overview:</h2>
			<p>This is a single tag. Add it to your page if you did include_js="no" on your {exp:eequiz:questions} tag. Note: if you use any of the options below, you MUST define the javascript functions before you insert this tag!</p>
		</div>
		
		<div class="subsection">
			<h2>Options:</h2>
			<dl>
				<dt>js_on_load_start</dt>
				<dd>Optional. Provide the name of a javascript function to add custom behavior for when loading begins.</dd>
				<dt>js_on_load_end</dt>
				<dd>Optional. Provide the name of a javascript function to add custom behavior for when loading ends.</dd>
				<dt>js_on_update</dt>
				<dd>Optional. An optional javascript callback when a question is updated on the page. Must receive one parameter (ex: onQuestionUpdate(json){} ). Refer to the questions tag for more information.</dd>
			</dl>
		</div>
		
	</div>
	
	
	
	
	<h3>Quiz Template Reference</h3>
	<div class="doc_section">
	
		<div class="subsection">
			<h2>Simple Value Tags</h2>
			<p>The following tags are values only and include no html/css. Feel free to use them in conditionals.</p>
			<ul>
			<li>{num_questions}</li>
			<li>{question_number}</li>
			<li>{question_title}</li>
			<li>{question_shortname}</li>
			<li>{question_tags}</li>
			<li>{text}</li>
			<li>{attempts}</li>
			<li>{max_attempts}</li>
			<li>{remaining_attempts}</li>
			<li>{answer_time format="m/d/Y"} : The time the user entered their last answer. The format parameter is optional; see php's <a href="http://php.net/manual/en/function.date.php">date documentation</a> for details on how to use it.</li>
			<li>{score} : The user's earned score for this question</li>
			<li>{weight} : The amount of points this question is worth</li>
			<li>{correctness} : Will be either "correct", "incorrect", "partially_correct", depending on the user's answer.</li>
			<li>{feedback_explanation} : This is the overall question explanation, with no extra html. Use this if you want to get at what's inside {feedback_section}</li>
			<li>{feedback_extra} : This is question-specific extra feedback, with no html. Use this if you want to get at what's inside {feedback_section}</li>
			<li>{quiz_title}</li>
			<li>{quiz_id}</li>
			<li>{quiz_description}</li>
			<li>{quiz_score} : The amount of points the user has earned on this quiz</li>
			<li>{quiz_percent} : The user's current score for this quiz, expressed as a number between 0 and 100</li>
			<li>{quiz_max_score} : The max amount of points possible on this quiz</li>
			<li>{quiz_passing_score} : The amount of points to be considered passing for this quiz</li>
			</ul>
		</div>
		<div class="subsection">
			<h2>Section Tags</h2>
			<p>The following tags include html and css classes. See documentation for styling guidelines.</p>
			<dl>
			<dt>{answer_section}</dt>
			<dd>The section where the user enters their answer</dd>
			<dt>{feedback_section}</dt>
			<dd>The section where feedback is provided to the user</dd>
			<dt>{correctness}</dt>
			<dd>The section that tells whether the user got the problem right or wrong (ex: &lt;div class='correct_mark'&gt;&lt;span&gt;correct&lt;/span&gt;&lt;div&gt;). If used within a conditional, it will evaluate to "", "correct", "incorrect", or "partially_correct".</dd>
			<dt>{previous}</dt>
			<dd>The button to go to the previous question. Will automatically evaluate to nothing if quiz display is set to 'view all at once'.</dd>
			<dt>{next}</dt>
			<dd>The button to go to the next question. Will automatically evaluate to nothing if quiz display is set to 'view all at once'.</dd>
			<dt>{submit}</dt>
			<dd>The button to submit the user's answer.</dd>
			<dt>{submit_and_advance}</dt>
			<dd>This button will submit the user's answer and automatically advance to the next question. If this is on an all-at-once quiz, it will revert to a regular submit button.</dd>
			</dl>
		</div>

	</div>
	
	
	
	
	<h3>CSS Styles</h3>
	<div class="doc_section">
	
		<div class="subsection">
			<h2>General Styles</h2>
			<dl>
				<dt>div.eequiz</dt>
				<dd>Div that wraps each question.</dd>
				<dt>div.eequiz_loading</dt>
				<dd>Div that wraps each question when loading is happening. Use this to add custom load styles, or to show/hide loading gifs.</dd>
				
				<dt>div.eequiz div.answer_section</dt>
				<dd>Wraps the answer section.</dd>
				<dt>div.eequiz div.answer_footer</dt>
				<dd>Goes right before the closing div of the answer section; commonly used to clear floats.</dd>
				
				<dt>div.eequiz div.feedback_section</dt>
				<dd>Wraps the feedback section.</dd>
				
				<dt>div.eequiz div.incorrect_mark</dt>
				<dd>Indicates in incorrect answer.</dd>
				<dt>div.eequiz div.partially_correct_mark</dt>
				<dd>Indicates a partially correct answer.</dd>
				<dt>div.eequiz div.correct_mark</dt>
				<dd>Indicates a correct answer.</dd>
				<dt>div.eequiz div span.mark_text</dt>
				<dd>The text inside a correctness mark.</dd>
				
				<dt>div.eequiz a.next_link</dt>
				<dd>A link to the next question.</dd>
				<dt>div.eequiz a.previous_link</dt>
				<dd>A link to the previous question.</dd>
				<dt>div.eequiz a.disabled</dt>
				<dd>A next or previous button that is disabled.</dd>
				<dt>div.eequiz input.submit_answer_button</dt>
				<dd>The button that submits the user's answer.</dd>
				<dt>input.eequiz_submit_all_button</dt>
				<dd>The button that submits all the user's answers (only applicable to all-at-once quizzes).</dd>
			</dl>
			<div style="clear: both"></div>
		</div>

		
		<div class="subsection">
			<h2>Question-Type Styles</h2>
			<dl>
				<dt>div.eequiz ol.multiple_choice_options</dt>
				<dd>Ordered list that contains the choices for a multiple choice question.</dd>
				<dt>div.eequiz ol.matching_problems</dt>
				<dd>List that contains the problems for a matching question.</dd>
				<dt>div.eequiz ol.matching_choices</dt>
				<dd>Ordered list that contains the choices for a matching question.</dd>
			</dl>
			<div style="clear: both"></div>
		</div>
		
		
		<div class="subsection">
			<h2>Quick Start / Example CSS</h2>
			<p>The following styles were designed according to the default quiz template.</p>
			<p class='doc_example'>
				/* ======================================================== */
				<br />/* general question styles */
				<br />
				<br />div.eequiz {
				<br />&nbsp;&nbsp;&nbsp;width: 400px;
				<br />&nbsp;&nbsp;&nbsp;padding: 5px;
				<br />&nbsp;&nbsp;&nbsp;margin: 5px auto;
				<br />&nbsp;&nbsp;&nbsp;background-color: #eee;
				<br />}
				<br />
				<br />div.eequiz h1 {
				<br />&nbsp;&nbsp;&nbsp;padding: 5px;
				<br />&nbsp;&nbsp;&nbsp;margin: 5px;
				<br />&nbsp;&nbsp;&nbsp;font-size: 24px;
				<br />&nbsp;&nbsp;&nbsp;font-weight: normal;
				<br />&nbsp;&nbsp;&nbsp;color: #000;
				<br />}
				<br />
				<br />div.eequiz div.question_info {
				<br />&nbsp;&nbsp;&nbsp;padding: 5px;
				<br />&nbsp;&nbsp;&nbsp;margin: 5px;
				<br />&nbsp;&nbsp;&nbsp;color: #555;
				<br />&nbsp;&nbsp;&nbsp;background-color: #ccc;
				<br />&nbsp;&nbsp;&nbsp;font-style: italic;
				<br />&nbsp;&nbsp;&nbsp;font-size: 12px;
				<br />&nbsp;&nbsp;&nbsp;line-height: 1.2em;
				<br />}
				<br />
				<br />div.eequiz div.question_text {
				<br />&nbsp;&nbsp;&nbsp;clear: both;
				<br />&nbsp;&nbsp;&nbsp;border-bottom: 1px solid #ccc;
				<br />&nbsp;&nbsp;&nbsp;padding: 10px;
				<br />}
				<br />
				<br />div.eequiz div.answer_section {
				<br />&nbsp;&nbsp;&nbsp;padding: 10px;
				<br />&nbsp;&nbsp;&nbsp;border-bottom: 1px solid #ccc;
				<br />}
				<br />div.eequiz div.answer_footer {
				<br />&nbsp;&nbsp;&nbsp;clear: both;
				<br />}
				<br />
				<br />div.eequiz div.feedback_section {
				<br />&nbsp;&nbsp;&nbsp;margin-bottom: 10px;
				<br />}
				<br />
				<br />/* ======================================================== */
				<br />/* answer mark styles */
				<br />
				<br />div.eequiz div.incorrect_mark {
				<br />&nbsp;&nbsp;&nbsp;padding: 5px;
				<br />&nbsp;&nbsp;&nbsp;margin-bottom: 10px;
				<br />&nbsp;&nbsp;&nbsp;color: #F00;
				<br />&nbsp;&nbsp;&nbsp;background-color: #FEE;
				<br />}
				<br />div.eequiz div.partially_correct_mark {
				<br />&nbsp;&nbsp;&nbsp;padding: 5px;
				<br />&nbsp;&nbsp;&nbsp;margin-bottom: 10px;
				<br />&nbsp;&nbsp;&nbsp;color: #BB0;
				<br />&nbsp;&nbsp;&nbsp;background-color: #FFE;
				<br />}
				<br />div.eequiz div.correct_mark {
				<br />&nbsp;&nbsp;&nbsp;padding: 5px;
				<br />&nbsp;&nbsp;&nbsp;margin-bottom: 10px;
				<br />&nbsp;&nbsp;&nbsp;color: #0F0;
				<br />&nbsp;&nbsp;&nbsp;background-color: #EFE;
				<br />}
				<br />div.eequiz div span.mark_text {
				<br />}
				<br />
				<br />/* ======================================================== */
				<br />/* controls styles */
				<br />
				<br />div.eequiz div.question_controls {
				<br />&nbsp;&nbsp;&nbsp;margin-top: 20px;
				<br />&nbsp;&nbsp;&nbsp;clear: both;
				<br />}
				<br />div.eequiz a.next_link {
				<br />&nbsp;&nbsp;&nbsp;float: right;
				<br />&nbsp;&nbsp;&nbsp;margin-right: 10px;
				<br />}
				<br />div.eequiz a.previous_link {
				<br />&nbsp;&nbsp;&nbsp;float: left;
				<br />&nbsp;&nbsp;&nbsp;margin-left: 10px;
				<br />}
				<br />div.eequiz a.disabled {
				<br />&nbsp;&nbsp;&nbsp;color: #CCC;
				<br />&nbsp;&nbsp;&nbsp;cursor: default;
				<br />}
				<br />div.eequiz input.submit_answer_button {
				<br />&nbsp;&nbsp;&nbsp;display: block;
				<br />&nbsp;&nbsp;&nbsp;width: 150px;
				<br />&nbsp;&nbsp;&nbsp;margin: 0 auto;
				<br />}
				<br />
				<br />/* ======================================================== */
				<br />/* multiple choice styles */
				<br />
				<br />div.eequiz ol.multiple_choice_options {
				<br />}
				<br />
				<br />/* ======================================================== */
				<br />/* matching styles */
				<br />
				<br />div.eequiz ol.matching_problems {
				<br />&nbsp;&nbsp;&nbsp;width: 150px;
				<br />&nbsp;&nbsp;&nbsp;float: left;
				<br />}
				<br />div.eequiz ol.matching_problems li select {
				<br />&nbsp;&nbsp;&nbsp;margin-right: 5px;
				<br />}
				<br />div.eequiz ol.matching_choices {
				<br />&nbsp;&nbsp;&nbsp;width: 150px;
				<br />&nbsp;&nbsp;&nbsp;float: right;
				<br />&nbsp;&nbsp;&nbsp;list-style-type: decimal;
				<br />}
				<br />div.eequiz ol.matching_choices li {
				<br />&nbsp;&nbsp;&nbsp;margin-left: 30px;
				<br />&nbsp;&nbsp;&nbsp;padding-left: 5px;
				<br />} 
			</p>
		</div>
	</div>
	
	<h3>FAQ</h3>
	<div class="doc_section">
	
		<div class="subsection">
			<h2>When I submit a question, it just redirects me to a blank page with some jibberish (json). What's wrong?</h2>
			<p>You probably don't have jquery included on your page, which is required.</p>
		</div>
	
		<div class="subsection">
			<h2>How can I add extra data to questions, such as images, files, and custom text fields?</h2>
			<p>Create a new channel and add any custom fields you want to it. Then, in your quiz template, add the following code (example):
			<p class='doc_example'>
				{exp:channel:entries url_title="{question_shortname}" dynamic="off"}
				<br />Here is a custom text: {custom_text_1}&lt;br /&gt;
				<br />Here is an image: &lt;img src={my_custom_image} /&gt;
				<br />{/exp:channel:entries}
			</p>
			<p>Now as you create questions, if you want extra data to be pulled in, simply publish an entry with the same url_title as your question_shortname. Note: dynamic="off" is very important, since you do not want to pull data using the uri.<p>
		</div>
	
		<div class="subsection">
			<h2>How can I make my quiz redirect to a new page, once the user has finished taking it?</h2>
			<p>First add js_on_update="questionCallback" to your {exp:eequiz:questions} tag. Then, add the following javascript to your page (edit as needed):
			<p class='doc_example'>
			function questionCallback(results) {
			<br />&nbsp;&nbsp;&nbsp;if (results.attempted_all) {
			<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;window.location = "http://www.[REDIRECT PAGE].com";
			<br />&nbsp;&nbsp;&nbsp;}
			<br />}
			</p>
			<p>Don't let that scare you, if you don't know javascript! It's very simple. Here's an english translation: "if the user has attempted every question, then redirect them."</p>
		</div>
		
	</div>

</div>
