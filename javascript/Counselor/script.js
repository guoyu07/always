var counselor = new Counselor;

$(window).load(function() {
    counselor.initDialog();
    counselor.initialize();
});

function Counselor() {
    this.initDialog = function() {
        $('#assign-form').dialog({
            autoOpen: false,
            title: 'Assign counselor',
            width: 500,
            height: 150
        });
    };

    this.initialize = function() {
        $this = this;
        $('#user-id').select2({
            width: 'copy'
        });

        $('#assign-counselor').click(function() {
            $('#assign-form').dialog('open');
        });

        $('.delete-counselor').click(function() {
            var row = $(this).parents('.pager-row')[0];
            var counselor_id = $(row).data('rowId');
            $.get('resumedrop/admin/counselors',
                    {
                        'command': 'delete_counselor',
                        'counselor_id': counselor_id
                    }, function(data) {
                if (data.counselors) {
                    $('#user-id').html('');
                    for (var id in data.counselors) {
                        $('#user-id').append('<option value="' + id + '">' + data.counselors[id] + '</option>');
                    }
                }
                Pagers.reload('counselor-list');
                $this.initialize();
            },'json');
        });
    };

}