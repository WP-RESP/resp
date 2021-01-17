jQuery(document).ready(function ($) {
    "use strict";

    $('.customize-control-tinymce-editor').each(function () {

        const control = _wpCustomizeSettings.controls[$(this).attr('id')];

        wp.editor.initialize($(this).attr('id'), {
            tinymce: {
                wpautop: true,
                toolbar1: control.tinymce_toolbar1,
                toolbar2: control.tinymce_toolbar2
            },
            quicktags: true,
            mediaButtons: control.tinymce_mediabuttons
        });
    });
    $(document).on('tinymce-editor-init', function (event, editor) {
        editor.on('change', function (e) {
            tinyMCE.triggerSave();
            $('#' + editor.id).trigger('change');
        });
    });

});