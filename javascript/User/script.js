$(window).load(function() {
    $('#success').dialog(
            {
                autoOpen: false,
                modal: true,
                width: 500,
                title: 'Thank you',
                buttons: [{text: "Ok", click: function() {
                            $(this).dialog("close");
                            location.href = location.href.replace(/resumedrop\/?.*/, '');
                        }}]
            });
    $('#failure').dialog(
            {
                autoOpen: false,
                modal: true,
                width: 500,
                title: 'Sorry',
                buttons: [{text: "Ok", click: function() {
                            $(this).dialog("close");
                        }}]
            });
});

$(function() {
    'use strict';
    // Change this to the location of your server-side upload handler:
    var url = 'resumedrop/upload/';
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
    }).prop('disabled', !$.support.fileInput)
            .parent().addClass($.support.fileInput ? undefined : 'disabled');
});