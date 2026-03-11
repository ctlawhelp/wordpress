// NSMI Icons JavaScript
(function($) {
    'use strict';

    console.log('NSMI Icons: Script loaded');

    function initIconUpload() {
        var $wrapper = $('.nsmi-icon-wrapper');
        var $input = $('#nsmi_icon_id');
        var $preview = $('.nsmi-icon-preview');
        var $uploadBtn = $('.nsmi-icon-upload');
        var $removeBtn = $('.nsmi-icon-remove');

        // Upload button click
        $uploadBtn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            console.log('NSMI Icons: Upload button clicked');

            if (typeof wp.media === 'undefined') {
                alert('WordPress Media Library is not available. Please refresh the page.');
                return false;
            }

            var frame = wp.media({
                title: 'Select NSMI Category Icon',
                button: { text: 'Use this icon' },
                library: { type: ['image', 'image/svg+xml'] },
                multiple: false
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                console.log('NSMI Icons: Selected attachment:', attachment);

                $input.val(attachment.id);
                $preview.html('<img src="' + attachment.url + '" style="max-width:80px;height:auto;" />');
                $removeBtn.prop('disabled', false);
            });

            frame.open();
            return false;
        });

        // Remove button click
        $removeBtn.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            console.log('NSMI Icons: Remove button clicked');

            $input.val('');
            $preview.empty();
            $removeBtn.prop('disabled', true);
            return false;
        });
    }

    // Initialize when document is ready
    $(document).ready(function() {
        console.log('NSMI Icons: Document ready, initializing icon upload');
        if ($('.nsmi-icon-wrapper').length > 0) {
            initIconUpload();
        }
    });

})(jQuery);