jQuery(document).ready( function( $ ) {
	$('#upload_file_button').click(function() {

        formfield = $('#upload_file').attr('name');
        tb_show( '', 'media-upload.php?type=image&amp;TB_iframe=true' );
        window.send_to_editor = function(html) {
           imgurl = $(html).attr('src');
           $('#upload_file').val(imgurl);
           tb_remove();
        }

        return false;
    });
});