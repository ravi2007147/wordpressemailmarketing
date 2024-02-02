<?php
	global $post;
	$setting_type=get_post_meta($post->ID,'setting_type',true);
?>
<select name="setting_type" style="width: 100%;">
	<option <?php echo $setting_type=="Text"?'selected="selected"':''; ?>>Text</option>
	<option <?php echo $setting_type=="Link"?'selected="selected"':''; ?>>Link</option>
</select>