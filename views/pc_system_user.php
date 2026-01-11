<?php
global $wpdb;

// Get the redirect URI for Gmail OAuth (slug-based)
$redirect_uri = site_url('/gmail-oauth-callback');
?>

<div class="wrap">
	<h1>System User Management</h1>
	
	<!-- Gmail Redirect URI Notice -->
	<div class="notice notice-info" style="margin: 20px 0; padding: 15px;">
		<h3 style="margin-top: 0;">Gmail OAuth Redirect URI</h3>
		<p>Add this redirect URI to your Google Cloud Console OAuth 2.0 Client IDs:</p>
		<div style="background: #f0f0f0; padding: 10px; border-radius: 4px; margin: 10px 0; display: flex; align-items: center; gap: 10px;">
			<code id="redirect-uri-display" style="flex: 1; word-break: break-all;"><?php echo esc_html($redirect_uri); ?></code>
			<button type="button" class="button" id="copy-redirect-uri" title="Copy to clipboard">
				<span class="dashicons dashicons-clipboard" style="margin-top: 3px;"></span> Copy
			</button>
		</div>
		<p class="description">
			<strong>Important:</strong> 
			<br>1. Go to <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console → APIs & Services → Credentials</a>
			<br>2. Edit your OAuth 2.0 Client ID
			<br>3. Add the redirect URI above to "Authorized redirect URIs"
			<br>4. Scope used: <code>https://www.googleapis.com/auth/gmail.send</code> (production-safe, avoids heavy verification)
		</p>
	</div>
	
	<!-- Gmail Token Refresh Cron URL -->
	<div class="notice notice-success" style="margin: 20px 0; padding: 15px;">
		<h3 style="margin-top: 0;">Gmail Token Refresh Cron</h3>
		<p>Set up a cron job to refresh Gmail tokens every 55 minutes:</p>
		<div style="background: #f0f0f0; padding: 10px; border-radius: 4px; margin: 10px 0; display: flex; align-items: center; gap: 10px;">
			<code id="cron-url-display" style="flex: 1; word-break: break-all;"><?php echo esc_html(site_url('/gmail-refresh-tokens')); ?></code>
			<button type="button" class="button" id="copy-cron-url" title="Copy to clipboard">
				<span class="dashicons dashicons-clipboard" style="margin-top: 3px;"></span> Copy
			</button>
		</div>
		
		<div style="margin-top: 20px; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
			<h4 style="margin-top: 0;">📋 Cron Setup Instructions</h4>
			
			<div style="margin-bottom: 20px;">
				<strong>Method 1: cPanel Cron Jobs (Recommended)</strong>
				<ol style="margin-left: 20px; margin-top: 10px;">
					<li>Log in to your cPanel</li>
					<li>Go to <strong>Advanced → Cron Jobs</strong></li>
					<li>Select <strong>"Every 55 minutes"</strong> or use custom: <code>*/55 * * * *</code></li>
					<li>In the command field, enter:
						<div style="background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 4px;">
							<code>curl -s "<?php echo esc_html(site_url('/gmail-refresh-tokens')); ?>"</code>
						</div>
					</li>
					<li>Click <strong>Add New Cron Job</strong></li>
				</ol>
			</div>
			
			<div style="margin-bottom: 20px;">
				<strong>Method 2: Server Crontab (SSH Access)</strong>
				<ol style="margin-left: 20px; margin-top: 10px;">
					<li>SSH into your server</li>
					<li>Run: <code>crontab -e</code></li>
					<li>Add this line:
						<div style="background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 4px;">
							<code>*/55 * * * * curl -s "<?php echo esc_html(site_url('/gmail-refresh-tokens')); ?>"</code>
						</div>
					</li>
					<li>Save and exit (usually <code>:wq</code> in vi/vim)</li>
				</ol>
			</div>
			
			<div style="margin-bottom: 20px;">
				<strong>Method 3: WordPress Cron (wp_schedule_event)</strong>
				<p style="margin-top: 10px;">Add this to your theme's <code>functions.php</code> or a custom plugin:</p>
				<div style="background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 4px; font-family: monospace; font-size: 12px; overflow-x: auto;">
					<pre style="margin: 0;">// Schedule Gmail token refresh
if (!wp_next_scheduled('gmail_refresh_tokens_cron')) {
    wp_schedule_event(time(), 'gmail_refresh_interval', 'gmail_refresh_tokens_cron');
}

// Add custom interval (55 minutes)
add_filter('cron_schedules', function($schedules) {
    $schedules['gmail_refresh_interval'] = array(
        'interval' => 3300, // 55 minutes in seconds
        'display' => 'Every 55 Minutes'
    );
    return $schedules;
});

// Hook the cron event
add_action('gmail_refresh_tokens_cron', function() {
    wp_remote_get(site_url('/gmail-refresh-tokens'));
});</pre>
				</div>
			</div>
			
			<div style="margin-bottom: 20px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
				<strong>🔒 Security (Optional but Recommended):</strong>
				<ol style="margin-left: 20px; margin-top: 10px;">
					<li>Add this to your <code>wp-config.php</code>:
						<div style="background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 4px; font-family: monospace; font-size: 12px;">
							<code>define('GMAIL_REFRESH_SECRET', 'your-random-secret-key-here');</code>
						</div>
					</li>
					<li>Update your cron command to include the key:
						<div style="background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 4px; font-family: monospace; font-size: 12px;">
							<code>curl -s "<?php echo esc_html(site_url('/gmail-refresh-tokens?key=your-random-secret-key-here')); ?>"</code>
						</div>
					</li>
				</ol>
			</div>
			
			<div style="padding: 10px; background: #d1ecf1; border-left: 4px solid #0c5460; border-radius: 4px;">
				<strong>ℹ️ How It Works:</strong>
				<ul style="margin-left: 20px; margin-top: 10px;">
					<li>Runs every 55 minutes automatically</li>
					<li>Checks all active Gmail records with tokens</li>
					<li>Refreshes access tokens before they expire (tokens last 1 hour)</li>
					<li>Preserves refresh tokens (they don't change)</li>
					<li>Updates token JSON in database automatically</li>
					<li>Returns JSON response with refresh statistics</li>
				</ul>
			</div>
			
			<div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
				<strong>🧪 Test the Endpoint:</strong>
				<p style="margin: 5px 0;">Visit the URL directly in your browser or run:</p>
				<div style="background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 4px; font-family: monospace; font-size: 12px;">
					<code>curl "<?php echo esc_html(site_url('/gmail-refresh-tokens')); ?>"</code>
				</div>
				<p style="margin: 5px 0;">You should see a JSON response with refresh statistics.</p>
			</div>
		</div>
	</div>
	
	<div style="margin-bottom: 20px;">
		<button type="button" class="button button-primary" id="add-new-record">Add New Record</button>
		<button type="button" class="button" id="refresh-list">Refresh List</button>
	</div>

	<!-- Add/Edit Form (hidden by default) -->
	<div id="record-form" style="display: none; background: #fff; padding: 20px; margin-bottom: 20px; border: 1px solid #ccc;">
		<h2 id="form-title">Add New Record</h2>
		<form id="system-user-form">
			<input type="hidden" id="form-id" name="id" value="">
			<table class="form-table">
				<tr>
					<th><label for="form-email">Email</label></th>
					<td>
						<input type="email" id="form-email" name="email" class="regular-text" value="" required>
						<p class="description" id="email-description"></p>
					</td>
				</tr>
				<tr>
					<th><label for="form-service">Service</label></th>
					<td>
						<select id="form-service" name="service" class="regular-text" required>
							<option value="">Select Service</option>
							<option value="gmail">Gmail</option>
							<option value="yahoo">Yahoo</option>
							<option value="outlook">Outlook</option>
							<option value="other">Other</option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="form-username">Username</label></th>
					<td>
						<input type="text" id="form-username" name="username" class="regular-text" value="" required>
					</td>
				</tr>
				<tr id="row-password" style="display: none;">
					<th><label for="form-password">Password</label></th>
					<td>
						<div style="display: flex; align-items: center; gap: 5px;">
							<input type="password" id="form-password" name="password" class="regular-text" value="" style="flex: 1;">
							<button type="button" id="copy-password-btn" class="button" title="Copy password to clipboard" style="white-space: nowrap;">
								<span class="dashicons dashicons-clipboard" style="margin-top: 3px;"></span> Copy
							</button>
						</div>
					</td>
				</tr>
				<tr id="row-clientid" style="display: none;">
					<th><label for="form-clientid">Client ID</label></th>
					<td>
						<input type="text" id="form-clientid" name="clientid" class="regular-text" value="">
					</td>
				</tr>
				<tr id="row-secret" style="display: none;">
					<th><label for="form-secret">Secret</label></th>
					<td>
						<input type="text" id="form-secret" name="secret" class="regular-text" value="">
					</td>
				</tr>
				<tr id="row-token" style="display: none;">
					<th><label for="form-token">Token</label></th>
					<td>
						<textarea id="form-token" name="token" class="large-text" rows="5"></textarea>
					</td>
				</tr>
				<tr>
					<th><label for="form-port">Port</label></th>
					<td>
						<input type="number" id="form-port" name="port" class="small-text" value="587">
					</td>
				</tr>
				<tr>
					<th><label for="form-host">Host</label></th>
					<td>
						<input type="text" id="form-host" name="host" class="regular-text" value="">
					</td>
				</tr>
				<tr>
					<th><label for="form-status">Status</label></th>
					<td>
						<select id="form-status" name="status" class="regular-text" required>
							<option value="active">Active</option>
							<option value="inactive">Inactive</option>
						</select>
					</td>
				</tr>
			</table>
			<p class="submit">
				<button type="submit" class="button button-primary">Save</button>
				<button type="button" class="button" id="cancel-form">Cancel</button>
			</p>
		</form>
	</div>

	<!-- Data Table -->
	<div id="records-table-container">
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th>ID</th>
					<th>Service</th>
					<th>Username</th>
					<th>Status</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody id="records-tbody">
				<tr>
					<td colspan="5" style="text-align: center;">Loading...</td>
				</tr>
			</tbody>
		</table>
		<div id="pagination" style="margin-top: 20px;"></div>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
	var currentPage = 0;
	var limit = 10;
	var totalRecords = 0;

	// Function to toggle fields based on service type
	function toggleServiceFields(service) {
		var serviceLower = (service || '').toLowerCase();
		if(serviceLower === 'yahoo') {
			$('#row-password').show();
			$('#row-clientid').hide();
			$('#row-secret').hide();
			$('#row-token').hide();
		} else {
			$('#row-password').hide();
			$('#row-clientid').show();
			$('#row-secret').show();
			$('#row-token').show();
		}
	}

	// Load records
	function loadRecords(page) {
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'system_user_read',
				limit: limit,
				offset: page * limit
			},
			success: function(response) {
				if(response.success && response.data) {
					var records = response.data.data || response.data;
					var total = response.data.total || 0;
					displayRecords(records);
					totalRecords = total;
					updatePagination(page);
				} else {
					$('#records-tbody').html('<tr><td colspan="5" style="text-align: center;">Error loading records</td></tr>');
				}
			},
			error: function() {
				$('#records-tbody').html('<tr><td colspan="5" style="text-align: center;">Error loading records</td></tr>');
			}
		});
	}

	// Display records
	function displayRecords(records) {
		var html = '';
		if(records.length === 0) {
			html = '<tr><td colspan="5" style="text-align: center;">No records found</td></tr>';
		} else {
			records.forEach(function(record) {
				var hasToken = record.token && record.token.trim() !== '' && record.token !== 'null';
				var isGmail = (record.service || '').toLowerCase() === 'gmail';
				var authButtonText = hasToken ? 'Re-Authorize' : 'Authorize';
				
				html += '<tr>';
				html += '<td>' + record.id + '</td>';
				html += '<td>' + (record.service || '') + '</td>';
				html += '<td>' + (record.username || '') + '</td>';
				html += '<td>' + (record.status || '') + '</td>';
				html += '<td>';
				
				// Add authorize button for Gmail records
				if(isGmail && record.clientid) {
					html += '<button class="button button-small authorize-record" data-id="' + record.id + '" data-clientid="' + (record.clientid || '') + '">' + authButtonText + '</button> ';
				}
				
				html += '<button class="button button-small edit-record" data-id="' + record.id + '">Edit</button> ';
				html += '<button class="button button-small delete-record" data-id="' + record.id + '">Delete</button>';
				html += '</td>';
				html += '</tr>';
			});
		}
		$('#records-tbody').html(html);
	}

	// Update pagination
	function updatePagination(page) {
		var totalPages = Math.ceil(totalRecords / limit);
		var html = '';
		if(totalPages > 1) {
			html += '<div style="display: flex; align-items: center; gap: 10px;">';
			if(page > 0) {
				html += '<button class="button" id="prev-page">Previous</button>';
			}
			html += '<span>Page ' + (page + 1) + ' of ' + totalPages + ' (Total: ' + totalRecords + ' records)</span>';
			if(page < totalPages - 1) {
				html += '<button class="button" id="next-page">Next</button>';
			}
			html += '</div>';
		}
		$('#pagination').html(html);
	}

	// Show add form
	$('#add-new-record').on('click', function() {
		$('#form-title').text('Add New Record');
		$('#system-user-form')[0].reset();
		$('#form-id').val('');
		$('#form-email').val('').prop('readonly', false).css('background-color', '');
		$('#email-description').text('');
		$('#form-service').val('');
		$('#form-port').val('587');
		$('#form-status').val('active');
		toggleServiceFields('');
		$('#record-form').show();
	});

	// Cancel form
	$('#cancel-form').on('click', function() {
		$('#record-form').hide();
	});

	// Handle service change
	$('#form-service').on('change', function() {
		toggleServiceFields($(this).val());
	});

	// Copy password to clipboard
	$('#copy-password-btn').on('click', function() {
		var passwordField = $('#form-password');
		var password = passwordField.val();
		
		if(!password) {
			alert('Password field is empty');
			return;
		}
		
		// Create a temporary input element
		var tempInput = $('<input>');
		$('body').append(tempInput);
		tempInput.val(password).select();
		
		try {
			// Try using the modern Clipboard API
			if(navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(password).then(function() {
					// Show success feedback
					var $btn = $('#copy-password-btn');
					var originalText = $btn.html();
					$btn.html('<span class="dashicons dashicons-yes-alt" style="margin-top: 3px; color: #46b450;"></span> Copied!');
					$btn.css('color', '#46b450');
					
					setTimeout(function() {
						$btn.html(originalText);
						$btn.css('color', '');
					}, 2000);
				}).catch(function(err) {
					// Fallback to execCommand
					document.execCommand('copy');
					alert('Password copied to clipboard!');
				});
			} else {
				// Fallback for older browsers
				document.execCommand('copy');
				var $btn = $('#copy-password-btn');
				var originalText = $btn.html();
				$btn.html('<span class="dashicons dashicons-yes-alt" style="margin-top: 3px; color: #46b450;"></span> Copied!');
				$btn.css('color', '#46b450');
				
				setTimeout(function() {
					$btn.html(originalText);
					$btn.css('color', '');
				}, 2000);
			}
		} catch(err) {
			alert('Failed to copy password. Please select and copy manually.');
		}
		
		tempInput.remove();
	});

	// Submit form
	$('#system-user-form').on('submit', function(e) {
		e.preventDefault();
		
		var formData = {
			action: $('#form-id').val() ? 'system_user_update' : 'system_user_create',
			id: $('#form-id').val(),
			email: $('#form-email').val(),
			service: $('#form-service').val(),
			username: $('#form-username').val(),
			port: $('#form-port').val(),
			host: $('#form-host').val(),
			status: $('#form-status').val()
		};

		// Add fields based on service type
		var service = $('#form-service').val().toLowerCase();
		if(service === 'yahoo') {
			formData.password = $('#form-password').val();
		} else {
			formData.clientid = $('#form-clientid').val();
			formData.secret = $('#form-secret').val();
			formData.token = $('#form-token').val();
		}

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: formData,
			success: function(response) {
				if(response.success) {
					alert(response.data.message);
					$('#record-form').hide();
					loadRecords(currentPage);
				} else {
					alert(response.data.message || 'Error saving record');
				}
			},
			error: function() {
				alert('Error saving record');
			}
		});
	});

	// Edit record
	$(document).on('click', '.edit-record', function() {
		var id = $(this).data('id');
		
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'system_user_get',
				id: id
			},
			success: function(response) {
				if(response.success && response.data) {
					// Handle nested data structure: response.data.data
					var record = response.data.data || response.data;
					$('#form-title').text('Edit Record');
					$('#form-id').val(record.id);
					$('#form-email').val(record.email || '').prop('readonly', true).css('background-color', '#f0f0f0');
					$('#email-description').text('Email is read-only');
					$('#form-service').val(record.service || '');
					$('#form-username').val(record.username || '');
					$('#form-password').val(record.password || '');
					$('#form-clientid').val(record.clientid == null ? '' : (record.clientid || ''));
					$('#form-secret').val(record.secret == null ? '' : (record.secret || ''));
					$('#form-token').val(record.token || '');
					$('#form-port').val(record.port || '587');
					$('#form-host').val(record.host || '');
					$('#form-status').val(record.status || 'active');
					
					toggleServiceFields(record.service);
					$('#record-form').show();
					$('html, body').animate({ scrollTop: 0 }, 500);
				} else {
					alert('Error loading record');
				}
			},
			error: function() {
				alert('Error loading record');
			}
		});
	});

	// Delete record
	$(document).on('click', '.delete-record', function() {
		if(!confirm('Are you sure you want to delete this record?')) {
			return;
		}

		var id = $(this).data('id');
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'system_user_delete',
				id: id
			},
			success: function(response) {
				if(response.success) {
					alert(response.data.message);
					loadRecords(currentPage);
				} else {
					alert(response.data.message || 'Error deleting record');
				}
			},
			error: function() {
				alert('Error deleting record');
			}
		});
	});

	// Pagination
	$(document).on('click', '#prev-page', function() {
		if(currentPage > 0) {
			currentPage--;
			loadRecords(currentPage);
		}
	});

	$(document).on('click', '#next-page', function() {
		currentPage++;
		loadRecords(currentPage);
	});

	// Refresh list
	$('#refresh-list').on('click', function() {
		loadRecords(currentPage);
	});

	// Authorize/Re-Authorize Gmail using OAuth redirect flow
	$(document).on('click', '.authorize-record', function() {
		var id = $(this).data('id');
		var $btn = $(this);
		var originalText = $btn.text();
		
		$btn.prop('disabled', true).text('Loading...');
		
		// Get authorization URL from backend (stores ID in session)
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'system_user_get_auth_url',
				id: id
			},
			success: function(response) {
				if(response.success && response.data && response.data.auth_url) {
					// Open OAuth popup
					var width = 600;
					var height = 700;
					var left = (screen.width / 2) - (width / 2);
					var top = (screen.height / 2) - (height / 2);
					
					var popup = window.open(
						response.data.auth_url,
						'gmail_oauth',
						'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',toolbar=no,menubar=no,scrollbars=yes,resizable=yes'
					);
					
					// Check if popup was blocked
					if(!popup || popup.closed || typeof popup.closed == 'undefined') {
						alert('Popup was blocked. Please allow popups for this site and try again.');
						$btn.prop('disabled', false).text(originalText);
						return;
					}
					
					// Monitor popup for closure
					var checkClosed = setInterval(function() {
						if(popup.closed) {
							clearInterval(checkClosed);
							$btn.prop('disabled', false).text(originalText);
							// Reload records after authorization
							setTimeout(function() {
								loadRecords(currentPage);
							}, 1000);
						}
					}, 500);
					
					// Listen for message from popup
					window.addEventListener('message', function(event) {
						if(event.data === 'oauth_success') {
							clearInterval(checkClosed);
							if(popup && !popup.closed) {
								popup.close();
							}
							loadRecords(currentPage);
							$btn.prop('disabled', false).text(originalText);
						}
					});
					
				} else {
					alert(response.data.message || 'Error getting authorization URL');
					$btn.prop('disabled', false).text(originalText);
				}
			},
			error: function() {
				alert('Error getting authorization URL');
				$btn.prop('disabled', false).text(originalText);
			}
		});
	});


	// Copy redirect URI to clipboard
	$('#copy-redirect-uri').on('click', function() {
		var redirectUri = $('#redirect-uri-display').text();
		copyToClipboard(redirectUri, $(this));
	});

	// Copy cron URL to clipboard
	$('#copy-cron-url').on('click', function() {
		var cronUrl = $('#cron-url-display').text();
		copyToClipboard(cronUrl, $(this));
	});

	// Generic copy to clipboard function
	function copyToClipboard(text, $button) {
		var tempInput = $('<input>');
		$('body').append(tempInput);
		tempInput.val(text).select();
		
		try {
			if(navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(text).then(function() {
					showCopySuccess($button);
				}).catch(function(err) {
					document.execCommand('copy');
					showCopySuccess($button);
				});
			} else {
				document.execCommand('copy');
				showCopySuccess($button);
			}
		} catch(err) {
			alert('Failed to copy. Please select and copy manually.');
		}
		
		tempInput.remove();
	}

	// Show copy success feedback
	function showCopySuccess($button) {
		var originalText = $button.html();
		$button.html('<span class="dashicons dashicons-yes-alt" style="margin-top: 3px; color: #46b450;"></span> Copied!');
		$button.css('color', '#46b450');
		
		setTimeout(function() {
			$button.html(originalText);
			$button.css('color', '');
		}, 2000);
	}

	// Initial load
	loadRecords(0);
});
</script>

<style>
#record-form {
	border-radius: 4px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
#records-table-container {
	background: #fff;
	padding: 20px;
	border-radius: 4px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
</style>
