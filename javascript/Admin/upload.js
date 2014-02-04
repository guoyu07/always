$(function() {
    'use strict';
    // Change this to the location of your server-side upload handler:
    var url = 'always/admin/gallery/upload';
    $('#fileupload').fileupload({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url: url
    });

    // Enable iframe cross-domain access via redirect option:
    $('#fileupload').fileupload(
            'option',
            'redirect',
            window.location.href.replace(
                    /\/[^\/]*$/,
                    '/cors/result.html?%s'
                    )
            );

    /*
     $('#fileupload').fileupload({
     url: url,
     dataType: 'json',
     done: function(e, data) {
     if (data._response.result.files[0].error !== undefined) {
     $('#failure').dialog('open');
     } else {
     $('#success').dialog('open');
     }
     },
     progressall: function(e, data) {
     var progress = parseInt(data.loaded / data.total * 100, 10);
     $('#progress .bar').css(
     'width',
     progress + '%'
     );
     }
     }).prop('disabled', !$.support.fileInput).parent().addClass($.support.fileInput ? undefined : 'disabled');
     */

    // Load existing files:
    $('#fileupload').addClass('fileupload-processing');
    $.ajax({
        // Uncomment the following to send cross-domain cookies:
        //xhrFields: {withCredentials: true},
        url: $('#fileupload').fileupload('option', 'url'),
        dataType: 'json',
        context: $('#fileupload')[0]
    }).always(function() {
        $(this).removeClass('fileupload-processing');
    }).done(function(result) {
        $(this).fileupload('option', 'done')
                .call(this, $.Event('done'), {result: result});
    });
});