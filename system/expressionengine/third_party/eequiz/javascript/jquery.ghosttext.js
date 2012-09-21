jQuery.fn.ghostText = function(text){
    return this.each(function(){
		
		if (this.type != 'text' && this.type != 'textarea') return;
		
        if (this.value == '' || this.value == text) {
			this.value = text;
			$(this).addClass('ghosted');
		}
		else $(this).removeClass('ghosted');
		
		// focus on text field
		$(this).focus(function() {
			if (this.value == text) {
				this.value = '';
				$(this).removeClass('ghosted');
			}
		});
		
		// unfocus from text field
		$(this).blur(function() {
			if (this.value == '') {
				this.value = text;
				$(this).addClass('ghosted');
			}
		});
		
		// clear form default values on submit
		/*var currentField = this;
		$(this).parents("form").each(function() {
			$(this).submit(function() {
				if (currentField.value == text) currentField.value = '';
			});
		});*/
    });
};

jQuery.fn.clearGhostText = function(){
    return this.each(function(){
		if ($(this).hasClass('ghosted')) {
			$(this).unbind('focus');
			$(this).unbind('blur');
			this.value = '';
			$(this).removeClass('ghosted');
		}
	});
};