/**
 * @detail
 * Additional function to handle content
 * http://zourbuth.com/
 */

(function($) { BeeDialog = {

	init : function(){
		$('.bee-dialog').closest(".widget-inside").addClass("total-widget");		
		$('.bee-dialog').closest(".inside").addClass("bee-wrapper");
		
		$('ul.nav-tabs li').live( "click", function(){
			BeeDialog.tabs(this)
		});
		
		$('.bee-dialog').on( "click", "a.add-image", function(e){
			e.preventDefault(); BeeDialog.addImage(this);
		});
		
		$('.bee-dialog').on( "click", "a.add-thumbnail", function(e){
			e.preventDefault(); BeeDialog.addThumbnail(this);
		});
		
		$('.bee-dialog').on( "click", "a.remove-image", function(e){
			e.preventDefault(); BeeDialog.removeImage(this);
		});
	
	},
	
	tabs : function(tab){
		var t, i, c;
		
		t = $(tab);
		i = t.index();
		c = t.closest("div.bee-dialog").find("ul.tab-content").children("li").eq(i);
		t.addClass('active').siblings("li").removeClass('active');
		$(c).show().addClass('active').siblings().hide().removeClass('active');
		t.closest("ul").next().val(i);
	},
	
	addImage : function(el){
		var $el = $(el), frame, attachment, img, input, removebtn;		
	
		img = $el.siblings('img');
		input = $el.siblings('input');
		removebtn = $el.siblings('a');
	
		// If the media frame already exists, reopen it.
		if ( frame ) {
			frame.open();
			return;
		}

		// Create the media frame.
		frame = wp.media({
			// Set the title of the modal.
			title: $el.data('choose'),

			// Tell the modal to show only images.
			library: {
				type: 'image'
			},

			// Customize the submit button.
			button: {
				// Set the text of the button.
				text: $el.data('update'),
				// Tell the button not to close the modal, since we're
				// going to refresh the page when the image is selected.
				close: false
			}
		});

		// When an image is selected, run a callback.
		frame.on( 'select', function() {
			// Grab the selected attachment.
			attachment = frame.state().get('selection').first();		
			input.val(attachment.attributes.url);
			img.attr('src', attachment.attributes.url).slideDown();
			removebtn.removeClass("hidden");
			frame.close();			
		});

		// Finally, open the modal.
		frame.open();
		return false;
	},
	
	addThumbnail : function(el){
		var $el = $(el), frame, attachment, img, input, removebtn;		
	
		img = $el.siblings('img');
		input = $el.siblings('input');
		removebtn = $el.siblings('a');
	
		// If the media frame already exists, reopen it.
		if ( frame ) {
			frame.open();
			return;
		}

		// Create the media frame.
		frame = wp.media({
			// Set the title of the modal.
			title: $el.data('choose'),

			// Tell the modal to show only images.
			library: {
				type: 'image'
			},

			// Customize the submit button.
			button: {
				// Set the text of the button.
				text: $el.data('update'),
				// Tell the button not to close the modal, since we're
				// going to refresh the page when the image is selected.
				close: false
			}
		});

		// When an image is selected, run a callback.
		frame.on( 'select', function() {
			// Grab the selected attachment.
			attachment = frame.state().get('selection').first();			
			input.val(attachment.id);
			img.attr('src', attachment.attributes.url).slideDown();
			removebtn.removeClass("hidden");
			frame.close();
		});

		// Finally, open the modal.
		frame.open();
		return false;
	},	
	
	removeImage : function(el){
		var t = $(el);
		
		t.next().val('');
		t.siblings('img').slideUp();
		t.removeClass('show-remove').addClass('hide-remove');
		t.fadeOut();
		return false;
	}	
};

$(document).ready(function(){BeeDialog.init();});
})(jQuery);
