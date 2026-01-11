<?php
global $wpdb;

$frows=$wpdb->get_results("select * from ".$wpdb->prefix."posts where post_type='pc_feeds' and post_status='publish'");

$feedid=@$_GET['feedid'];

//delete old posts


$query="select p.* from ".$wpdb->prefix."posts p INNER JOIN ".$wpdb->prefix."postmeta pm1 on pm1.post_id=p.ID and pm1.meta_key='pc_feed_post_date'  where p.post_type='pc_feed_posts' and p.post_status='publish' and pm1.meta_value<'".(date('Y-m-d H:i:s', strtotime('-7 day', strtotime(date('Y-m-d H:i:s')))))."'";
$rows=$wpdb->get_results($query);

foreach($rows as $row){
	$wpdb->query("delete from ".$wpdb->prefix."posts where ID=".$row->ID);
}
// $query="select * from ".$wpdb->prefix."posts  p INNER JOIN ".$wpdb->prefix."postmeta pm on pm.post_id=p.ID INNER JOIN ".$wpdb->prefix."postmeta pm1 on pm1.post_id=p.ID and pm1.meta_key='pc_feed_post_date' where p.post_type='pc_feed_posts' and p.post_status='publish' order by DATE_FORMAT(STR_TO_DATE(pm1.meta_value, '%a, %d %b %Y %H:%i:%s %z'), '%Y-%m-%d %H:%i:%s')";
$totalrecordperpage=100;
$totalpages=0;

if($feedid){
	$query="select * from ".$wpdb->prefix."posts p INNER JOIN ".$wpdb->prefix."postmeta pm on pm.post_id=p.ID and pm.meta_key='pc_feed_post_id' INNER JOIN ".$wpdb->prefix."postmeta pm1 on pm1.post_id=p.ID and pm1.meta_key='pc_feed_post_date'  where p.post_type='pc_feed_posts' and p.post_status='publish' and pm.meta_value='".$feedid."' group by p.ID  order by pm1.meta_value desc";
}else{
	$query="select * from ".$wpdb->prefix."posts p INNER JOIN ".$wpdb->prefix."postmeta pm on pm.post_id=p.ID and pm.meta_key='pc_feed_post_id' INNER JOIN ".$wpdb->prefix."postmeta pm1 on pm1.post_id=p.ID and pm1.meta_key='pc_feed_post_date'  where p.post_type='pc_feed_posts' and p.post_status='publish'  order by pm1.meta_value desc";
}

$posts=$wpdb->get_results($query);
$totalpages=ceil(count($posts)/$totalrecordperpage);
$page=0;

if(isset($_GET['number'])){
	$page=$_GET['number'];
}

$start = $page * $totalrecordperpage;
$end = $totalrecordperpage;

$query = $query." Limit ".$start.",".$end;
$posts=$wpdb->get_results($query);
?>
<form action="" method="post">
Filter : <select name="feedlist"><option value="">All</option> <?php foreach($frows as $row){
	?>
	<option <?php echo $feedid==$row->ID?'selected="selected"':''; ?> value="<?php echo $row->ID; ?>"><?php echo $row->post_title; ?></option>
	<?php
} ?></select>
</form>

<div style="margin-top: 20px;">
	<?php
		$rows=$posts;
		?>
		<div>Total Jobs : <?php echo count($rows); ?></div>
		<table cellpadding="1" style="width: 100%;">
			<tr>
				<th>ID</th>
				<th>Title</th>
				<th>Date</th>
			</tr>
		
		<?php
		foreach($rows as $row){
			$metas=get_post_meta($row->ID);
			?>
			<tr>
				<td><?php echo $row->ID; ?></td>
				<td><a style="display: block;text-align: left;padding: 5px;background-color: #c8c8ce;" class="showdescription" href="<?php echo $metas['pc_feed_post_link'][0]; ?>" target="_blank"><?php echo $row->post_title; ?></a>
					<!-- <div class="showdescription">Show Description</div> -->
					<div class="descriptionbox"><?php echo $metas['pc_feed_post_description'][0]; ?></div>
				</td>
				<td style="text-align: center;"><?php echo $this->getTimeAgo($metas['pc_feed_post_date'][0]); ?></td>
			</tr>
			<?php
		}
	?>
		</table>

		<div class="pagination">
			<?php
				for($i=1;$i<=$totalpages;$i++){
					?>
					<span><a href="/wp-admin/admin.php?page=pc-feed-posts&number=<?php echo $i; ?>"><?php echo $i; ?></a></span>
					<?php
				}
			?>
		</div>
</div>
<style>
	.descriptionbox{
		display: none;
		background-color: #e0e0e0;
		padding: 5px;
	}

	.showdescription{
		cursor: pointer;
		text-align: right;
		color: blue;
	}
</style>

<script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery(".showdescription").click(function(){
			jQuery(".descriptionbox").css("display","none");
			jQuery(this).parent().find(".descriptionbox").toggle();
			return false;
		});

		jQuery("select[name=feedlist]").change(function(){
			window.location="/wp-admin/admin.php?page=pc-feed-posts&feedid="+jQuery(this).val()
		});
	});
</script>