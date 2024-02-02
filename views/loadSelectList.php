<?php
global $wpdb,$post;
$rows=$wpdb->get_results("select * from ".$wpdb->prefix."posts where post_type='pc_lists' and post_status='publish'");
$listid=get_post_meta($post->ID,'c_list',true);

if(!$listid){
?>
<p>
	<select name="list" style="width: 100%;">
		<option value="">---Select List---</option>
		<?php
		foreach($rows as $row){
			?>
			<option <?php echo $listid==$row->ID?"selected='selected'":""; ?> value="<?php echo $row->ID; ?>"><?php echo $row->post_title; ?></option>
			<?php
		}
		?>
	</select>
</p>
<?php
}else{
	foreach($rows as $row){
		if($row->ID==$listid){
			echo $row->post_title;
		}
	}
}
?>