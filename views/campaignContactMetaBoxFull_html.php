<?php
	global $post;
	$phonenumber=get_post_meta($post->ID,'phonenumber',true);
?>
<div class="flex-grid">
	<div>
		<input type="number" value="<?php echo $phonenumber; ?>" name="phonenumber" placeholder="Phone Number">
	</div>
</div>

<style type="text/css">
	.flex-grid{
		display: inline-flex;
		width: 100%;
	}

	.flex-grid > div{
		width: 33%;
	}

	.flex-grid > div > input{
		width: 100%;
	}
</style>