/**
 * Admin scripts for SEO & Open Graph Manager
 */

(function($) {
	'use strict';
	
	$(document).ready(function() {
		// Image upload
		var mediaUploader;
		
		$('.seoog-upload-image').on('click', function(e) {
			e.preventDefault();
			
			var button = $(this);
			var inputField = button.prev('input');
			
			if (mediaUploader) {
				mediaUploader.open();
				return;
			}
			
			mediaUploader = wp.media({
				title: 'Select Image',
				button: {
					text: 'Use this image'
				},
				multiple: false
			});
			
			mediaUploader.on('select', function() {
				var attachment = mediaUploader.state().get('selection').first().toJSON();
				inputField.val(attachment.url);
			});
			
			mediaUploader.open();
		});
	});
})(jQuery);
