<div class="wrap">
	<h2>Location Settings</h2>
	<?php settings_errors(); ?>
	<?php
	  $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'contact';
	  $page = isset( $_GET['page'] ) ? $_GET['page'] : ''; 
	  ?>
	<h2 class="nav-tab-wrapper">
		<a href="<?php echo '?page=' . $page; ?>&amp;tab=contact" class="nav-tab <?php echo $active_tab == 'contact' ? 'nav-tab-active' : ''; ?>">Contact Information</a>
		<a href="<?php echo '?page=' . $page; ?>&amp;tab=socialmedia" class="nav-tab <?php echo $active_tab == 'socialmedia' ? 'nav-tab-active' : ''; ?>">Social Media</a>
		<a href="<?php echo '?page=' . $page; ?>&amp;tab=business_hours" class="nav-tab <?php echo $active_tab == 'business_hours' ? 'nav-tab-active' : ''; ?>">Hours</a>
	</h2>
	<form method="post" action="options.php"  class="ssc_location_<?php echo $active_tab; ?>">
    <?php
      if ( $active_tab == 'contact' ) {
        @settings_fields( 'ssc_location_contact' );
        //@do_settings_fields( 'ssc_location_settings_section', 'ssc_admin_contact_settings_section_ssc_location_contact' );
		    do_settings_sections( 'ssc_location_contact' );
		  }
		  if ( $active_tab == 'socialmedia' ){
        @settings_fields( 'ssc_location_socialmedia' );
        //@do_settings_fields( 'ssc_location_settings_section' );
		    do_settings_sections( 'ssc_location_socialmedia' );		  	
		  }
		  if ( $active_tab == 'business_hours' ){
        @settings_fields( 'ssc_location_business_hours' );
        //@do_settings_fields( 'ssc_admin_location_settings_section' );
		    do_settings_sections( 'ssc_location_business_hours' );		  	
		  }		  

		 //@submit_button();
		  echo '<input type="submit" name="submit_button" class="location_submit button button-primary" value="Submit">';
		?>
	</form>
</div>