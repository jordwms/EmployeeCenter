var QEAjaxURL = "";
var moduleURL = "";

$(document).ready(function () {

	$('#publishdropmenu').css('zIndex', '100');
		
	$("a#create_question_dropdown_btn").fancybox({
		modal	: true,
		margin	: 20,
		padding	: 10
	});
	
	// dropdowns for ie6
	$("#iv_nav li").each(function(){
		$(this).bind('mouseover', function(){$(this).addClass('sfHover');});
		$(this).bind('mouseout', function(){$(this).removeClass('sfHover');});
	});

});


var eequiz = {
	
	shortname_separator : "_",
	
	showPopup : function(success, message) {
		
		$.fancybox.close();
		$.fancybox.hideActivity();
		
		if (success) $.ee_notice(message, {type:'success'});
		else $.ee_notice(message, {type:'error'});
		
		/*setTimeout(function(){
		$.fancybox("<p class='"+(success ? 'success_popup' : 'error_popup')+"'>"+message+"</p>", {
			padding		: 5,
			margin		: 20,
			scrolling	: "no"
		});}, 400);*/
	},
	
	showConfirmation : function(message, onClickCode) {
		
		$.fancybox.close();
		$.fancybox.hideActivity();
		
		setTimeout(function(){
		$.fancybox("<p class='warning_popup'>"+message+"</p>"+
					"<a class='prompt_continue' href='javascript:void(0)' onclick='"+onClickCode+"'>continue</a> <a class='prompt_cancel' href='javascript:void(0)' onclick='$.fancybox.close()'>cancel</a> ", {
			padding		: 5,
			margin		: 20,
			modal		: true,
			scrolling	: "no"
		});});
	},
	
	showPopupFromJSON : function(json) {
		
		eequiz.showPopup(json.success, json.message);
	},
	
	createQuestion : function() {
		
		$.fancybox.close();
		
		var questionType = $('#create_question_type_radio_wrapper input:checked').val();
		setTimeout(function(){window.location.href = moduleURL+"&method=edit_question&question_type="+questionType;}, 0);
	},
	
	liveShortname : function(shortnameId, titleId) {
		
		if ($("#"+shortnameId).attr("value") != "") return;
		
		$("#"+titleId).bind("keyup blur", function(){
			
			var newText = $("#"+titleId).attr('value');
			newText = newText.toLowerCase();
			
			var separator = eequiz.shortname_separator;

			var multiReg = new RegExp(separator + '{2,}', 'g');

			newText = newText.replace('/<(.*?)>/g', '');
			newText = newText.replace(/\s+/g, separator);
			newText = newText.replace(/\//g, separator);
			newText = newText.replace(/[^a-z0-9\-\._]/g,'');
			newText = newText.replace(/\+/g, separator);
			newText = newText.replace(/\./g, separator);
			newText = newText.replace(multiReg, separator);
			newText = newText.replace(/-$/g,'');
			newText = newText.replace(/_$/g,'');
			newText = newText.replace(/^_/g,'');
			newText = newText.replace(/^-/g,'');
			
			$("#"+shortnameId).attr("value", newText);
		});
		
		$("#"+shortnameId).bind("keyup", function(){
			$("#"+titleId).unbind("keyup blur")
		});
	}

};


var viewQuestions = {

	delete_message : "",
	
	fillTable : function(json) {
		
		if (json.num_rows > 0) $("#questions_tbody").html(json.html_string);
		else $("#questions_tbody").html("");
	},
	
	ajaxSortQuestions : function(field) {
	
		var buttons = ["question_id", "title", "question_shortname"];
		for (var i = 0; i < buttons.length; i++) {
			
			if (buttons[i] != field) {
				$("#"+buttons[i]+"_sort_btn").addClass('sorting');
				$("#"+buttons[i]+"_sort_btn").removeClass('headerSortUp');
				$("#"+buttons[i]+"_sort_btn").removeClass('headerSortDown');
			}
		}
		
		var classname = $('#'+field+'_sort_btn').attr('class');
		var dir = (classname == 'sorting' || classname == 'headerSortDown') ? "ASC" : "DESC";
		
		$('#'+field+'_sort_btn').removeClass("sorting");
		$('#'+field+'_sort_btn').removeClass("headerSortUp");
		$('#'+field+'_sort_btn').removeClass("headerSortDown");
		$('#'+field+'_sort_btn').addClass((dir == "ASC") ? "headerSortUp" : "headerSortDown");
		
		var data = {
			sort : field,
			direction : dir,
			rand : new Date().getTime()
		};
		
		$.get(moduleURL+"&method=ajax_questions_table", data, viewQuestions.fillTable, "json");
	},
	
	duplicateQuestion : function(question_id) {
		
		//$.fancybox({'padding':0,'margin':0,'modal':true});
		$.fancybox.showActivity();
		$.post(moduleURL+"&method=ajax_duplicate_question&question_id="+question_id, null, viewQuestions.changedQuestionList, "json");
	},
	
	deleteQuestion : function(question_id) {
		
		$.fancybox.close();
		$.fancybox.showActivity();
		$.post(moduleURL+"&method=ajax_delete_question&question_id="+question_id, null, viewQuestions.changedQuestionList, "json");
	},
	
	changedQuestionList : function(json) {
		
		var dir = "";
		var field = "";
		
		var buttons = ["question_id", "title", "question_shortname"];
		for (var i = 0; i < buttons.length; i++) {
			
			if ($("#"+buttons[i]+"_sort_btn").hasClass("headerSortUp") ) {
				field = buttons[i];
				dir = "ASC";
				break;
			}
			else if ($("#"+buttons[i]+"_sort_btn").hasClass("headerSortDown") ) {
				field = buttons[i];
				dir = "DESC";
				break;
			}
		}
		
		var data = {
			sort : field,
			direction : dir,
			rand : new Date().getTime()
		};
		
		$.get(moduleURL+"&method=ajax_questions_table", data, viewQuestions.fillTable, "json");
		
		eequiz.showPopup(json.success, json.message);
	}
}


var editQuestion = {
	
	preSubmitFunction : false,
	postSubmitFunction : false,
	
	submitQuestionForm : function() {
		
		var result = validateForm('question_form', 'invalid_input');
		if (!result) {
			eequiz.showPopup(false, "Error: There are problems with your values.");
			return false;
		}
		
		if (editQuestion.preSubmitFunction) {
			
			var result = editQuestion.preSubmitFunction();
			if (!result) {
				eequiz.showPopup(false, "Error: There are problems with your values.");
				return false;
			}
		}
		
		$.post($('#question_form').attr('action'), getValues('question_form'), editQuestion.submitCallback, "json");
		
		//var dest = $('#question_form').attr('action');
		/*dest = dest.replace(/\?/g, "%3f");
		dest = dest.replace(/&/g, "%26");
		dest = dest.replace(/=/g, "%3D");
		dest = "http://localhost/ee2/system/index.php?URL=localhost%2Fee2%2Fsystem%2F"+dest
		alert(dest);*/
		/*$.ajax({
			type: "post",
			url: dest,
			data: $('#question_form').serialize(),
			//data: $('#question_form').serializeArray(),
			success: editQuestion.submitCallback,
			dataType: "json",
			cache: false,
			contentType: "application/x-www-form-urlencoded"
			//contentType: "application/json; charset=utf-8"
			});*/
		
		$.fancybox.showActivity();
		
		return false;
	},
	
	submitCallback : function(json, status) {
		
		$.fancybox.hideActivity();
		
		if (editQuestion.postSubmitFunction) editQuestion.postSubmitFunction();
		
		if (json.success) {
			window.location.replace(moduleURL+"&method=view_questions&message=success");
			return;
		}
		
		eequiz.showPopup(json.success, json.message);

		// now that question is created/updated, update the form to contain the correct question_id
		var form = document.getElementById('question_form');
		form.question_id.value = json.question_id;
	}
	
}


var viewQuizzes = {
	
	delete_message : "",
	
	fillTable : function(json) {
		
		if (json.num_rows > 0) $("#quizzes_tbody").html(json.html_string);
		else $("#quizzes_tbody").html("");
	},
	
	ajaxSortQuizzes : function(field) {
		
		var buttons = ["quiz_id", "title"];
		for (var i = 0; i < buttons.length; i++) {
			if (buttons[i] != field) {
				$("#"+buttons[i]+"_sort_btn").addClass('sorting');
				$("#"+buttons[i]+"_sort_btn").removeClass('headerSortUp');
				$("#"+buttons[i]+"_sort_btn").removeClass('headerSortDown');
			}
		}
		
		var classname = $('#'+field+'_sort_btn').attr('class');
		var dir = (classname == 'sorting' || classname == 'headerSortDown') ? "ASC" : "DESC";
		
		$('#'+field+'_sort_btn').removeClass("sorting");
		$('#'+field+'_sort_btn').removeClass("headerSortUp");
		$('#'+field+'_sort_btn').removeClass("headerSortDown");
		$('#'+field+'_sort_btn').addClass((dir == "ASC") ? "headerSortUp" : "headerSortDown");
		
		/*var data = {
			sort : field,
			direction : dir,
			rand : new Date().getTime()
		};*/
		$.post(moduleURL+"&method=ajax_quizzes_table&sort="+field+"&direction="+dir+"&rand="+(new Date().getTime()), null, viewQuizzes.fillTable, "json");
		//$.get(moduleURL+"&method=ajax_quizzes_table", data, viewQuizzes.fillTable, "json");
	},
	
	duplicateQuiz : function(quiz_id) {
		
		$.fancybox.showActivity();
		$.post(moduleURL+"&method=ajax_duplicate_quiz&quiz_id="+quiz_id, null, viewQuizzes.changedQuizList, "json");
	},
	
	deleteQuiz : function(quiz_id) {
		
		$.fancybox.close();
		$.fancybox.showActivity();
		$.post(moduleURL+"&method=ajax_delete_quiz&quiz_id="+quiz_id, null, viewQuizzes.changedQuizList, "json");
	},
	
	changedQuizList : function(json) {
		
		var dir = "";
		var field = "";
		
		var buttons = ["quiz_id", "title"];
		for (var i = 0; i < buttons.length; i++) {
			
			if ($("#"+buttons[i]+"_sort_btn").hasClass("headerSortUp") ) {
				field = buttons[i];
				dir = "ASC";
				break;
			}
			else if ($("#"+buttons[i]+"_sort_btn").hasClass("headerSortDown") ) {
				field = buttons[i];
				dir = "DESC";
				break;
			}
		}
		
		var data = {
			sort : field,
			direction : dir,
			rand : new Date().getTime()
		};
		
		$.get(moduleURL+"&method=ajax_quizzes_table", data, viewQuizzes.fillTable, "json");
		
		eequiz.showPopup(json.success, json.message);
	}
}


var editQuiz = {
	
	confirmedRemove : false,
	
	filterQuestions : function() {
		
		var filter = $("#tags_filter").val();
		
		if (filter == "") {
		
			$("#unused_question_list li").show();
		}
		else {
			
			var filters = filter.split(" ");
			
			$("#unused_question_list li").hide();
			for (var i = 0; i < filters.length; i++) {
				$("#unused_question_list li."+filters[i]).show();
			}
		}
		
		var unusedHeight = $("ul#unused_question_list").css("height", "auto").height();
		var usedHeight = $("ul#question_list").css("height", "auto").height();
		var max = unusedHeight > usedHeight ? unusedHeight : usedHeight;
		$("ul#unused_question_list").height(max);
	},
	
	clearFilter : function() {
		
		$("#tags_filter").val("");
		editQuiz.filterQuestions();
	},
	
	confirmQuestionRemove : function() {
		
		editQuiz.confirmedRemove = true;
		editQuiz.submitQuizForm();
	},

	submitQuizForm : function() {
		
		var result = validateForm('quiz_form', 'invalid_input');
		if (!result) {
			eequiz.showPopup(false, "Error: There are problems with your values.");
			return false;
		}
		
		var selectedTemplate = $('select[name=quiz_template_id] option:selected').val();
		if (selectedTemplate == 0) {
			eequiz.showPopup(false, "Error: You must select a template.");
			return false;
		}
		
		// check to see if an original question is removed from this quiz, and then show warning if so
		if (!editQuiz.confirmedRemove) {
			var unused_items = $('#unused_question_list').sortable('toArray');
			for (var i = 0; i < unused_items.length; i++) {
				if (!/^quiz_question_\d+_0$/.test(unused_items[i])) {
					eequiz.showConfirmation("Warning: You are about to remove questions from this quiz. All user answers to the removed questions will be lost forever. Are you sure you wish to continue?", "editQuiz.confirmQuestionRemove()");
					return false;
				}
			}
		}
		
		var mappings = "";
		var items = $('#question_list').sortable('toArray');
		for (var i = 0; i < items.length; i++) mappings += items[i]+" ";
		mappings = $.trim(mappings);
		
		$('#quiz_form').append("<input type='hidden' id='quiz_questions_string' name='quiz_questions_string' value='"+mappings+"' />");
		
		$.post($('#quiz_form').attr('action'), getValues('quiz_form'), editQuiz.submitCallback, "json");
		$.fancybox.showActivity();
		
		return false;
	},
	
	submitCallback : function(json) {
		
		$.fancybox.hideActivity();
		
		if (json.success) {
			window.location.replace(moduleURL+"&method=view_quizzes&message=success");
			return;
		}
		
		eequiz.showPopup(json.success, json.message);

		// now that question is created/updated, update the form to contain the correct question_id
		var form = document.getElementById('quiz_form');
		form.quiz_id.value = json.quiz_id;
		
		// reset confirmation
		editQuiz.confirmedRemove = false;
		$("#quiz_questions_string").remove();
	}
}



var viewTemplates = {
	
	delete_message : "",
	
	fillTable : function(json) {
	
		if (json.num_rows > 0) $("#templates_tbody").html(json.html_string);
		else $("#templates_tbody").html("");
	},
	
	ajaxSort : function(field) {
		
		var buttons = ["quiz_template_id", "title"];
		for (var i = 0; i < buttons.length; i++) {
			if (buttons[i] != field) {
				$("#"+buttons[i]+"_sort_btn").addClass('sorting');
				$("#"+buttons[i]+"_sort_btn").removeClass('headerSortUp');
				$("#"+buttons[i]+"_sort_btn").removeClass('headerSortDown');
			}
		}
		
		var classname = $('#'+field+'_sort_btn').attr('class');
		var dir = (classname == 'sorting' || classname == 'headerSortDown') ? "ASC" : "DESC";
		
		$('#'+field+'_sort_btn').removeClass("sorting");
		$('#'+field+'_sort_btn').removeClass("headerSortUp");
		$('#'+field+'_sort_btn').removeClass("headerSortDown");
		$('#'+field+'_sort_btn').addClass((dir == "ASC") ? "headerSortUp" : "headerSortDown");
		
		var data = {
			sort : field,
			direction : dir,
			rand : new Date().getTime()
		};
		
		$.get(moduleURL+"&method=ajax_quiz_templates_table", data, viewTemplates.fillTable, "json");
	},
	
	deleteTemplate : function(quiz_template_id) {
		
		$.fancybox.close();
		$.fancybox.showActivity();
		$.post(moduleURL+"&method=ajax_delete_quiz_template&quiz_template_id="+quiz_template_id, null, viewTemplates.changedList, "json");
	},
	
	changedList : function(json) {
		
		var dir = "";
		var field = "";
		
		var buttons = ["quiz_template_id", "title"];
		for (var i = 0; i < buttons.length; i++) {
			
			if ($("#"+buttons[i]+"_sort_btn").hasClass("headerSortUp") ) {
				field = buttons[i];
				dir = "ASC";
				break;
			}
			else if ($("#"+buttons[i]+"_sort_btn").hasClass("headerSortDown") ) {
				field = buttons[i];
				dir = "DESC";
				break;
			}
		}
		
		var data = {
			sort : field,
			direction : dir,
			rand : new Date().getTime()
		};
		
		$.get(moduleURL+"&method=ajax_quiz_templates_table", data, viewTemplates.fillTable, "json");
		
		eequiz.showPopup(json.success, json.message);
	}
}




var editTemplate = {

	submit : function() {
	
		var result = validateForm('quiz_template_form', 'invalid_input');
		if (!result) {
			eequiz.showPopup(false, "Error: There are problems with your values.");
			return false;
		}
		
		$.post($('#quiz_template_form').attr('action'), getValues('quiz_template_form'), editTemplate.submitCallback, "json");
		$.fancybox.showActivity();
		
		return false;
	},
	
	submitCallback : function(json) {
	
		$.fancybox.hideActivity();
		
		if (json.success) {
			window.location.replace(moduleURL+"&method=view_quiz_templates&message=success");
			return;
		}
		
		eequiz.showPopup(json.success, json.message);

		// now that question is created/updated, update the form to contain the correct question_id
		var form = document.getElementById('quiz_template_form');
		form.quiz_template_id.value = json.quiz_template_id;
	}
}



var viewAnswers = {
	
	lastDeleteURL : "",
	
	submitFilter : function(form) {
		
		var subject = $('#filter_subject').val();
		
		var url = $(form).attr('action')+"&subject="+subject+"&query=";
		if (subject == "member_group") url += $('#filter_member_group').val();
		else url += $('#filter_query').val();
		
		setTimeout(function(){window.location.href = url;}, 0);
	},
	
	clearFilter : function(form) {
		
		setTimeout(function(){window.location.href = $(form).attr('action');}, 0);
	},
	
	changeSubject : function() {
	
		var subject = $('#filter_subject').val();
		if (subject == 'member_group') {
			$('#filter_query').css('display', 'none');
			$('#filter_member_group').css('display', '');
		}
		else {
			$('#filter_query').css('display', '');
			$('#filter_member_group').css('display', 'none');
		}
		$('#filter_query').val("");
	},
	
	deleteAnswers : function(quiz_id, member_id, mapping_id, anonymous) {
		
		var message = "You are about to delete all answers to this quiz question. Proceed?";
		if (member_id && mapping_id) message = "You are about to delete this user's answer to this question. Proceed?";
		else if (member_id && !mapping_id) message = "You are about to delete all of this user's answers to this quiz. Proceed?";
		
		viewAnswers.lastDeleteURL = moduleURL+"&method=ajax_delete_answers&quiz_id="+quiz_id+"&member_id="+member_id+"&mapping_id="+mapping_id+"&anonymous="+anonymous;
		
		eequiz.showConfirmation(message, "viewAnswers.deleteAnswersExecute();");
	},
	
	deleteAnswersExecute : function() {
	
			$.fancybox.close();
			$.fancybox.showActivity();
			$.post(viewAnswers.lastDeleteURL, null, viewAnswers.deleteAnswersCallback, "json");
	},
	
	deleteAnswersCallback : function(json) {
		
		window.location.reload(true);
	}
}
