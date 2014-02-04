$(function() {
    'use strict';
    var root_dir = 'images/always/mcnaneym-at-appstate-edu/';
/*
    $.get('index.php', {
        module: 'always',
        command: 'profile_images',
        profile_id : pid
    });
*/
    var gallery = blueimp.Gallery(
            {
                container: '#blueimp-image-carousel',
                carousel: true
            }
    );
});