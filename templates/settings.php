<div class="wrap">
	<h2>Location Settings</h2>
	<form method="post" action="options.php" class="ssc_location">
		<?php @settings_fields( 'ssc_admin_location_settings_section' ); ?>
		<?php @do_settings_fields( 'ssc_admin_location_settings_section' ); ?>
		<?php do_settings_sections( 'ssc_location' ); ?>
		<?php @submit_button(); ?>
	</form>
</div>