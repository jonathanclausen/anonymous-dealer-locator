<?php
if (!defined('ABSPATH')) {
	exit;
}
?>
<div class="wrap">
	<h1><?php echo esc_html__('Import Dealers', 'anonymous-dealer-locator'); ?></h1>
	<p><?php echo esc_html__('Import dealers from a CSV file. You can either upload a file or provide a full server file path.', 'anonymous-dealer-locator'); ?></p>
	<p><strong><?php echo esc_html__('Note:', 'anonymous-dealer-locator'); ?></strong> <?php echo esc_html__('If your source is an Excel (.xlsx) file, please export it as CSV (UTF-8) first.', 'anonymous-dealer-locator'); ?></p>

	<form method="post" enctype="multipart/form-data">
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
		
		<p class="submit">
			<button type="submit" class="button button-primary" name="adl_import_dealers" value="1">
				<?php echo esc_html__('Import Dealers', 'anonymous-dealer-locator'); ?>
			</button>
		</p>
	</form>
</div>
<?php
?>

