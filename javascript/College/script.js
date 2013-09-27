var college = new College;
$(window).load(function() {
    college.init();
    Pagers.options({callback: college.initializeRowClick});
});

function College() {
    this.college_id = 0;

    this.init = function() {
        $this = this;
        $('#college-options').dialog({
            modal: true,
            autoOpen: false,
            width: 500
        });

        $('#new-college').click(function() {
            $this.college_id = 0;
            $this.popup();
        });

        $('#college-options form').submit(function() {
            var college_name = $('input#college-name').val();
            if (college_name.length === 0) {
                return false;
            }
        });

        this.deleteApproval();
        $('#delete-approval').change(function() {
            $this.deleteApproval();
        });

        this.initializeRowClick();
    };

    this.deleteApproval = function() {
        if ($('#delete-approval').is(':checked')) {
            $('#delete-button').removeAttr('disabled');
        } else {
            $('#delete-button').attr('disabled', 'disabled');
        }
    }

    this.initializeRowClick = function()
    {
        $this = this;
        $('.pager-row').click(function() {
            $this.college_id = $(this).data('rowId');
            $('#college-name').val($('.name', this).html());
            college.popup();
        });
    };

    this.popup = function() {
        $this = this;
        $('#delete-approval').attr('checked', false);
        $('#delete-button').attr('disabled', 'disabled');

        $.get('resumedrop/admin/colleges/?command=counselors', {'college_id': this.college_id},
        function(data) {
            $('#counselor-select').html(data.counselors);
            $this.initSelect();
        }, 'json');

        $('.college-id').val(this.college_id);
        var college_title = 'College options';
        if (this.college_id > 0) {
            $('#save-button').val('Update name');
            $('#other-options').show();
        } else {
            $('#save-button').val('Create college');
            $('#other-options').hide();
        }
        $('#college-options').dialog({title: college_title});
        $('#college-options').dialog('open');
    };

    this.initSelect = function() {
        $('#counselor-select').select2({
            placeholder: 'Click to pick counselors',
            width: 'copy'
        });
    };

    this.delete = function() {

    };
}
;