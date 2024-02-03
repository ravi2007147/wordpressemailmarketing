<?php
	global $post;
	$contact_status=get_post_meta($post->ID,'contact_status',true);
?>
<p>
	Select Type<br />
	<select name="contact_status">
		<option <?php echo $contact_status=="New Lead"?"selected='selected'":""; ?>>New Lead</option>
		<option <?php echo $contact_status=="Email Sent"?"selected='selected'":""; ?>>Email Sent</option>
		<option <?php echo $contact_status=="Follow-Up"?"selected='selected'":""; ?>>Follow-Up</option>
		<option <?php echo $contact_status=="Received Reply"?"selected='selected'":""; ?>>Received Reply</option>
		<option <?php echo $contact_status=="Exploratory Call Scheduled"?"selected='selected'":""; ?>>Exploratory Call Scheduled</option>
		<option <?php echo $contact_status=="Negotiation/Proposal Sent"?"selected='selected'":""; ?>>Negotiation/Proposal Sent</option>
		<option <?php echo $contact_status=="Contract Sent"?"selected='selected'":""; ?>>Contract Sent</option>
		<option <?php echo $contact_status=="Project Won"?"selected='selected'":""; ?>>Project Won</option>
		<option <?php echo $contact_status=="Project Lost"?"selected='selected'":""; ?>>Project Lost</option>
	</select>
</p>

<!-- New Lead:

Status: Initial stage when you have identified a potential contact and sent them a cold email.
Action: Move leads to this status when you initiate contact.
Email Sent:

Status: After sending the cold email.
Action: Move leads to this status once the email is sent. It indicates that the initial outreach has been made.
Follow-Up:

Status: If you need to send a follow-up email.
Action: Move leads to this status if you don't receive a response after the initial email, and you decide to follow up.
Received Reply:

Status: When the lead responds to your email.
Action: Move leads to this status upon receiving a response. This signifies an engagement and a potential opportunity for further communication.
Exploratory Call Scheduled:

Status: When you have successfully scheduled a call to discuss potential collaboration.
Action: Move leads to this status when a meeting or call is confirmed, indicating progress in the interaction.
Negotiation/Proposal Sent:

Status: After sending a proposal or discussing project details.
Action: Move leads to this status when you've reached the stage of providing detailed information or negotiating terms.
Contract Sent:

Status: When you send a formal contract.
Action: Move leads to this status when you send a contract for review and signature.
Project Won:

Status: When the lead converts into a confirmed project or client.
Action: Move leads to this status when the project is officially secured.
Project Lost:

Status: If the lead does not convert into a project.
Action: Move leads to this status if the deal falls through or if the lead decides not to proceed. -->