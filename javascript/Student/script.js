var student = new student;
$(window).load(function() {
    student.init();
    Pagers.options({callback: student.initializeRowClick});
});

function student() {
    this.student_id = 0;

    this.init = function() {
        $this = this;
        $('#student-options').dialog({
            modal: true,
            autoOpen: false,
            width: 500
        });

        $('#new-student').click(function() {
            $this.student_id = 0;
            $this.popup();
        });

        $('#student-options form').submit(function() {
            var student_name = $('input#student-name').val();
            if (student_name.length === 0) {
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
            $this.student_id = $(this).data('rowId');
            $('#student-name').val($('.name', this).html());
            student.test();
        });
    };

    this.test = function() {
        $this = this;
        $.get('always/admin/students/?command=student', {'student_id': this.student_id},
        console.debug($this);
    };

    this.popup = function() {
        $this = this;
        $('#delete-approval').attr('checked', false);
        $('#delete-button').attr('disabled', 'disabled');


        $.get('always/admin/students/?command=student', {'student_id': this.student_id},
        function(data) {
            $('#counselor-select').html(data.counselors);
            $this.initSelect();
        }, 'json');

        $('.student-id').val(this.student_id);
        var student_title = "Student";
        if (this.student_id > 0) {
            $('#save-button').val('');
            $('#other-options').show();
        } else {
            $('#save-button').val('Create student');
            $('#other-options').hide();
        }
        $('#student-options').dialog({title: student_title});
        $('#student-options').dialog('open');
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
