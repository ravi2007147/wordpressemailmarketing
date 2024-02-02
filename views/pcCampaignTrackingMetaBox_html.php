<?php
global $wpdb,$post;
$rows=$wpdb->get_results("select * from ".$wpdb->prefix."campaign_link_tracking where campaignid=".$post->ID);
$urows=$wpdb->get_results("select * from ".$wpdb->prefix."campaign_link_tracking where campaignid=".$post->ID." group by url_link,ipaddress");
echo "Total Clicks : ".count($rows)."<br />";
echo "Total Unique Clicks : ".count($urows)."<br />";
?>
<table style="width: 100%;">
	<tr>
		<th>URL</th>
		<th>IP</th>
		<th>City</th>
		<th>Region</th>
		<th>Country</th>
	</tr>
	<?php
	foreach($rows as $row){
		if(!$row->country && $row->ipaddress){
			$ldata=json_decode(file_get_contents("http://ipinfo.io/".$row->ipaddress."/json"));
			$wpdb->query("update ".$wpdb->prefix."campaign_link_tracking set city='".$ldata->city."',region='".$ldata->region."',country='".$ldata->country."' where id=".$row->id);
		}
		
		?>
		<tr>
			<td><?php echo urldecode($row->url_link); ?></td>
			<td><?php echo urldecode($row->ipaddress); ?></td>
			<td><?php echo urldecode($row->city); ?></td>
			<td><?php echo urldecode($row->region); ?></td>
			<td><?php echo urldecode($row->country); ?></td>
		</tr>
		<?php
	}
	?>
</table>
<style type="text/css">
	td{
		text-align: center;
	}
</style>