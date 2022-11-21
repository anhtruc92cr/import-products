(function ($) {
    'use strict';

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */

    $(document).ready(function () {
        $('.btn-import').on('click', function (e) {
            if (ajaxurl) {
                let $this = $(this),
                    $type = $this.val(),
                    $target = $('.btn-wrapper'),
                    $btn = $('.btn-import'),
                    $loading = '<img class="loading-icon" src="/wp-includes/js/tinymce/skins/lightgray/img/loader.gif" alt="loading" />';
                $btn.addClass('disable');
                $btn.prop('disabled', true);
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'import_products',
                        import_type: $type ? $type : 0
                    },
                    beforeSend: function () {
                        $target.append($loading);
                    },
                    success: function (res) {
                        $target.find('.loading-icon').remove();
                        $btn.prop('disabled', false);
                    }
                });
            }
        })
    });
})(jQuery);
