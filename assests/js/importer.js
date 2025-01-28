jQuery(document).ready(function ($) {
    $('#csv-import-form').on('submit', function (e) {
        e.preventDefault();

        var fileInput = $('#csv-file')[0];
        if (!fileInput.files.length) {
            alert('Please select a file to upload.');
            return;
        }

        var formData = new FormData();
        formData.append('csv_file', fileInput.files[0]);
        formData.append('action', 'import_csv');
        formData.append('nonce', importerAjax.nonce);

        $('#progress-container').show();
        $('#progress-bar').css('width', '0%').text('0%');
        $('#import-log').html('');

        $.ajax({
            url: importerAjax.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            xhr: function () {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        var percentComplete = Math.round((e.loaded / e.total) * 100);
                        $('#progress-bar').css('width', percentComplete + '%').text(percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            success: function (response) {
                if (response.success) {
                    $('#import-log').html(
                        '<p>Successfully updated ' + response.data.success_count + ' entries.</p>'
                    );
                    if (response.data.errors.length) {
                        $('#import-log').append('<p>Errors:</p><ul></ul>');
                        response.data.errors.forEach(function (error) {
                            $('#import-log ul').append('<li>' + error + '</li>');
                        });
                    }
                } else {
                    $('#import-log').html('<p>Error: ' + response.data.message + '</p>');
                }
                $('#progress-bar').css('width', '100%').text('Complete');
            },
            error: function () {
                $('#import-log').html('<p>An error occurred while importing the CSV file.</p>');
            }
        });
    });
});