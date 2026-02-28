jQuery(document).ready(function($){
  $('#xilio-upload-btn').on('click', function(e){
    e.preventDefault();
    var files = $('input[name="kb_files[]"]')[0].files;
    if (!files.length) {
      $('#xilio-upload-status').text('Seleziona almeno un file.');
      return;
    }
    var form = new FormData();
    for (var i=0;i<files.length;i++){
      form.append('kb_files[]', files[i]);
    }
    form.append('nonce', XILIO_ADMIN.nonce);
    $.ajax({
      url: ajaxurl + '?action=xilio_admin_upload',
      method: 'POST',
      data: form,
      processData: false,
      contentType: false,
      success: function(resp){
        if (resp.success) {
          $('#xilio-upload-status').text(resp.data.message);
        } else {
          $('#xilio-upload-status').text('Errore: ' + resp.data);
        }
      },
      error: function(){
        $('#xilio-upload-status').text('Errore di rete durante upload.');
      }
    });
  });
});
