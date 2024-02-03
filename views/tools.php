<div style="display: flex;align-items: center;">
	<form action="/wp-admin/admin.php?page=pc-tools&downloadtodaylist=1">
		<p>Download Email List</p>
		<div>
			<input type="hidden" name="page" value="pc-tools">
			<input type="hidden" name="downloadtodaylist" value="1">
			<input type="date" name="date" placeholder="Choose date"> <input type="text" name="location" placeholder="Location (Optional)"> <input type="submit" name="downloadlist" value="Download" class="button">
		</div>
	</form>

	<div style="margin-left: 25px;">
		<p>&nbsp;</p>
		<a href="/wp-admin/edit.php?post_type=pc_campaign&deletependingrecord=1" class="button">Delete All Pending Pages</a>
	</div>
</div>