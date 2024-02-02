<?php
global $post,$wpdb;

$fileurl="";

if($post){
	$fileurl=get_post_meta($post->ID,'upload_file_url',true);
}

// if(!$fileurl){
?>
	<p>
		<!-- <input type="file" name="uploadFile" id="upload_file"/> -->
		<input id="upload_file" type="text" size="36" name="uploadFile" value="" />
	<input id="upload_file_button" type="button" value="Upload File" />
	</p>
<?php
// }else{

$issaved=get_post_meta($post->ID,'saved_uploaded_file',true);
$meta=get_post_meta($post->ID);

if($issaved){
	$totalContacts=$wpdb->get_var("select count(*) as count from ".$wpdb->prefix."user_list ul INNER JOIN ".$wpdb->prefix."posts p on p.ID=ul.contactid where ul.listid=".$post->ID." and p.post_status='publish'");
	echo "Total Contacts : ".$totalContacts;
	if(@$meta['saved_uploaded_file_tl'][0] || @$meta['saved_uploaded_file_tl'][0]==0){
		echo "<br />Total Last Saved List : ".$meta['saved_uploaded_file_tl'][0];
	}

	if(@$meta['saved_uploaded_file_ta'][0] || @$meta['saved_uploaded_file_ta'][0]==0){
		echo "<br />Total Last Added To List : ".$meta['saved_uploaded_file_ta'][0];
	}

	if(@$meta['saved_uploaded_file_td'][0] || @$meta['saved_uploaded_file_td'][0]==0){
		echo "<br />Total Last Added Duplicate List : ".$meta['saved_uploaded_file_td'][0];
	}

	if(@$meta['saved_uploaded_file_ti'][0] || @$meta['saved_uploaded_file_ti'][0]==0){
		echo "<br />Total Last Ignored List : ".$meta['saved_uploaded_file_ti'][0];
	}
}else{

	$prows=$wpdb->get_results("select * from ".$wpdb->prefix."posts where post_type='pc_custom_fields' and post_status='publish'");

	$customfields=array();

	foreach($prows as $prow){
		$customfields[]=array('id'=>$this->createId($prow->post_title),'title'=>$prow->post_title);
	}
	$csvContent = file_get_contents($fileurl);
	// Parse the CSV content
	$rows = array_map('str_getcsv', explode("\n", $csvContent));

	// Assuming the first row contains headers
	$headers = array_shift($rows);
		
		?>
		<div>
			Add Contact List : <input type="checkbox" name="addToContactList" value="1" /><br /><br />
		</div>
		<div class="flexBoxContainer">
			<?php
			foreach($customfields as $customfield){
				?>
				<div>
					<?php
						echo $customfield['title'];
					?><br />
					<select name="cfields[<?php echo $customfield['id']; ?>]">
					<option></option>
					<?php
					foreach($headers as $header){
						?>
						<option><?php echo $header; ?></option>
						<?php
					}
				?></select>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}
// }

$rows=$wpdb->get_results("select * from ".$wpdb->prefix."posts p INNER JOIN ".$wpdb->prefix."user_list u on u.contactid=p.ID where p.post_status='publish' and listid=".$post->ID);
?>
<table style="width: 100%;">
	<tr>
		<th>Email</th>
	</tr>
	<?php
	foreach($rows as $row){
		?>
		<tr>
			<td><?php echo $row->post_title; ?></td>
		</tr>
		<?php
	}
	?>
</table>
<style type="text/css">
	.flexBoxContainer{
		display: inline-flex;
	    justify-content: space-between;
	    width: 100%;
	    flex-wrap: wrap;
	}

	.flexBoxContainer div{
		padding-bottom: 10px;
	}
</style>