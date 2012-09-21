/*
FormValidation (1.0)
Author: Miles Jackson
Utility used with forms.

Rules:
	Check out inside the validateSingle function

Functions:

getValues(obj)
	This will get all the input values that are descendants of the given object, and will encapsulate them in
	an object. This object can be used with most framework Ajax functions (ex: jQuery.post(url, getValues(form), callback))
	
getSelectValue(select)
	This will return the value of the given select box. It is compatable with IE6+, and takes into account if value is defined or not.
	
validateSingle(obj, invalidStyle)
	This will validate a given input based on the rule defined in its class. It will either add or remove the given
	invalidStyle, depending on whether or not it is valid. It will return true or false.

validateForm(form, invalidStyle)
	This will go through all inputs in the form and validate them based on the rule defined in each input's class. It
	will either add or remove the invalidStyle, depending on the inputs' validity. It will return true or false.
*/


function getValues(obj) {
	
	if (typeof(obj) == 'string') obj = document.getElementById(obj);
	
	var returnObject = {};
		
	var inputs = obj.getElementsByTagName("input");
	for (var i = 0; i < inputs.length; i++) {
		
		if (inputs[i].name == "") continue;
		
		var child = inputs[i];
		var type = child.getAttribute("type").toLowerCase();
		
		if (type == "text" || type == "hidden" || type == "password") returnObject[child.name] = child.value;
			
		if (type == "checkbox") {
			if (/\[\]$/.test(child.name)) {
				var test = typeof returnObject[child.name];
				if (test == "null" || test == "undefined") returnObject[child.name] = [];
				if (child.checked) returnObject[child.name].push(child.value);
			}
			else {
				if (child.checked) returnObject[child.name] = child.value;
				else returnObject[child.name] = "";
			}
		}
		
		if (type == "radio") if (child.checked) returnObject[child.name] = child.value;
	}

	inputs = obj.getElementsByTagName("select");
	for (var i = 0; i < inputs.length; i++) {
		if (inputs[i].name == "") continue;
		if (inputs[i].selectedIndex != '-1') {
			returnObject[inputs[i].name] = getSelectValue(inputs[i]);//inputs[i].options[inputs[i].selectedIndex].value;
		}
	}

	inputs = obj.getElementsByTagName("textarea");
	for (var i = 0; i < inputs.length; i++) {
		if (inputs[i].name == "") continue;
		returnObject[inputs[i].name] = inputs[i].value;
	}
	
	return returnObject;
}


function validateSingle(obj, invalidStyle) {
	
	if (typeof(obj) == 'string') obj = document.getElementById(obj);
	
	var rules = [
		/./,
		/^\d+$/,
		/(^\d+\.{1}\d*$)|(^\d+$)|(^\.\d+$)/,
		/^\w+$/,
		/^[\w\-]+$/,
		
		/^[\w \.\-]+$/,
		
		/^\d{4}$/,
		/^\d{2}$/,
		/(^0?[1-9]$)|(^1[012]$)/,
		/(^0?[1-9]$)|(^[12]\d$)|(^3[01]$)/,
		
		/^[0-9\-\.\(\) extnsio]{7,25}$/,
		/^\d{3}\-\d{3}\-\d{4}$/,
		/^\d{3}\-\d{3}\-\d{4}([ x]{1,2}\d{1,5})?$/,
		/^\d{3}\-\d{3}\-\d{4}.{0,10}$/,
		
		/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}$/
	];
		
	var classes = [
		"v_required",
		"v_positive_integer",
		"v_positive_decimal",
		"v_word_characters",
		"v_word_characters_hyphen",
		
		"v_full_name",
		
		"v_long_year",
		"v_short_year",
		"v_month",
		"v_day",
		
		"v_phone_flexible", //(.- 0123456789
		"v_phone_1", //###-###-####
		"v_phone_2", //###-###-#### followed by 2 space or x, followed by 1-4 digits
		"v_phone_3", //###-###-#### followed by anything up to 10 characters
		
		"v_email"
	];
	
	for (var i = 0; i < rules.length; i++) {
		if (_FormValidationUtil.hasClass(obj, classes[i])) {
		
			var val = obj.value;
			if (obj.tagName.toLowerCase() == "select") val = getSelectValue(obj);
			
			if (!rules[i].test(val)) {
				if (!(_FormValidationUtil.hasClass(obj, "v_optional") && val == "")) {
					_FormValidationUtil.addClass(obj, invalidStyle);
					return false;
				}
			}
		}
	}
	
	_FormValidationUtil.removeClass(obj, invalidStyle);
	return true;
}


function validateForm(form, invalidStyle) {
	
	if (typeof(form) == 'string') form = document.getElementById(form);
	
	var result = true;
		
	// Supports matching 0-9
	var matching = [[], [], [], [], [], [], [], [], [], []];
	var elements = [[], [], [], [], [], [], [], [], [], []];
	
	for (var i = 0; i < form.elements.length; i++) {
	
		var element = form.elements[i];
		
		if (!validateSingle(element, invalidStyle)) result = false;
		
		var matches = /v_match(\d+?)/.exec(element.className);
		if (matches)  {
			if (matches[1]*1 >= 0 && matches[1]*1 <= 9) {
				matching[ matches[1]*1 ].push(element.value);
				elements[ matches[1]*1 ].push(element);
			}
		}
	}
	
	//check matching
	for (var i = 0; i < 10; i++) {
		
		if (matching[i].length == 0) continue;
		
		var matchingResult = true;
		
		for (var j = 1; j < matching[i].length; j++) {
			if (matching[i][j] != matching[i][0]) {
				matchingResult = result = false;
			}
		}
		
		if (!matchingResult) {
			for (var j = 0; j < matching[i].length; j++) {
				_FormValidationUtil.addClass(elements[i][j], invalidStyle);
			}
		}
	}	
	
	return result;
}


function getSelectValue(select) {
	
	if (typeof(select) == 'string') select = document.getElementById(select);
	
	// IE6/7 workaround to see if value is defined

	// get option outerHTML
	var temp = select.options[select.selectedIndex].cloneNode(false);
	var temp2 = document.createElement("div");
	temp2.appendChild(temp);
	var outer = temp2.innerHTML;
	
	// if value attribute defined, use it, otherwise use innerHTML
	if (/\s+value\s*=/.test(outer)) return select.options[select.selectedIndex].value;
	else return select.options[select.selectedIndex].text;
}


var _FormValidationUtil = {
	hasClass : function(obj, testClass) {
		var re = new RegExp("(^| )"+testClass+"( |$)");  
		return re.test(obj.className);
	},
	addClass : function(obj, theClass) {
		if (!_FormValidationUtil.hasClass(obj, theClass)) {
			if (obj.className == "") obj.className = theClass;
			else obj.className += " "+theClass;
		}
	},
	removeClass : function(obj, theClass) {
		var pattern = new RegExp("(^| )"+theClass+"( |$)");
		obj.className = obj.className.replace(pattern, "$1");
		obj.className = obj.className.replace(/ $/, "");
		obj.className = obj.className.replace(/^ /, "");
	}
};