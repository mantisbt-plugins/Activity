$(document).ready(function () {
    $('.activity-project-link').click(function () {
        document.getElementById('activity_project_id').value = $(this).attr('data-project');
        document.forms.activity_page_form.submit();
    });
});