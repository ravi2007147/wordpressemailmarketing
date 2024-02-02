<?php
global $wpdb;
$rows=$wpdb->get_results("select * from ".$wpdb->prefix."posts where post_type='pc_templates' and post_status='publish'");?>
<p>
	<select name="template">
		<option value="">---Select Email Template---</option>
		<?php
		foreach($rows as $row){
			?>
			<option value="<?php echo $row->ID; ?>"><?php echo $row->post_title; ?></option>
			<?php
		}
		?>
	</select>
</p>