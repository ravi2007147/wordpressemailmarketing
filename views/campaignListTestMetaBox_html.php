<p>
	Testing Email<br />
	<input type="text" name="testingemail" value="ravi@priorcoder.com" style="width: 100%;"><Br /><br />
	<input type="button" name="sendtestemail" value="Send">
</p>
<script type="text/javascript">
	jQuery(document).ready(function(){
		var ajaxurl='<?php echo admin_url( 'admin-ajax.php' ); ?>';
		jQuery("input[name=sendtestemail]").click(function(){
			jQuery.ajax({
			  method: "POST",
			  url: ajaxurl,
			  data: { action: "sendtestemail", id: "<?php echo $_GET['post']; ?>",email:jQuery("input[name=testingemail]").val() }
			})
			  .done(function( msg ) {
			    alert( "Email sent..." );
			  });
		});
	});
</script>