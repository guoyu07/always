/*
 * jQuery File Upload Plugin JS Example 8.9.1
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/* global $, window */

$(function() {
    'use strict';

    // Initialize the jQuery File Upload widget:
    $('#fileupload').fileupload({
        url: upload_url,
        redirect: window.location.href.replace(
                /\/[^\/]*$/,
                '/cors/result.html?%s'
                ),
        acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,
        disableImageResize: /Android(?!.*Chrome)|Opera/
                .test(window.navigator.userAgent),
        previewMaxWidth: 80,
        previewMaxHeight: 80,
        previewCrop: true
    }).on('fileuploadprogressall', function(e, data) {
        showError(data);
        var progress = parseInt(data.loaded / data.total * 100, 10);
        $('.progress-bar').css(
                'width',
                progress + '%'
                );
    }).on('fileuploadfinished', function(e, data) {
        showError(data);
        initCaption();
        initDefault();
    });

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
        showError(result);
        $(this).fileupload('option', 'done')
                .call(this, $.Event('done'), {result: result});
    });

    function initDefault() {
        $('.profile-button').click(function() {
            $.get(command_dir + 'gallery/pickdefault', {
                image_id: $(this).data('imageId')
            }, function(data) {
                //console.log(data);
            }).always(function(){
                window.location.reload();
            });
            return false;
        });
    }

    function initCaption() {

        $('.caption').focusout(function() {
            var caption = $(this).val();
            var profile_id = $(this).data('profileId');
            var image_id = $(this).data('imageId');
            $.post(command_dir + 'gallery/caption', {
                caption: caption,
                profile_id: profile_id,
                image_id: image_id
            }, function(data) {
                //console.log(data);
            });
        });
    }
});

function showError(data) {
    var message = '';

    if (data.error !== undefined) {
        if (data.error.exception !== undefined) {
            if (showExceptions) {
                message = data.error.exception.xdebug_message;
                $('body').html('<table>' + message + '</table>');
                return;
            } else {
                message = 'An error occurred. Cannot continue.';
            }
        } else {
            message = data.error;
        }
        $('.form-error p').html(message);
        $('.form-error').show();
    }
}