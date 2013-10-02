var parent = new parent;
$(window).load(function() {
    parent.init();
});

function parent() {
    this.parent_id = 0;

    this.init = function() {
        $this = this;

        if (this.parent_id) {
            var dialog_title = 'Update parent account';
        } else {
            var dialog_title = 'Create new parent account';
        }

        $('#parent-options').dialog({
            modal: true,
            autoOpen: false,
            width: 500,
            title: dialog_title
        });

        $('#new-parent').click(function() {
            //$this.parent_id = 0;
            $('#parent-options').dialog('open');

        });

    };

}
;
