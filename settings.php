<?php 
/*
 * Build settings page and handle options
 */
if(!class_exists('SSC_Locations_Settings')) {
  class SSC_Locations_Settings {
    public function __construct() {
  	  add_action( 'admin_menu', array( &$this, 'add_menu' ) );
  	  add_action( 'admin_init', array( &$this, 'admin_init' ) );
    }
    public function admin_init(){
    	global $ssc_location_options;
	    $options = $ssc_location_options;
	    foreach ( $options['settings'] as $settings_group ) {
			  $group_name = $settings_group['group_name'];
  			$group_title = $settings_group['group_title'];
			  $group_section = $settings_group['group_section'];
			  $section_name = 'ssc_admin_' . $group_name . '_settings_section_' . $group_section;
			  add_settings_section(
  				$section_name,
	  			$group_title,
		  		array( &$this, 'ssc_admin_settings_section_callback' ),
		  		$group_section
	  			);			
		  	foreach ( $settings_group['group_fields'] as $fields ) {
		  		$args = array();
		  		$args = $fields;
		  		$args['group_name'] = $group_name;
		  		$args['group_section'] = $group_section;
		  		$field_name = 'ssc_admin_' . $group_name . '_settings_' . $fields['name'];
			  	add_settings_field(
			  		$field_name,
			  		$fields['title'],
		  			array( &$this, 'ssc_admin_settings_fields_callback' ),
		  			$group_section,
		  			$section_name,
		  			$args
		  			);
			  	$tabs[] = $group_section;
		  		register_setting( $group_section, $field_name );
			  }
	    }
    }
  public function ssc_admin_settings_fields_callback( array $args ) {
    $field_name = 'ssc_admin_' . $args['group_name'] . '_settings_' .$args['name'];
	  if ( 'text' == $args['type'] || 'time' == $args['type'] ){
		  echo '<input type="text" name="' . $field_name . '" ';
		  if ( get_option( $field_name ) ) {
			  echo 'value="' . get_option( $field_name ) . '" ';
		  }
		  echo 'class="' . $field_name;
		  if ( 'time' == $args['type'] ) {
		  	echo ' time ';
		  }

		  echo '"/>';
	  }
	  if ( 'select' == $args['type'] ) {
		  $options = array();
		  echo '<select name="' . $field_name . '" class="' . $field_name . '">';
		  if ( 'us_state_abbrevs_names' == $args['options'] ) {
			  $options = ssc_us_states();
		  }
		  elseif ( is_array( $args['options'] ) ) {
			  $options = $args['options'];
		  }
		  foreach ( $options as $key => $value ) {
			  echo '<option value="' .$key . '" ';
			  if ( get_option( $field_name ) == $key ) {
				  echo 'selected';
			  }
			  echo '>' . $value . '</option>';
		  }
		  echo '</select>';
	  }
  }
  public function ssc_admin_settings_section_callback( $section_passed ) {
    echo '<p>' . $section_passed['title'] . '</p>';
  }  
    public function add_menu(){
  	  add_menu_page(
  		  'Location Settings',
  		  'Location Information',
  		  'administrator',
  		  'ssc_location_settings',
    		array( &$this, 'ssc_location_settings_page')
    		);
    }
    public function ssc_location_settings_page() {
  	  if(!current_user_can( 'manage_options' ) ) {
  		  wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
  	  }
  	  include( sprintf( "%s/templates/settings.php", dirname(__FILE__) ) );

    }
  }
}
?>