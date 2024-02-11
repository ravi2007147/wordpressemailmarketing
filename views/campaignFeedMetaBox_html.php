<?php
global $post;
$meta=get_post_meta($post->ID,'feedurl',true);
?>
<input value="<?php echo $meta; ?>" type="text" style="width: 100%;" name="feedurl" placeholder="Enter feed url" />