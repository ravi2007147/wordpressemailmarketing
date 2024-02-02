<?php
/* 
Plugin Name: Priorcoder Email Campaign
Version: 1.0
Description: Bulk Email Campaign Using Multiple SMTP accounts
Author: Priorcoder
Email: ravi@priorcoder.com
Author URI: http://www.priorcoder.com
*/
// echo __DIR__;die;
require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
include_once(__DIR__.'/google-api-php-client/vendor/autoload.php');
include_once(__DIR__.'/class.phpmaileroauthgoogle.php');
include_once(__DIR__.'/class.phpmaileroauth.php');

class EmailCampaign{
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'plugin_menu' ] );
		add_action( 'init', [ $this, 'init_func' ] );
		add_action('add_meta_boxes', [$this,'addMetaBoxes']);
		add_action( 'save_post', [$this,'save_template_post'], 10,3 );
		add_action( 'save_post', [$this,'handle_list_upload'], 10,3 );
		add_action( 'save_post', [$this,'handle_setting_update'], 10,3 );
		add_action('admin_print_scripts', [$this,'my_admin_scripts']);
		add_action('admin_print_styles', [$this,'my_admin_styles']);
		add_filter( 'mime_types', [$this,'wpse_mime_types'] );

		add_action( 'init', [$this,'my_custom_status_creation'] );
		add_action( 'post_submitbox_misc_actions', [$this,'add_to_post_status_dropdown']);
		add_action('admin_footer-edit.php',[$this,'custom_status_add_in_quick_edit']);
		add_filter( 'display_post_states', [$this,'display_archive_state'] );
		add_action('wp_loaded',[$this,'loading_func']);
		add_action('template_redirect', [$this,'template_handler']);
		add_action('wp_head', [$this,'custom_rewrite_rules_debug']);

		add_filter( 'query_vars', function( $query_vars ) {
		    $query_vars[] = 'logo_images';
		    $query_vars[] = 'hyperlink';
		    $query_vars[] = 'linkid';
		    $query_vars[] = 'cid';
		    $query_vars[] = 'contactid';
		    return $query_vars;
		} );

		// $this->emailtest();
		add_action('admin_head', [$this,'custom_post_list_page_notice']);
	}

	function getTimePassed($timestamp) {
	    $currentTime = time();
	    $timeDifference = $currentTime - strtotime($timestamp);

	    $hours = floor($timeDifference / 3600);
	    $minutes = floor(($timeDifference % 3600) / 60);
	    $seconds = $timeDifference % 60;

	    $result = '';

	    if ($hours > 0) {
	        $result .= $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ';
	    }

	    if ($minutes > 0) {
	        $result .= $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ';
	    }

	    if ($seconds > 0 || empty($result)) {
	        $result .= $seconds . ' second' . ($seconds > 1 ? 's' : '');
	    }

	    return $result;
	}


	function custom_post_list_page_notice(){
		$current_screen = get_current_screen();

	    // Replace 'your_custom_post_type' with the actual name of your custom post type
	    if ($current_screen->id === 'edit-pc_campaign') {
	    	global $wpdb;

	    	$row=$wpdb->get_row("select meta_value as datetime from ".$wpdb->prefix."postmeta where meta_key='last_email_tried_on' order by meta_value desc");
	    	$total_today_sites=$wpdb->get_var("select count(*) from polls_stocknewswebsites where lastChecked='".date('Y-m-d')."' and email is not null");
	    	$pending_sites=$wpdb->get_var("select count(*) from polls_stocknewswebsites where lastChecked is null and email is null and status='active'");
	    	$pending_pages=$wpdb->get_var("select count(*) from polls_stocknewswebsitepages sp INNER JOIN polls_stocknewswebsites ps on ps.id=sp.website_id where sp.status='new' and ps.status='inactive' and ps.lastChecked='".date('Y-m-d')."'");
	    	$queueemails=$wpdb->get_var("select count(*) from stock_campaign_list where status='new'");
	    	// echo date('Y-m-d H:i:s');
	    	// print_r($row->datetime);die;
	        ?>
	        <div class="notice notice-info">
	            <p>Last campaign sent on : <?php echo $this->getTimePassed($row->datetime); ?></p>
	            <p>Today Found Email : <?php echo $total_today_sites; ?> <a href="/wp-admin/edit.php?post_type=pc_campaign&downloadtodaylist=1">Download List</a></p>
	            <p>Pending Websites : <?php echo $pending_sites; ?></p>
	            <p>Pending Pages : <?php echo $pending_pages; ?> <a href="/wp-admin/edit.php?post_type=pc_campaign&deletependingrecord=1">Clear List</a></p>
	            <p>Emails In Queue : <?php echo $queueemails; ?></p>
	        </div>
	        <?php
	    }
	}

	function smtpEmail($to,$subject,$body,$id){
		global $wpdb;
		$row=$wpdb->get_row("select * from ".$wpdb->prefix."email_clients where id=".$id);

		// if($row->service=="gmail"){
		// 	$mail = new PHPMailerOAuth();
		// }else{
		// 	$mail = new PHPMailer();
		// }
		$mail = new PHPMailerOAuth();
		// $body="<b>This is best news</b>";
		// $body = eregi_replace("[\]",'',$body);
		$mail->IsSMTP();
		$mail->SMTPSecure="tls";

		$mail->SMTPAuth   = true;

		$mail->Host       = $row->host; // SMTP server
		$mail->SMTPDebug  = 2;                     // enables SMTP debug information (for testing)
		
		$mail->Port       = $row->port;                    // set the SMTP port for the GMAIL server
		if($row->service=="gmail"){

			$mail->AuthType = 'XOAUTH2';
			$mail->SMTPAutoTLS = false;
			$mail->oauthUserEmail = $row->username;
			$mail->oauthClientId = $row->clientid;
			$mail->oauthClientSecret = $row->secret;
			$gmail_token = json_decode($row->token, true);
			// print_r($gmail_token['access_token']);die;
			$mail->oauthAccessToken=$row->token;
			$mail->oauthRefreshToken = $gmail_token['refresh_token'];
		}else{
			                  // enable SMTP authentication
			$mail->Username   = $row->username; // SMTP account username
			$mail->Password   = $row->password;        // SMTP account password
		}
		
		$mail->SetFrom($row->username, 'Ravi Kumar');
		// $mail->AddReplyTo("priorcoder@gmail.com","Ravi Kumar");
		$mail->Subject    = $subject;
		$mail->MsgHTML($body);
		$mail->AddAddress($to,$to);
		$mail->isHTML( true );
		// print_r($mail);die;

		if(!$mail->Send()) {
		  return false;
		} else {
		  return true;
		}
	}

	function custom_rewrite_rules_debug() {
		return;
	    global $wp_rewrite;
	    $rules = $wp_rewrite->wp_rewrite_rules();
	    echo '<pre>' . print_r($rules, true) . '</pre>';
	}

	function get_client_ip() {
	    $ipaddress = '';
	    if (getenv('HTTP_CLIENT_IP'))
	        $ipaddress = getenv('HTTP_CLIENT_IP');
	    else if(getenv('HTTP_X_FORWARDED_FOR'))
	        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
	    else if(getenv('HTTP_X_FORWARDED'))
	        $ipaddress = getenv('HTTP_X_FORWARDED');
	    else if(getenv('HTTP_FORWARDED_FOR'))
	        $ipaddress = getenv('HTTP_FORWARDED_FOR');
	    else if(getenv('HTTP_FORWARDED'))
	       $ipaddress = getenv('HTTP_FORWARDED');
	    else if(getenv('REMOTE_ADDR'))
	        $ipaddress = getenv('REMOTE_ADDR');
	    else
	        $ipaddress = 'UNKNOWN';
	    return $ipaddress;
	}

	function template_handler() {
		if (get_query_var('logo_images') || get_query_var('hyperlink')) {}else{
			return;
		}
		$ip_address = $this->get_client_ip();

		$city="";
		$region="";
		$country="";

		if($ip_address){
			$ldata=json_decode(file_get_contents("http://ipinfo.io/".$ip_address."/json"));
			$city=$ldata->city;
			$region=$ldata->region;
			$country=$ldata->country;
		}

	    if (get_query_var('logo_images')) {
	    	global $wpdb;
	    	
	    	$lid=get_query_var('cid');

	    	if((int)$lid){
		    	$userAgent = $_SERVER['HTTP_USER_AGENT'];
		    	$wpdb->query("update ".$wpdb->prefix."campaign_list set hasviewed=1,viewedip='".$ip_address."',viewedtime='".date('Y-m-d H:m:s')."',useragent='".$userAgent."',viewercity='".$city."',viewerregion='".$region."',viewercountry='".$country."' where id=".$lid);
	    	}
	    	// print_r(get_query_var('logo_images'));die;
	        $remoteImage = "https://sahajnivesh.com/wp-content/uploads/2024/01/logo.png";
			$imginfo = getimagesize($remoteImage);
			header("Content-type: {$imginfo['mime']}");
			readfile($remoteImage);
			die;
	    }else if (get_query_var('hyperlink')) {
	    	global $wpdb;
	    	$id=(int)get_query_var('hyperlink');
	    	$cid=(int)get_query_var('contactid');
	    	$url=urldecode($_GET['u']);

	    	if($id && $url){

	    		$row=$wpdb->get_row("select * from ".$wpdb->prefix."posts where post_type='pc_campaign' and ID=".$id);
	    		// print_r($row);die;
	    		if($row){
	    			if(!$cid){
	    				$cid="";
	    			}

	    			$wpdb->query("insert into ".$wpdb->prefix."campaign_link_tracking set contactid=".$cid.",url_link='".urlencode($url)."',campaignid=".$id.",ipaddress='".$ip_address."',city='".$city."',region='".$region."',country='".$country."'");
	    		}
	    	}
	    	header("Location:".$url);
	    	die;
	    }
	}

	function generateSegment(){
		global $wpdb;

		$rows=$wpdb->get_results("select * from ".$wpdb->prefix."posts where post_type='pc_campaign' and post_status='segmented' Limit 0,1");

		foreach($rows as $row){
			$listid=get_post_meta($row->ID,'c_list',true);

			if($listid){
				$contacts=$wpdb->get_results("select * from ".$wpdb->prefix."posts p INNER JOIN ".$wpdb->prefix."user_list ul on ul.contactid=p.ID where p.post_status='publish' and p.post_type='pc_emails' and ul.listid=".$listid);

				foreach($contacts as $contact){
					$wpdb->query("insert into ".$wpdb->prefix."campaign_list set campaignid=".$row->ID.",contactid=".$contact->ID.",listid=".$listid.",status='new'");
				}
			}

			$wpdb->query("update ".$wpdb->prefix."posts set post_status='sending' where ID=".$row->ID);
		}

		die;
	}

	function sendCampaign(){
		global $wpdb;

		$obj=get_option( 'last_email_record' );

		if(!$obj){
			$obj=array();
			$obj['total']=0;
			$obj['datetime']=date('Y-m-d');
		}
		
		//pause after sending 100 emails
		if($obj['total']>=100 && (strtotime($obj['datetime'])==strtotime(date('Y-m-d')))){
			return;
		}
		
		$rows=$wpdb->get_results("select p.post_title as email,p.ID as contactid,pm.post_content as body,pm.post_title as subject,cl.id as cid,cl.campaignid from ".$wpdb->prefix."posts p INNER JOIN ".$wpdb->prefix."campaign_list cl on cl.contactid=p.ID INNER JOIN ".$wpdb->prefix."posts pm on pm.ID=cl.campaignid and pm.post_type='pc_campaign' where p.post_type='pc_emails' and p.post_status='publish' and cl.status='new' group by p.post_title Limit 0,1");
		// print_r($rows);die;

		foreach($rows as $row){
			$contactmetas=$wpdb->get_results("select * from ".$wpdb->prefix."customer_meta where contactid=".$row->contactid);
			$metas=array();

			foreach($contactmetas as $contactmetas){
				$metas[$contactmetas->meta_key]=$contactmetas->meta_value;
			}

			$content=$this->applyCustomFields($row->body,$metas,$row->campaignid,$row->contactid);
			$content = nl2br($content);
			$content=$content."<center><img style='width:20px;' src='https://sahajnivesh.com/logo_images/".$row->cid."/logo.png/' title='Priorcoder.com'/></center><br />";

			$headers = array(
				'Content-Type: text/html; charset=UTF-8',
				'From: Priorcoder <priorcoder@gmail.com>'
			);

			$smtplists=array(1,2,3);
			$k = array_rand($smtplists);
			$v = $smtplists[$k];

			// $sent=$this->smtpEmail($row->email, $row->subject, $content,1);
			// die;
			
			if($v==0){
				$sent=wp_mail($row->email, $row->subject, $content, $headers );
			}else{
				$sent=$this->smtpEmail($row->email, $row->subject, $content,$v);
			}

			if($sent){
				$wpdb->query("update ".$wpdb->prefix."campaign_list set status='sent' where id=".$row->cid);
				update_post_meta($row->contactid,'last_email_tried_on',date('Y-m-d H:i:s'));
				update_post_meta($row->contactid,'last_email_status','sent');
			}else{
				$wpdb->query("update ".$wpdb->prefix."campaign_list set status='failed' where id=".$row->cid);
				$wpdb->query("update ".$wpdb->prefix."posts set post_status='bounce' where ID=".$row->contactid);
				update_post_meta($row->contactid,'last_email_tried_on',date('Y-m-d H:i:s'));
				update_post_meta($row->contactid,'last_email_status','failed');
			}

			$obj['total']=$obj['total']+1;
			$obj['datetime']=date('Y-m-d');
			update_option('last_email_record',$obj);

			echo $row->email;
			echo "<br />";
			die;
		}

		$rows=$wpdb->get_results("select * from stock_posts where post_type='pc_campaign' and post_status='sending'");

		foreach($rows as $row){
			$notsent=$wpdb->get_var("select count(*) as ccount from ".$wpdb->prefix."campaign_list where campaignid=".$row->ID." and status='new'");

			if($notsent<=0){
				$wpdb->query("update ".$wpdb->prefix."posts set post_status='sent' where ID=".$row->ID);
			}
		}
	}

	function disableemail(){
		global $wpdb;

		$rows=$wpdb->get_results("select * from ".$wpdb->prefix."campaign_list where status='failed' and hasviewed<>1");
		
		foreach($rows as $row){
			$wpdb->query("update ".$wpdb->prefix."posts set post_status='bounce' where ID=".$row->contactid);
		}
	}

	function downloadtodaylist(){
		global $wpdb;
		$csv=array();
		$csv[]="WebsiteName,domain,email,location";
		$rows=$wpdb->get_results("select * from polls_stocknewswebsites where lastChecked='".date('Y-m-d')."' and email is not null");

		foreach($rows as $row){
			$csv[]=$row->websiteName.",".$row->domain.",".$row->email.",".str_replace(","," ",$row->city);
		}
		$csv=implode("\n",$csv);

		header('Content-Type: application/csv');
		header('Content-Disposition: attachment; filename=email-download-list.csv');
		header('Pragma: no-cache');
		echo $csv;
		die;
	}

	function deletependingrecords(){
		global $wpdb;
		$wpdb->query("delete from polls_stocknewswebsitepages");
	}

	function loading_func(){
		global $wpdb;

		if(isset($_GET['deletependingrecords'])){
			$this->deletependingrecords();
			die;
		}

		if(isset($_GET['downloadtodaylist'])){
			$this->downloadtodaylist();
			die;
		}

		if(isset($_GET['disableemail'])){
			$this->disableemail();
			die;
		}

		if(isset($_GET['emailtest'])){
			$this->smtpEmail("ravi@priorcoder.com","test-email","this is test email",3);
			die;
		}

		if(isset($_GET['generateSegment'])){
			$this->generateSegment();
			die;
		}

		if(isset($_GET['sendCampaign'])){
			$this->sendCampaign();
			die;
		}
	}

	function display_archive_state( $states ) {
	global $post;
		$arg = get_query_var( 'post_status' );
		if($arg != 'scheduled' || $arg != 'segmented' || $arg != 'sending' || $arg != 'sent'){
			if($post->post_status == 'scheduled'){
				echo "<script>
				jQuery(document).ready( function() {
				jQuery( '#post-status-display' ).text( 'Scheduled' );
				});
				</script>";
				return array('Scheduled');
			}

			if($post->post_status == 'segmented'){
				echo "<script>
				jQuery(document).ready( function() {
				jQuery( '#post-status-display' ).text( 'Segmented' );
				});
				</script>";
				return array('Segmented');
			}

			if($post->post_status == 'sending'){
				echo "<script>
				jQuery(document).ready( function() {
				jQuery( '#post-status-display' ).text( 'Sending' );
				});
				</script>";
				return array('Sending');
			}

			if($post->post_status == 'sent'){
				echo "<script>
				jQuery(document).ready( function() {
				jQuery( '#post-status-display' ).text( 'Sent' );
				});
				</script>";
				return array('Sent');
			}
		}
		return $states;
	}

	function custom_status_add_in_quick_edit() {
		global $post;
		if($post->post_type == 'pc_campaign'){
			echo "<script>
			jQuery(document).ready( function() {
			jQuery( 'select[name=\"_status\"]' ).append( '<option value=\"scheduled\">Scheduled</option>' );
			});
			</script>";

			echo "<script>
			jQuery(document).ready( function() {
			jQuery( 'select[name=\"_status\"]' ).append( '<option value=\"segmented\">Segmented</option>' );
			});
			</script>";

			echo "<script>
			jQuery(document).ready( function() {
			jQuery( 'select[name=\"_status\"]' ).append( '<option value=\"sending\">Sending</option>' );
			});
			</script>";

			echo "<script>
			jQuery(document).ready( function() {
			jQuery( 'select[name=\"_status\"]' ).append( '<option value=\"sent\">Sent</option>' );
			});
			</script>";
		}else if($post->post_type == 'pc_emails'){
			echo "<script>
			jQuery(document).ready( function() {
			jQuery( 'select[name=\"_status\"]' ).append( '<option value=\"bounce\">Bounce</option>' );
			});
			</script>";
		}
	}

	function add_to_post_status_dropdown()
	{
		global $post;
		if($post->post_type == 'pc_campaign'){
		
			$status = ($post->post_status == 'scheduled') ? "jQuery( '#post-status-display' ).text( 'Scheduled' );
			jQuery( 'select[name=\"post_status\"]' ).val('scheduled');" : '';
			echo "<script>
			jQuery(document).ready( function() {
			jQuery( 'select[name=\"post_status\"]' ).append( '<option value=\"scheduled\">Scheduled</option>' );
			".$status."
			});
			</script>";

			$status = ($post->post_status == 'segmented') ? "jQuery( '#post-status-display' ).text( 'Segmented' );
			jQuery( 'select[name=\"post_status\"]' ).val('segmented');" : '';
			echo "<script>
			jQuery(document).ready( function() {
			jQuery( 'select[name=\"post_status\"]' ).append( '<option value=\"segmented\">Segmented</option>' );
			".$status."
			});
			</script>";

			$status = ($post->post_status == 'sending') ? "jQuery( '#post-status-display' ).text( 'Sending' );
			jQuery( 'select[name=\"post_status\"]' ).val('sending');" : '';
			echo "<script>
			jQuery(document).ready( function() {
			jQuery( 'select[name=\"post_status\"]' ).append( '<option value=\"sending\">Sending</option>' );
			".$status."
			});
			</script>";

			$status = ($post->post_status == 'sent') ? "jQuery( '#post-status-display' ).text( 'Sent' );
			jQuery( 'select[name=\"post_status\"]' ).val('sent');" : '';
			echo "<script>
			jQuery(document).ready( function() {
			jQuery( 'select[name=\"post_status\"]' ).append( '<option value=\"sent\">Sent</option>' );
			".$status."
			});
			</script>";
		}else if($post->post_type == 'pc_emails'){
			$status = ($post->post_status == 'bounce') ? "jQuery( '#post-status-display' ).text( 'Bounce' );
			jQuery( 'select[name=\"post_status\"]' ).val('bounce');" : '';
			echo "<script>
			jQuery(document).ready( function() {
			jQuery( 'select[name=\"post_status\"]' ).append( '<option value=\"bounce\">Bounce</option>' );
			".$status."
			});
			</script>";
		}
	}

	function my_custom_status_creation(){
		register_post_status( 'scheduled', array(
		'label'                     => _x( 'Scheduled', 'post' ),
		'label_count'               => _n_noop( 'Scheduled <span class="count">(%s)</span>', 'Scheduled <span 
		class="count">(%s)</span>'),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true
		));

		register_post_status( 'segmented', array(
		'label'                     => _x( 'Segmented', 'post' ),
		'label_count'               => _n_noop( 'Segmented <span class="count">(%s)</span>', 'Segmented <span 
		class="count">(%s)</span>'),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true
		));

		register_post_status( 'sending', array(
		'label'                     => _x( 'Sending', 'post' ),
		'label_count'               => _n_noop( 'Sending <span class="count">(%s)</span>', 'Sending <span 
		class="count">(%s)</span>'),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true
		));

		register_post_status( 'sent', array(
		'label'                     => _x( 'Sent', 'post' ),
		'label_count'               => _n_noop( 'Sent <span class="count">(%s)</span>', 'Sent <span 
		class="count">(%s)</span>'),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true
		));

		register_post_status( 'bounce', array(
		'label'                     => _x( 'Bounce', 'post' ),
		'label_count'               => _n_noop( 'Bounce <span class="count">(%s)</span>', 'Bounce <span 
		class="count">(%s)</span>'),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true
		));
	}

	function wpse_mime_types( $existing_mimes ) {
	    // Add csv to the list of allowed mime types
	    $existing_mimes['csv'] = 'text/csv';

	    return $existing_mimes;
	}

	function my_admin_scripts() {    
	    wp_enqueue_script('media-upload');
	    wp_enqueue_script('thickbox');
	    wp_register_script('my-upload', WP_PLUGIN_URL.'/email-campaign/my-script.js', array('jquery','media-upload','thickbox'));
	    wp_enqueue_script('my-upload');
	}

	function my_admin_styles() {
	    wp_enqueue_style('thickbox');
	}

	function createId($title){
		$title=strtolower($title);
		$title=str_replace(" ","",$title);
		return $title;
	}

	function convertHyperlink($inputString, $trackingUrl,$campaignid='',$contactid='') {
	    // Define a regular expression to match hyperlinks
	    $pattern = '/https:\/\/[^\s<>"]+/';

	    // Replace the matched hyperlink with the desired format
	    $replacement = $trackingUrl . '$1';
	    $outputString = preg_replace_callback($pattern, function($matches) use ($trackingUrl,$contactid) {

	    	return $trackingUrl."?u=".(urlencode($matches[0]));
	    }, $inputString);

	    return $outputString;
	}

	function applyCustomFields($content,$metas=array(),$campaignid='',$contactid=''){
		global $wpdb;

		$rows=$wpdb->get_results("select * from ".$wpdb->prefix."posts where post_type='pc_setting_fields' and post_status='publish'");

		if(count($rows)>0){
			$trackingUrl=site_url()."/hyperlink/{campaignid}/{contactid}/";
			foreach($rows as $row){
				$type=get_post_meta($row->ID,'setting_type',true);
				$postcontent=$row->post_content;
				if($type=="Link"){
					$turl=$trackingUrl;
					$turl=str_replace("{contactid}",$contactid,$turl);
					$turl=str_replace("{campaignid}",$campaignid,$turl);
					$postcontent = $this->convertHyperlink($postcontent, $turl,$campaignid,$contactid);
				}
				$content=str_replace("[".$row->post_title."]",$postcontent,$content);
			}
		}

		if(count($metas)>0){
			foreach($metas as $key=>$meta){
				$content=str_replace("[".$key."]",$meta,$content);
			}
		}

		return $content;
	}

	function handle_setting_update($post_id){
		global $wpdb;
		if($_POST['post_type']!="pc_setting_fields"){
			return;
		}

		if($_POST['setting_type']){
			update_post_meta($post_id,'setting_type',$_POST['setting_type']);
		}
	}

	function handle_list_upload($post_id){
		global $wpdb;
		if($_POST['post_type']!="pc_lists"){
			return;
		}

		if($_POST['uploadFile']){
			update_post_meta($post_id,'upload_file_url',$_POST['uploadFile']);
			update_post_meta($post_id,'saved_uploaded_file',false);
		}

		if($_POST['addToContactList']){
			$cfields=$_POST['cfields'];

			$fileurl=get_post_meta($post_id,'upload_file_url',true);
			$csvContent = file_get_contents($fileurl);
			$rows = array_map('str_getcsv', explode("\n", $csvContent));
			$headers = array_shift($rows);
			$cnFields=array();
			$indexs=array();

			
			$emailindex=-1;
			foreach($cfields as $key=>$cfield){
				$i=0;

				foreach($headers as $header){
					if($header==$cfield){
						if($key=="email"){
							$cnFields[$i]=$key;
							$emailindex = $i;
							break;
						}

						$cnFields[]=$key;
						$indexs[]=$i;
						break;
					}

					$i=$i+1;
				}
			}

			$totalAdded=0;
			$totalDuplicate=0;
			$totalIgnored=0;
			$totalList=count($rows);

			// print_r($cnFields);die;
			foreach($rows as $row){
				if($row[$emailindex]){
					$rw=$wpdb->get_row("select * from ".$wpdb->prefix."posts where post_title='".$row[$emailindex]."' and post_type='pc_emails'");
					

					if(!$rw){
						$my_post = array(
						'post_title'    => wp_strip_all_tags($row[$emailindex]),
						'post_content'  => '',
						'post_type'=>'pc_emails',
						'post_status'   => 'publish'
						);

						// Insert the post into the database
						$contactid =wp_insert_post( $my_post );
						$listid=$_POST['post_ID'];

						$wpdb->query("insert into ".$wpdb->prefix."user_list set contactid=".$contactid.",listid=".$listid);

						
						foreach($cnFields as $k=>$val){
							$wpdb->query("insert into ".$wpdb->prefix."customer_meta set contactid=".$contactid.",meta_key='".$val."',meta_value='".$row[$k]."'");
						}

						$totalAdded = $totalAdded + 1;
					}else{
						$totalDuplicate = $totalDuplicate + 1;
					}
				}else{
					$totalIgnored = $totalIgnored + 1;
				}
			}

			update_post_meta($post_id,'saved_uploaded_file','yes');
			update_post_meta($post_id,'saved_uploaded_file_tl',$totalList);
			update_post_meta($post_id,'saved_uploaded_file_ta',$totalAdded);
			update_post_meta($post_id,'saved_uploaded_file_td',$totalDuplicate);
			update_post_meta($post_id,'saved_uploaded_file_ti',$totalIgnored);
		}
	}

	function save_template_post($post_id){
		global $wpdb;
		if($_POST['post_type']!="pc_campaign"){
			return;
		}

		// Remove the action to prevent a loop
        remove_action('save_post', [$this, 'save_template_post']);

		if(isset($_POST['template']) && strlen(@$_POST['template'])>0){
			$meta=get_post_meta($post_id);

			// if($meta['template'[0]]!=$_POST['template']){
				$row=$wpdb->get_row("select * from ".$wpdb->prefix."posts where ID=".$_POST['template']);
				// $content=$this->applyCustomFields($row->post_content);
				$content=$row->post_content;
				
				// if($row->post_content){
					$my_post = array(
				      'ID'           => $post_id,
				      'post_content' => $content,
				  	);
				  	wp_update_post($my_post,false,false);
				// }

				update_post_meta($post_id,'template',$_POST['template']);
			// }
		}

		if(isset($_POST['list']) && strlen(@$_POST['list'])>0){
			$wpdb->query("update ".$wpdb->prefix."posts set post_status='segmented' where ID=".$post_id);
			update_post_meta($post_id,'c_list',$_POST['list']);
		}
	}

	function init_func(){
		// flush_rewrite_rules();
		//add rewrite rule for tracking
		add_rewrite_rule('^logo_images/(.*)/(.*)\.png$', 'index.php?logo_images=$matches[2]&cid=$matches[1]', 'top');
		add_rewrite_rule('^hyperlink/(.*)/(.*)$', 'index.php?hyperlink=$matches[1]&contactid=$matches[2]', 'top');
		add_rewrite_rule('^hyperlink/(.*)$', 'index.php?hyperlink=$matches[1]&contactid=$matches[2]', 'top');
		//end

		$labels = array(
	    'name'               => _x( 'Campaigns', 'post type general name' ),
	    'singular_name'      => _x( 'Campaign', 'post type singular name' ),
	    'add_new'            => _x( 'Add New', 'campaign' ),
	    'add_new_item'       => __( 'Add New Campaign' ),
	    'edit_item'          => __( 'Edit Campaign' ),
	    'new_item'           => __( 'New Campaign' ),
	    'all_items'          => __( 'All Campaigns' ),
	    'view_item'          => __( 'View Campaign' ),
	    'search_items'       => __( 'Search Campaign' ),
	    'not_found'          => __( 'No Campaigns found' ),
	    'not_found_in_trash' => __( 'No Campaigns found in the Trash' ), 
	    'parent_item_colon'  => ’,
	    'menu_name'          => 'Campaigns'
	  );
	  $args = array(
	    'labels'        => $labels,
	    'description'   => 'Holds our products and product specific data',
	    'public'        => false,
	    'menu_position' => 5,
	    'supports'      => array( 'title', 'editor' ),
	    'has_archive'   => true,
	    'show_in_menu'=>'pc_email_campaign',
	    'show_ui'            => true,
	  );
	  register_post_type( 'pc_campaign', $args );


	  $labels1 = array(
	    'name'               => _x( 'Templates', 'post type general name' ),
	    'singular_name'      => _x( 'Template', 'post type singular name' ),
	    'add_new'            => _x( 'Add New', 'template' ),
	    'add_new_item'       => __( 'Add New Template' ),
	    'edit_item'          => __( 'Edit Template' ),
	    'new_item'           => __( 'New Template' ),
	    'all_items'          => __( 'All Templates' ),
	    'view_item'          => __( 'View Template' ),
	    'search_items'       => __( 'Search Template' ),
	    'not_found'          => __( 'No Templates found' ),
	    'not_found_in_trash' => __( 'No Templates found in the Trash' ), 
	    'parent_item_colon'  => ’,
	    'menu_name'          => 'Templates'
	  );
	  $args1 = array(
	    'labels'        => $labels1,
	    'description'   => 'Holds our products and product specific data',
	    'public'        => false,
	    'menu_position' => 6,
	    'supports'      => array( 'title', 'editor' ),
	    'has_archive'   => true,
	    'show_in_menu'=>'pc_email_campaign',
	    'show_ui'            => true,
	  );
	  register_post_type( 'pc_templates', $args1 );

	  $labels1 = array(
	    'name'               => _x( 'Custom Fields', 'post type general name' ),
	    'singular_name'      => _x( 'Custom Fields', 'post type singular name' ),
	    'add_new'            => _x( 'Add New', 'customfield' ),
	    'add_new_item'       => __( 'Add New Custom Field' ),
	    'edit_item'          => __( 'Edit Custom Field' ),
	    'new_item'           => __( 'New Custom Field' ),
	    'all_items'          => __( 'All Custom Fields' ),
	    'view_item'          => __( 'View Custom Field' ),
	    'search_items'       => __( 'Search Custom Field' ),
	    'not_found'          => __( 'No Custom Fields found' ),
	    'not_found_in_trash' => __( 'No Custom Fields found in the Trash' ), 
	    'parent_item_colon'  => ’,
	    'menu_name'          => 'Custom Fields'
	  );
	  $args1 = array(
	    'labels'        => $labels1,
	    'description'   => 'Holds our products and product specific data',
	    'public'        => false,
	    'menu_position' => 7,
	    'supports'      => array( 'title', 'editor' ),
	    'has_archive'   => true,
	    'show_in_menu'=>'pc_email_campaign',
	    'show_ui'            => true,
	  );
	  register_post_type( 'pc_custom_fields', $args1 ); 

	  $labels2 = array(
	    'name'               => _x( 'Setting Fields', 'post type general name' ),
	    'singular_name'      => _x( 'Setting Fields', 'post type singular name' ),
	    'add_new'            => _x( 'Add New', 'scustomfield' ),
	    'add_new_item'       => __( 'Add New Setting Field' ),
	    'edit_item'          => __( 'Edit Setting Field' ),
	    'new_item'           => __( 'New Setting Field' ),
	    'all_items'          => __( 'All Setting Fields' ),
	    'view_item'          => __( 'View Setting Field' ),
	    'search_items'       => __( 'Search Setting Field' ),
	    'not_found'          => __( 'No Setting Fields found' ),
	    'not_found_in_trash' => __( 'No Setting Fields found in the Trash' ), 
	    'parent_item_colon'  => ’,
	    'menu_name'          => 'Setting Fields'
	  );
	  $args2 = array(
	    'labels'        => $labels2,
	    'description'   => 'Holds our products and product specific data',
	    'public'        => false,
	    'menu_position' => 8,
	    'supports'      => array( 'title', 'editor' ),
	    'has_archive'   => true,
	    'show_in_menu'=>'pc_email_campaign',
	    'show_ui'            => true,
	  );
	  register_post_type( 'pc_setting_fields', $args2 );

	  $labels2 = array(
	    'name'               => _x( 'Contacts', 'post type general name' ),
	    'singular_name'      => _x( 'Contacts', 'post type singular name' ),
	    'add_new'            => _x( 'Add New', 'contact' ),
	    'add_new_item'       => __( 'Add New Contact' ),
	    'edit_item'          => __( 'Edit Contact' ),
	    'new_item'           => __( 'New Contact' ),
	    'all_items'          => __( 'All Contacts' ),
	    'view_item'          => __( 'View Contact' ),
	    'search_items'       => __( 'Search Contact' ),
	    'not_found'          => __( 'No Contacts found' ),
	    'not_found_in_trash' => __( 'No Contacts found in the Trash' ), 
	    'parent_item_colon'  => ’,
	    'menu_name'          => 'Contacts'
	  );
	  $args2 = array(
	    'labels'        => $labels2,
	    'description'   => 'Holds our products and product specific data',
	    'public'        => false,
	    'menu_position' => 8,
	    'supports'      => array( 'title'),
	    'has_archive'   => true,
	    'show_in_menu'=>'pc_email_campaign',
	    'show_ui'            => true,
	  );
	  register_post_type( 'pc_emails', $args2 );

	  $labels2 = array(
	    'name'               => _x( 'Lists', 'post type general name' ),
	    'singular_name'      => _x( 'Lists', 'post type singular name' ),
	    'add_new'            => _x( 'Add New', 'list' ),
	    'add_new_item'       => __( 'Add New List' ),
	    'edit_item'          => __( 'Edit List' ),
	    'new_item'           => __( 'New List' ),
	    'all_items'          => __( 'All Lists' ),
	    'view_item'          => __( 'View List' ),
	    'search_items'       => __( 'Search List' ),
	    'not_found'          => __( 'No Lists found' ),
	    'not_found_in_trash' => __( 'No Lists found in the Trash' ), 
	    'parent_item_colon'  => ’,
	    'menu_name'          => 'Lists'
	  );
	  $args2 = array(
	    'labels'        => $labels2,
	    'description'   => 'Holds our products and product specific data',
	    'public'        => false,
	    'menu_position' => 8,
	    'supports'      => array( 'title'),
	    'has_archive'   => true,
	    'show_in_menu'=>'pc_email_campaign',
	    'show_ui'            => true,
	  );
	  register_post_type( 'pc_lists', $args2 );

	  
	}

	function addMetaBoxes(){
		global $post;

		$screens = ['pc_campaign'];
		if($post->post_status=="publish"){
		    foreach ($screens as $screen) {
		        add_meta_box(
		            'campaignsync_box_id',           // Unique ID
		            'Select Template',  // Box title
		            [$this,'campaignMetaBox_html'],  // Content callback, must be of type callable
		            $screen,                  // Post type
		            'side'
		        );
		    }
		}

	    foreach ($screens as $screen) {
	        add_meta_box(
	            'campaignlist_box_id',           // Unique ID
	            'Select List',  // Box title
	            [$this,'campaignListMetaBox_html'],  // Content callback, must be of type callable
	            $screen,                  // Post type
	            'side'
	        );
	    }

	    $screens = ['pc_lists'];
	    foreach ($screens as $screen) {
	        add_meta_box(
	            'pc_list_box_id',           // Unique ID
	            'List Panel',  // Box title
	            [$this,'pcListMetaBox_html'],  // Content callback, must be of type callable
	            $screen
	        );
	    }

	    $screens = ['pc_campaign'];
	    foreach ($screens as $screen) {
	        add_meta_box(
	            'pc_campaign_box_id',           // Unique ID
	            'Report',  // Box title
	            [$this,'pcCampaignReportMetaBox_html'],  // Content callback, must be of type callable
	            $screen
	        );
	    }

	    $screens = ['pc_campaign'];
	    foreach ($screens as $screen) {
	        add_meta_box(
	            'pc_campaign_tracking_box_id',           // Unique ID
	            'Tracking',  // Box title
	            [$this,'pcCampaignTrackingMetaBox_html'],  // Content callback, must be of type callable
	            $screen
	        );
	    }

	    $screens = ['pc_setting_fields'];
	    foreach ($screens as $screen) {
	        add_meta_box(
	            'pc_setting_fields_box_id',           // Unique ID
	            'Type',  // Box title
	            [$this,'pcSettingFieldsMetaBox_html'],  // Content callback, must be of type callable
	            $screen,
	            'side'
	        );
	    }
	}

	function pcSettingFieldsMetaBox_html(){
		require_once("views/pcSettingFieldsMetaBox_html.php");
	}

	function pcCampaignTrackingMetaBox_html(){
		require_once("views/pcCampaignTrackingMetaBox_html.php");
	}

	function pcCampaignReportMetaBox_html(){
		require_once("views/campaignReport.php");
	}

	function campaignListMetaBox_html(){
		require_once("views/loadSelectList.php");
	}

	function pcListMetaBox_html(){
		require_once("views/loadPcListFile.php");
	}

	function campaignMetaBox_html(){
		global $post;
		$meta=get_post_meta($post->ID);
		require_once("views/loadMetaBoxes.php");
	}

	//code to handle admin menu
	function plugin_menu() {
		add_menu_page(
			'Email Campaign', // Tab title.
			'Email Campaigns', //Menu title.
			'manage_options',
			'pc_email_campaign',
			 [$this, 'pc_email_campaign' ],
			'dashicons-book',
			6
		);
	}

	function email_campaign(){
		
	}
}

new EmailCampaign();
?>