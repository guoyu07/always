var student = new student;
$(window).load(function() {
    student.init();
});

function student() {
    this.student_id = 0;

    this.init = function() {
        $this = this;

        if (this.student_id) {
            var dialog_title = 'Update student account';
        } else {
            var dialog_title = 'Create new student account';
        }

        $('#student-options').dialog({
            modal: true,
            autoOpen: false,
            width: 500,
            title: dialog_title
        });

        $('#new-student').click(function() {
            //$this.student_id = 0;
            $('#student-options').dialog('open');

        });

    };

}
;
