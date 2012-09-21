/*
 * jQuery LightSwitch plugin
 * @author admin@catchmyfame.com - http://www.catchmyfame.com
 * @edited Miles Jackson - 8/4/2010
 *		+ made default options configurable
 *		+ changed lightSwitch declaration
 *		+ fixed the duplication issue when you re-lightSwitch stuff
 * @version 1.0.0
 * @date May 6, 2010
 * @category jQuery plugin
 * @copyright (c) 2010 admin@catchmyfame.com (www.catchmyfame.com)
 * @license CC Attribution-Share Alike 3.0 - http://creativecommons.org/licenses/by-sa/3.0/
 */
(function($){

	$.fn.lightSwitch = function(options) {
	
		var options = $.extend({}, $.fn.lightSwitch.defaults, options);
		options.switchImg = options.imageDir + options['switchImg'];
		options.switchImgCover = options.imageDir + options['switchImgCover'];
		options.disabledImg = options.imageDir + options['disabledImg'];
		
		return this.each(function() {
			var o=options;
			var obj = $(this);
			
			$('+ span.lightSwitchContainer', this).remove();

			if($(this).attr('disabled'))
			{
				$(this).css({'display':'none'}).after('<span class="lightSwitchContainer"><img src="'+o.disabledImg+'" /></span>');
			}
			else
			{			
				$(this).css({'display':'none'}).after('<span class="lightSwitchContainer switch"><img src="'+o.switchImgCover+'" width="'+o.switchImgCoverWidth+'" height="'+o.switchImgCoverHeight+'" /></span>'); //'display':'none'
			}
			$('+ span.switch', this).css({'display':'inline-block','background-image':'url("'+o.switchImg+'")','background-repeat':'no-repeat','overflow':'hidden','cursor':'pointer','margin-right':'2px'});
			$('+ span.switch', this).click(function(){
				// When we click any span image for a radio button, animate the previously selected radio button to 'off'. 
				if($(this).prev().is(':radio'))
				{
					radioGroupName = $(this).prev().attr('name');
					$('input[name="'+radioGroupName+'"]'+':checked + span').css({'background-position':o.offShift});
					//$('input[name="'+radioGroupName+'"]'+':checked + span').stop().animate({'background-position':o.offShift},o.animSpeed);
				}
				if($(this).prev().is(':checked'))
				{
					$(this).css({'background-position':o.offShift}); // off
					//$(this).stop().animate({'background-position':o.offShift},o.animSpeed); // off
					$(this).prev().removeAttr('checked');
				}
				else
				{
					$(this).css({'background-position':o.onShift}); // on
					//$(this).stop().animate({'background-position':o.onShift},o.animSpeed); // on
					if($(this).prev().is(':radio')) $('input[name="'+radioGroupName+'"]'+':checked').removeAttr('checked');
					$(this).prev('input').attr('checked','checked');
				}
				
				obj.trigger('change'); // MILES EDIT
			}).hover(function(){
					$(this).css({'background-position': $(this).prev().is(':checked') ? o.peekOff : o.peekOn});
					//$(this).stop().animate({'background-position': $(this).prev().is(':checked') ? o.peekOff : o.peekOn},o.hoverSpeed);
				},function(){
					$(this).css({'background-position': $(this).prev().is(':checked') ? o.onShift :o.offShift});
					//$(this).stop().animate({'background-position': $(this).prev().is(':checked') ? o.onShift :o.offShift},o.hoverSpeed);
			});
			$('+ span', this).css({'background-position': $(this).is(':checked') ? o.onShift : o.offShift }); // setup default states

			$('input + span').live("click", function() { return false; });

			$(this).change(function(){
			
				radioGroupName = $(this).attr('name');
				if($(this).is(':radio'))
				{
					$(this).css({'background-position':o.onShift});
					//$(this).stop().animate({'background-position':o.onShift},o.animSpeed);
					$('input[name="'+radioGroupName+'"]'+' + span').stop().animate({'background-position':o.offShift},o.animSpeed);
				}
				$(this).css({'background-position': $(this).is(':checked') ? o.onShift :o.offShift});
				//$(this).next('span').stop().animate({'background-position': $(this).is(':checked') ? o.onShift :o.offShift},o.animSpeed);
			});
		});
	};
	
	$.fn.lightSwitch.defaults = {
		animSpeed : 120,
		hoverSpeed : 100,
		imageDir : "",
		switchImg : 'switch.png',
		switchImgCover: 'switchplate.png',
		switchImgCoverWidth : '63px',
		switchImgCoverHeight : '18px',
		disabledImg : 'disabled.png',
		onShift : '0px 0px',
		offShift : '-37px 0px',
		peekOff : '-6px 0px',
		peekOn : '-31px 0px'
	};
	
})(jQuery);