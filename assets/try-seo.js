jQuery(document).ready(function() {
	
	if(jQuery('#try-seo.postbox').length > 0){
		
		// Title vars and watcher
		var title = jQuery('#try-seo #seo_title');
		var title_counter = jQuery('#try-seo #character_count_title');
		var title_total = 60;
		var title_current = jQuery('#post-body #title');
		
		seo_get_count(title, title_counter, title_total);
		seo_get_placeholder(title_current, title);
		
		title.on('change keyup', function(){ 
			seo_get_count(title, title_counter, title_total); 
		});
		
		title.on('blur', function(){
			var id = jQuery(this).data('id');
			if(title.val().length > 0){
				get_identical_meta('seo_title', title.val(), 'post_title', id);
			} else {
				get_identical_meta('seo_title', title_current.val(), 'post_title', id);
			}
		});
		get_identical_meta('seo_title', title.val(), 'post_title', title.data('id'));
		
		// Description vars and watcher
		var description = jQuery('#try-seo #seo_description');
		var description_counter = jQuery('#try-seo #character_count_desc');
		var description_total = 300;
		var description_current = jQuery('#post-body #content');
		
		seo_get_count(description, description_counter, description_total);
		
		jQuery(window).load( function() {
			seo_get_placeholder(description_current, description, true, description_total);
		});
		
		description.on('change keyup', function(){ 
			seo_get_count(description, description_counter, description_total); 
		});	
		description.on('blur', function(){
			var id = jQuery(this).data('id');
			if(description.val().length > 0){
				get_identical_meta('seo_description', description.val(), false, id);
			} else {
				get_identical_meta('seo_description', description_current.val(), false, id);
			}
		});
		get_identical_meta('seo_description', description.val(), false, description.data('id'));
		
		
		// Encapsulate functions inside this anonymous scope
		function get_identical_meta(key, value, fallback, id){
			if(typeof fallback === 'undefined'){ fallback = false; };
			if(typeof id === 'undefined'){ id = false; };
			
			jQuery.ajax({
				type : 'POST',
				url : ajaxurl,
				data : {
					action : 'get_identical_meta', 
					key : key,
					value : value,
					post_field : fallback,
					post_id : id
				},
				success: function(response) {
					jQuery('#'+key).parent().find('.seo-error').remove();
					jQuery('#'+key).after('<span class="seo-error"></span>');
					jQuery('#'+key).parent().find('.seo-error').html(response);
				}
			})  
		}
		
		function seo_get_count(element, counter, total) {
			
			var count = parseFloat(counter.find('.count').text(), 10);
			
			var length_near = Math.floor(total*0.85);
			var length_over = total;
			
			var length_current = element.val().length;
			var length_remaining = total-length_current;
			
			counter.find('.count').text(length_remaining);
			
			if (length_current > 0 && length_current <= length_near){
				counter.addClass('good').removeClass('near over');
			} else if (length_current > length_near && length_current <= length_over) {
				counter.addClass('near').removeClass('good over');
			} else if (length_current > length_over) {
				counter.addClass('over').removeClass('near good');
			} else {
				counter.removeClass('near good over');
			}
				
		};
		
		function seo_get_placeholder(element, placeholder, wysiwyg, limit){
			
			if(typeof wysiwyg === 'undefined') { var wysiwyg = false; }
			if(typeof limit === 'undefined') { var limit = 0; }
			
			var current_placeholder = placeholder.attr('placeholder');
			
			if(wysiwyg !== false){
				
				var editor_content = tinymce.get('content');
				
				if(editor_content !== null){
					set_wysiwyg_event(editor_content, placeholder, limit);
		        } else {
			        var checkForContent = setInterval(function(){
			        	var editor_content = tinymce.get('content');
			        	if(editor_content !== null){
			        		clearInterval(checkForContent);
			        		set_wysiwyg_event(editor_content, placeholder, limit);
			        	}
			        }, 1000);
		        }
				
			}

			var current_text = element.val();
			
			element.on('change keyup blur', function() {
				
				var new_text = jQuery(this).val();
				var has_text = placeholder.val().length ? true : false;
				
				if(!has_text) {
					if(current_text.length === 0){
						var new_placeholder = new_text + current_placeholder;
					} else {
						var other_length = current_placeholder.replace(current_text, '').length;
						if(limit > 0){
							if((new_text.length + other_length + 3) > limit){
								new_text = truncate_to_length(new_text, limit-(other_length+3)) + '...';
							}
						}
						var new_placeholder = current_placeholder.replace(current_text, new_text);
					}
					
					placeholder.attr('placeholder', strip_html(new_placeholder));
				}
			});
			
		};
		
		function set_wysiwyg_event(editor_content, placeholder, limit){
			editor_content.on('keyup',function(e){
				var content = strip_html(this.getContent());
				if(limit > 0){
					if((content.length + 3) > limit){
						content = truncate_to_length(content, limit-3) + '...';
					}
				}
				placeholder.attr('placeholder', content );
	        });
		}
		
		function strip_html(html) {
			var tmp = document.createElement('div');
			tmp.innerHTML = html;
			return tmp.textContent || tmp.innerText || '';
		}
		
		function truncate_to_length(string, length){
			var truncated = string.substr(0, length);
			truncated = truncated.substr(0, Math.min(truncated.length, truncated.lastIndexOf(' ')));
			return truncated;
		}
	};
	
});
