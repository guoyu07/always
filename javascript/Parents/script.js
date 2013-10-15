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

        this.initNewClick();
        this.initEditClick();
        this.initDeleteClick();
    };

    this.initCreateProfileClick = function() {
        $('#create-new-profile').click(function() {
            window.location.href = 'always/admin/profile/new/?parent_id=' + $(this).data('parentId');
        });
    };

    this.initNewClick = function() {
        $('#new-parent').click(function() {
            $this.initDeleteClick();
            $('#delete-parent').hide();
            $this.parent_id = 0;
            $this.dialog_title = 'Create new parent account';
            $('#first_name').val('');
            $('#last_name').val('');
            $('#username').val('');
            $('#parent-id').val(0);
            $('.username-group').show();
            $('#profile-form').show();
            $('#profiles').hide();
            $('#parent-options').dialog({title: $this.dialog_title});
            $('#parent-options').dialog('open');
        });

    };

    this.initDeleteClick = function() {
        $('#delete-button').unbind();
        $('#delete-button .confirm').html('Delete parent');

        $('#delete-button').click(function() {
            $('.confirm', this).html('Click again to confirm deletion');
            $(document).click(function(event){
                if ($(event.target).closest('#delete-button').length == 0) {
                    $this.initDeleteClick();
                }
            });
            $('#delete-button').click(function() {
                $.get('always/admin/parents/', {
                    'command': 'delete_parent',
                    'parent_id': $('#parent-id').val()
                }, function() {
                    window.location.reload();
                }, 'json');
            });
        });
    };

    this.initEditClick = function() {
        $('.pager-row').click(function() {
            $this.initDeleteClick();
            $('#delete-parent').show();
            var row_id = $(this).data('rowId');
            $.get('always/admin/parents/', {
                'command': 'edit_parent',
                'pid': row_id
            }, function(data) {
                if (data.profile_list !== undefined) {
                    $('#profile-list').html(data.profile_list);
                }
                $this.dialog_title = 'Update parent account';
                $('#first_name').val(data['first_name']);
                $('#last_name').val(data['last_name']);
                $('.username-group').hide();
                $('#profile-form').hide();
                $('#profiles').show();
                $('#parent-id').val(row_id);
                $('#parent-options').dialog({title: $this.dialog_title});
                $('#parent-options').dialog('open');
            }, 'json');
        });
    };
}
;
