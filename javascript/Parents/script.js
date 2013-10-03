var parent = new parent;
$(window).load(function() {
    parent.init();
});

function parent() {
    this.parent_id = 0;
    this.display_title = 'Create new parent account';

    this.init = function() {
        $this = this;

        $('#parent-options').dialog({
            modal: true,
            autoOpen: false,
            width: 500
        });

        $('#new-parent').click(function() {
            $this.parent_id = 0;
            $this.dialog_title = 'Create new parent account';
            $('.username-group').show();
            $('#parent-options').dialog({title: $this.dialog_title});
            $('#parent-options').dialog('open');
        });

        $('.pager-row').click(function() {
            var row_id = $(this).data('rowId');
            $.get('always/admin/parents/', {
                'command': 'edit_parent',
                'pid': row_id
            }, function(data) {
                $this.dialog_title = 'Update parent account';
                $('#first_name').val(data['first_name']);
                $('#last_name').val(data['last_name']);
                $('.username-group').hide();
                $('#parent-id').val(row_id);
                $('#parent-options').dialog({title: $this.dialog_title});
                $('#parent-options').dialog('open');
            }, 'json');
        });
    };
}
;
