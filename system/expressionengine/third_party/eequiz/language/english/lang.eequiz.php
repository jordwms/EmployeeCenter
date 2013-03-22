<?php 

$lang = array( 


//---------------------------------------- 
// front-end messages (edit these as needed)

"next"						=> "Next",
"previous"					=> "Previous",
"submit_answer"				=> "Submit Answer",
"correct"					=> "correct",
"incorrect"					=> "incorrect",
"partially_correct"			=> "partially correct",
"error_need_answer"			=> "Error: you must enter an answer before submitting.",
"error_no_attempts_left"	=> "Error: you have no attempts left.",
"error_same_answer"			=> "Error: please enter a different answer before submitting.",
"error_logged_out"			=> "Error: you must be logged in to take this quiz.",
"error_disabled"			=> "Error: this quiz is currently disabled.",
"error_same_answer"			=> "Error: please enter a different answer before submitting.",






//---------------------------------------- 
// Required for MODULES page 
//---------------------------------------- 

"eequiz_module_name" => 
"4-eeQuiz", 

"eequiz_module_description" => 
"A quiz engine", 


//---------------------------------------- 
// MISC GLOBAL

'manage_questions'		=> "Questions",
'manage_quizzes'		=> "Quizzes",
'view_answer_data'		=> "Answer Data",
'view_documentation'	=> "Documentation",

"view_questions"		=> "Questions",
"view_quizzes"			=> "Quizzes",
"view_quiz_templates"	=> "Templates",
"export_all_answers"	=> "Export All Answers",


//---------------------------------------- 
// VIEW ALL QUESTIONS PAGE

"edit_question"				=> "Edit",

"create_question_button"	=> "Create Question",
"Essay"						=> "Essay",
"FillInTheBlank"			=> "Fill In The Blank",
"Matching"					=> "Matching",
"MultipleChoice"			=> "Multiple Choice",
"TrueFalse"					=> "True/False",

"create_question_prompt"	=>
"What kind of question will this be?",

"delete_question_prompt"	=>
"Are you sure you wish to delete {question_title}? All answers to this question will be lost forever.",

"question_id"				=> "Question ID",
"question_title"			=> "Title",
"shortname"					=> "Shortname",
"contained_in_quiz"			=> "Contained In Quizzes",


//---------------------------------------- 
// EDIT QUESTION PAGE

//headers
"general_information"		=> "General Information",
"general_settings"			=> "General Settings",
"answer_settings"			=> "Answer Settings",

//labels and descriptions
"question_title_lbl"		=> "Title",
"question_title_desc"		=> "",

"question_shortname_lbl"	=> "Shortname",
"question_shortname_desc"	=> "A unique name identifying the question. Can be letters, numbers, and underscores.",

"question_type_lbl" 		=> "Type",

"question_text_lbl"			=> "Question",
"question_text_desc"		=> "The actual question to be posed.",

"question_explanation_lbl"	=> "Explanation",
"question_explanation_desc"	=> "An explanation to the question.",

"question_optional_lbl"		=> "Optional",
"question_optional_desc"	=> "If optional, the user will be able to proceed past this question in a sequential quiz and it will not be required for quiz completion.",

"question_weight_lbl"		=> "Weight",
"question_weight_desc"		=> "The amount of points this question is worth.",

"question_max_attempts_lbl"	=> "Number of Attempts",
"question_max_attempts_desc"	=> "The number of attempts a user is allowed for this question. Enter 0 for unlimited attempts.",



//---------------------------------------- 
// VIEW ALL QUIZ PAGE

"create_quiz_button"			=> "Create Quiz",

"delete_quiz_prompt"		=> "Are you sure you wish to delete {quiz_title}? All answers to this quiz will be lost forever.",

"quiz_id"					=> "Quiz ID",
"quiz_title"				=> "Title",
"contained_text"			=> "Contained In Quizzes",




//---------------------------------------- 
// EDIT QUIZ PAGE

"add_question_prompt"		=> "Which question would you like to add?",

"edit_quiz"					=> "Edit",

"quiz_information"			=> "General Information",
"quiz_settings"				=> "Settings",
"quiz_questions"			=> "Questions",

"quiz_title_lbl"			=> "Title",
"quiz_title_desc"			=> "",
"quiz_description_lbl"		=> "Description",
"quiz_description_desc"		=> "",
"quiz_template_lbl"			=> "Question Template",
"quiz_template_desc"		=> "",
"quiz_status_lbl"			=> "Enabled",
"quiz_status_desc"			=> "If disabled, nobody will be able to submit answers to this quiz.",
"quiz_feedback_lbl"			=> "Feedback Mode",
"quiz_feedback_desc"		=> "Choose how you want feedback (question explanation and option feedback) to be displayed to the users. The {feedback_section} tag respects this setting.",
"quiz_display_lbl"			=> "Display All At Once",
"quiz_display_desc"			=> "",
"quiz_submit_all_lbl"		=> "Show Submit All Button",
"quiz_submit_all_desc"		=> "This option only applies to quizzes set to 'display all at once.' If enabled, it will add a button underneath the questions, giving the user the ability to submit them all at once.",
"quiz_anonymous_lbl"		=> "Anonymous Answer Tracking",
"quiz_anonymous_desc"		=> "If enabled, users do not have to log in to take this quiz. Their answers will expire after 24 hours on the user-side, but you will be able to view them permantly using this module. Example uses for this mode are simple polls and anonymous surveys.",
"quiz_order_lbl"			=> "Order",
"quiz_order_desc"			=> "",
"quiz_passing_grade_lbl"	=> "Passing Grade",
"quiz_passing_grade_desc"	=> "Should be a number between 0 and 100 (represents the percentage of the max score to pass).",
"quiz_add_question_lbl"		=> "Add A Question",
"quiz_add_question_desc"	=> "Drag and drop questions from 'Unused Questions' to 'Questions In This Quiz'.",














//---------------------------------------- 
// QUIZ TEMPLATES PAGE

"create_quiz_template_button"	=> "Create Template",

"delete_quiz_template_prompt"	=> "Are you sure you wish to delete {template_title}? This cannot be undone!",

"template_id"					=> "Template ID",
"template_title"				=> "Title",


"edit_quiz_template"			=> "Edit",

"template_information"			=> "General Information",
"template_settings"				=> "Template",
"template_reference"			=> "Reference",


"quiz_template_title_lbl"		=> "Title",
"quiz_template_title_desc"		=> "",
"quiz_template_shortname_lbl"	=> "Shortname",
"quiz_template_shortname_desc"	=> "",
"quiz_template_template_lbl"	=> "Template",
"quiz_template_template_desc"	=> "",









//---------------------------------------- 
// ANSWER DATA PAGE

"quiz_statistics"				=> "Quiz Statistics",
"question_statistics"			=> "Question Answers (members)",
"anon_question_statistics"		=> "Question Answers (anonymous)",

"statistic_header"				=> "Statistic",
"value_header"					=> "Value",

"question_header"				=> "Question",
"final_answers_header"			=> "Final Answers",
"all_answers_header"			=> "All Answers",










//---------------------------------------- 
// VIEW DOCUMENTATION PAGE

"documentation"				=> "Documentation",



//---------------------------------------- 
// APPLUS RTD CUSTOM VALUES
'manage_quiz_groups_tip'	=> 'Click on group name to manage quizzes in group.',
'overview'					=> 'Overview',
'manage_quiz_groups'		=> 'Manage Quiz Groups',
'overview_tip'				=> 'Click on group results to view score card.',
'overview_tip2'				=> 'Use Ctrl+F to find username on page.',
'quiz_group_details'		=> 'Quiz Group Details',
'add'						=> 'Add',
'quizzes_for_group'			=> 'Quizzes for Group',




























// END 
''=>'' 
);