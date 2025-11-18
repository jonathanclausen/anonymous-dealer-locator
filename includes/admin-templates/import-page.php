<?php
if (!defined('ABSPATH')) {
	exit;
}
?>
<div class="wrap">
	<h1><?php echo esc_html__('Import Dealers', 'anonymous-dealer-locator'); ?></h1>
	<p><?php echo esc_html__('Import dealers from a CSV file. You can either upload a file or provide a full server file path.', 'anonymous-dealer-locator'); ?></p>
	<p><strong><?php echo esc_html__('Note:', 'anonymous-dealer-locator'); ?></strong> <?php echo esc_html__('If your source is an Excel (.xlsx) file, please export it as CSV (UTF-8) first.', 'anonymous-dealer-locator'); ?></p>

	<form id="adl-import-form" enctype="multipart/form-data">
		<?php wp_nonce_field('adl_import_dealers', 'adl_nonce'); ?>
		
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="import_file"><?php echo esc_html__('Upload CSV', 'anonymous-dealer-locator'); ?></label></th>
					<td>
						<input type="file" id="import_file" name="import_file" accept=".csv,.txt" />
						<p class="description"><?php echo esc_html__('Accepted types: CSV (UTF-8).', 'anonymous-dealer-locator'); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="file_path"><?php echo esc_html__('Or server file path', 'anonymous-dealer-locator'); ?></label></th>
					<td>
						<input type="text" class="regular-text" id="file_path" name="file_path" placeholder="C:\path\to\dealers.csv" />
						<p class="description"><?php echo esc_html__('Provide a full absolute path on the server (Windows paths allowed).', 'anonymous-dealer-locator'); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php echo esc_html__('Expected columns', 'anonymous-dealer-locator'); ?></th>
					<td>
						<p class="description">
							<?php echo esc_html__('Required: name, email, address. Optional: phone, city, postal_code (or zip/postal), country, latitude, longitude, status.', 'anonymous-dealer-locator'); ?>
						</p>
						<p class="description">
							<?php echo esc_html__('If latitude/longitude are missing, the importer will try to geocode from the address.', 'anonymous-dealer-locator'); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>
		
		<!-- Progress Bar -->
		<div id="adl-import-progress" style="display: none; margin: 20px 0;">
			<div style="background: #f0f0f1; border: 1px solid #c3c4c7; border-radius: 4px; padding: 2px; height: 30px; position: relative;">
				<div id="adl-progress-bar" style="background: #2271b1; height: 100%; width: 0%; border-radius: 3px; transition: width 0.3s ease; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">
					<span id="adl-progress-text">0%</span>
				</div>
			</div>
			<p id="adl-progress-status" style="margin-top: 10px; font-weight: 600;"></p>
		</div>
		
		<p class="submit">
			<button type="submit" class="button button-primary" id="adl-import-btn">
				<?php echo esc_html__('Import Dealers', 'anonymous-dealer-locator'); ?>
			</button>
			<button type="button" class="button" id="adl-cancel-import" style="display: none;">
				<?php echo esc_html__('Cancel', 'anonymous-dealer-locator'); ?>
			</button>
		</p>
	</form>
	
	<div id="adl-import-results" style="margin-top: 20px;"></div>
</div>

<script>
jQuery(document).ready(function($) {
	var importInProgress = false;
	var importCanceled = false;
	var importId = null;
	var currentBatch = 0;
	var totalBatches = 0;
	
	$('#adl-import-form').on('submit', function(e) {
		e.preventDefault();
		
		if (importInProgress) {
			return false;
		}
		
		var formData = new FormData(this);
		var fileInput = $('#import_file')[0];
		var filePath = $('#file_path').val();
		
		if (!fileInput.files.length && !filePath) {
			alert('<?php echo esc_js(__('Please upload a file or provide a server file path.', 'anonymous-dealer-locator')); ?>');
			return false;
		}
		
		// Show progress bar
		$('#adl-import-progress').show();
		$('#adl-import-btn').prop('disabled', true);
		$('#adl-cancel-import').show();
		$('#adl-import-results').empty();
		importInProgress = true;
		importCanceled = false;
		currentBatch = 0;
		
		// Initialize import
		formData.append('action', 'adl_import_dealers');
		formData.append('import_action', 'start');
		formData.append('adl_nonce', '<?php echo wp_create_nonce('adl_import_dealers'); ?>');
		
		$.ajax({
			url: adl_ajax.ajax_url,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				if (response.success && !response.data.error) {
					importId = response.data.import_id;
					totalBatches = response.data.total_batches;
					updateProgress(0, '<?php echo esc_js(__('Starting import...', 'anonymous-dealer-locator')); ?>');
					// Start processing batches
					processNextBatch();
				} else {
					$('#adl-import-progress').hide();
					var errorMsg = response.data && response.data.error ? response.data.error : '<?php echo esc_js(__('Import failed.', 'anonymous-dealer-locator')); ?>';
					$('#adl-import-results').html('<div class="notice notice-error"><p>' + errorMsg + '</p></div>');
					resetImport();
				}
			},
			error: function() {
				$('#adl-import-progress').hide();
				$('#adl-import-results').html('<div class="notice notice-error"><p><?php echo esc_js(__('An error occurred during import.', 'anonymous-dealer-locator')); ?></p></div>');
				resetImport();
			}
		});
		
		return false;
	});
	
	function processNextBatch() {
		if (importCanceled || !importId) {
			return;
		}
		
		var formData = new FormData();
		formData.append('action', 'adl_import_dealers');
		formData.append('import_action', 'process');
		formData.append('import_id', importId);
		formData.append('batch', currentBatch);
		formData.append('adl_nonce', '<?php echo wp_create_nonce('adl_import_dealers'); ?>');
		
		$.ajax({
			url: adl_ajax.ajax_url,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				if (response.success && !response.data.error) {
					var data = response.data;
					currentBatch = data.batch;
					
					// Calculate real progress
					var percent = (data.batch / data.total_batches) * 100;
					var statusMsg = '<?php echo esc_js(__('Processing batch', 'anonymous-dealer-locator')); ?> ' + data.batch + ' / ' + data.total_batches + 
						' (<?php echo esc_js(__('Added:', 'anonymous-dealer-locator')); ?> ' + data.total_added + 
						', <?php echo esc_js(__('Failed:', 'anonymous-dealer-locator')); ?> ' + data.total_failed + ')';
					
					updateProgress(percent, statusMsg);
					
					if (data.complete) {
						updateProgress(100, data.message || '<?php echo esc_js(__('Import completed!', 'anonymous-dealer-locator')); ?>');
						
						if (data.review_url) {
							setTimeout(function() {
								window.location.href = data.review_url;
							}, 2000);
						} else {
							setTimeout(function() {
								window.location.href = '<?php echo admin_url('admin.php?page=adl-dealers'); ?>';
							}, 2000);
						}
					} else {
						// Process next batch after a short delay
						setTimeout(processNextBatch, 100);
					}
				} else {
					$('#adl-import-progress').hide();
					var errorMsg = response.data && response.data.error ? response.data.error : '<?php echo esc_js(__('Import failed.', 'anonymous-dealer-locator')); ?>';
					$('#adl-import-results').html('<div class="notice notice-error"><p>' + errorMsg + '</p></div>');
					resetImport();
				}
			},
			error: function() {
				$('#adl-import-progress').hide();
				$('#adl-import-results').html('<div class="notice notice-error"><p><?php echo esc_js(__('An error occurred during import.', 'anonymous-dealer-locator')); ?></p></div>');
				resetImport();
			}
		});
	}
	
	$('#adl-cancel-import').on('click', function() {
		importCanceled = true;
		resetImport();
		$('#adl-import-progress').hide();
		$('#adl-import-results').html('<div class="notice notice-info"><p><?php echo esc_js(__('Import canceled.', 'anonymous-dealer-locator')); ?></p></div>');
	});
	
	function updateProgress(percent, message) {
		$('#adl-progress-bar').css('width', Math.min(100, Math.max(0, percent)) + '%');
		$('#adl-progress-text').text(Math.round(Math.min(100, Math.max(0, percent))) + '%');
		if (message) {
			$('#adl-progress-status').text(message);
		}
	}
	
	function resetImport() {
		importInProgress = false;
		importId = null;
		currentBatch = 0;
		totalBatches = 0;
		$('#adl-import-btn').prop('disabled', false);
		$('#adl-cancel-import').hide();
	}
});
</script>

