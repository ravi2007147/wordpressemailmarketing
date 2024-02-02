<?php
global $wpdb,$post;

$totalSent=$wpdb->get_var("select count(*) as cnt from ".$wpdb->prefix."campaign_list where campaignid=".$post->ID);
$failed=$wpdb->get_var("select count(*) as failed from ".$wpdb->prefix."campaign_list where campaignid=".$post->ID." and status='failed'");
$sent=$wpdb->get_var("select count(*) as sent from ".$wpdb->prefix."campaign_list where campaignid=".$post->ID." and status='sent'");
$totalViewed=$wpdb->get_var("select count(*) as viewed from ".$wpdb->prefix."campaign_list where campaignid=".$post->ID." and status='sent' and hasviewed=1");

$status='sent';

if($_GET['showfailed']){
	$status='failed';
}

$rows=$wpdb->get_results("select p.post_title,pl.*,pl.id as clid from ".$wpdb->prefix."posts p INNER JOIN ".$wpdb->prefix."campaign_list pl on pl.contactid=p.ID where p.post_type='pc_emails' and p.post_status='publish' and pl.campaignid=".$post->ID." and pl.status='".$status."'");
?>
<p>
	Total Emails : <?php echo $totalSent; ?>
</p>
<p>
	Total Failed : <?php echo $failed; ?>
</p>
<p>
	Total Sent : <?php echo $sent; ?>
</p>
<p>
	Total Viewed : <?php echo $totalViewed; ?>
</p>

<?php
if(!$_GET['showfailed']){
?>
<p>
	<a href="/wp-admin/post.php?post=<?php echo $post->ID; ?>&action=edit&showfailed=1">Show Failed</a>
</p>
<?php
}else{
	?>
	<p>
	<a href="/wp-admin/post.php?post=<?php echo $post->ID; ?>&action=edit&showfailed=0">Show Sent</a>
</p>
	<?php
}
?>
<table style="width: 100%;">
	<tr>
		<th>Email</th>
		<th>IP Address</th>
		<th>City</th>
		<th>Region</th>
		<th>Country</th>
		<th>Viewed Time</th>
	</tr>
	<?php
	foreach($rows as $row){
		if(!$row->viewercountry && $row->viewedip){
			$ldata=json_decode(file_get_contents("http://ipinfo.io/".$row->viewedip."/json"));
			// print_r($ldata);die;
			$wpdb->query("update ".$wpdb->prefix."campaign_list set viewercity='".$ldata->city."',viewerregion='".$ldata->region."',viewercountry='".$ldata->country."' where id=".$row->clid);
		}
		?>
		<tr>
			<td><?php echo $row->post_title; ?></td>
			<td><?php echo $row->viewedip; ?></td>
			<td><?php echo $row->viewercity; ?></td>
			<td><?php echo $row->viewerregion; ?></td>
			<td><?php echo $row->viewercountry; ?></td>
			<td><?php echo $row->viewedtime; ?></td>
		</tr>
		<?php
	}
	?>
</table>