<?php
/*
Plugin Name: SSC Locations Widget
Plugin URI:
Description: Display Locations Widget ( for multi-sites )
Version: 0.1.1
Author: Scott Sawyer Consulting
Author URI: http://www.scottsawyerconsulting.com/about
License: GPL2
*/
require_once( sprintf( "%s/widgets/widget.php", dirname(__FILE__) ) );
add_action( 'widgets_init', create_function('', 'return register_widget("SSC_Locations_Widget");') );
add_action( 'admin_footer', 'ssc_location_js' );
add_action( 'wp_ajax_ssc_location_update_form', 'ssc_location_update_form_callback' );
add_action( 'wp_ajax_nopriv_ssc_location_update_form', 'ssc_location_update_form_callback' );
add_shortcode( 'location', 'ssc_location_shortcode' );
//add_action( 'admin_init', 'ssc_admin_settings_api_init' );
require_once( sprintf( "%s/settings.php", dirname(__FILE__) ) );
$ssc_location_settings = new SSC_Locations_Settings;

function ssc_get_site_count(){
    global $wpdb;
    $id = 0;
    $site_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM wp_blogs WHERE deleted = %d",$id));
    return $site_count;
}
function ssc_get_blog_list ( $args = NULL ) {
	$sites_list = array();
	if ( function_exists( 'get_blog_list' )  ){
		$sites_list = get_blog_list ( $args['offset'], $args['limit'] );
	}
	else {
		if ( wp_is_large_network() == false && ( $args['limit'] >= 100 || $args['limit'] == 'all' )  ) {
			if ( $args['limit'] == 'all' ) {
				$sites_args['limit'] = get_site_count();
			}
			else {
				$site_args['limit'] = $args['limit'];
			}
			if ( array_key_exists('network_id', $args ) ) {
				$sites_args['network_id'] = $args['network_id'];
			}
			if ( array_key_exists('public', $args ) ) {
				$sites_args['public'] = $args['public'];
			}
			if ( array_key_exists('archived', $args ) ) {
				$sites_args['archived'] = $args['archived'];
			}
			if ( array_key_exists('mature', $args ) ) {
				$sites_args['mature'] = $args['mature'];
			}			
			if ( array_key_exists('spam', $args ) ) {
				$sites_args['spam'] = $args['spam'];
			}
			if ( array_key_exists('deleted', $args ) ) {
				$sites_args['deleted'] = $args['deleted'];
			}
			if ( array_key_exists('offset', $args ) ) {
				$sites_args['offset'] = $args['offset'];
			}
			$sites_list = wp_get_sites( $sites_args );
		}
	}
	/**/
	return $sites_list;
}


/*
 * Get Blog Vals
 */
function get_site_contacts( $site_list ) {
	if ( !is_array( $site_list ) ) {
		$site_list = array( $site_list );
	}
  foreach( $site_list as $site ){
   	$site_options[$site['blog_id']]['name'] = get_blog_details( $site['blog_id'])->blogname;
    $site_options[$site['blog_id']]['street'] = get_blog_option( $site['blog_id'], 'ssc_admin_contact_settings_street' );
    $site_options[$site['blog_id']]['city'] = get_blog_option( $site['blog_id'], 'ssc_admin_contact_settings_city' );
    $site_options[$site['blog_id']]['state'] = get_blog_option( $site['blog_id'], 'ssc_admin_contact_settings_state' );
    $site_options[$site['blog_id']]['zip'] = get_blog_option( $site['blog_id'], 'ssc_admin_contact_settings_zip' );
    $site_options[$site['blog_id']]['phone'] = get_blog_option( $site['blog_id'], 'ssc_admin_contact_settings_phone' );
    $site_options[$site['blog_id']]['path'] = get_blog_details( $site['blog_id'])->path;
  }
  return $site_options;
}
/*
 * Get Social Media
 */
function get_site_socialmedia( $site_list ) {
	/*global $ssc_options;
	$options = $ssc_options;
	/**/
	global $ssc_location_options;
	$options = $ssc_location_options;
	if ( !is_array( $site_list ) ){
		$site_list = array( $site_list );
	}
	foreach ( $options['settings'] as $option_group ) {
		if ( $option_group['group_name'] == 'socialmedia' ){
			foreach ($option_group['group_fields'] as $field ) {
				if ( get_blog_option( $site_list['blog_id'], 'ssc_admin_socialmedia_settings_' . $field['name'] ) ) {
  				$site_options[$site_list['blog_id']][$field['name']] = array(
	  				'name' => $field['name'],
		  			'title' => $field['title'],
			  		'value' => get_blog_option( $site_list['blog_id'], 'ssc_admin_socialmedia_settings_' . $field['name'] )
				  	);
  		  }
			}
		}
	}
	if ( $site_options ){
		return $site_options;
	}
	else {
		return false;
	}
}
/*
 * Get blog hours
 */

function get_site_hours( $site_list ) {
	/*global $ssc_options;
	$options = $ssc_options;
	/**/
	global $ssc_location_options;
	$options = $ssc_location_options;
	if ( !is_array( $site_list ) ){
		$site_list = array( $site_list );
	}
	foreach ($options['settings'] as $option_group) {
		if ( $option_group['group_name'] == 'business_hours' ) {
  		foreach ( $option_group['group_fields'] as $field ) {
	  		if ( get_blog_option( $site_list['blog_id'], 'ssc_admin_business_hours_settings_' . $field['name'] ) ) {
					$site_options[$site_list['blog_id']][$field['name']] = array( 
						'name' => $field['name'],
						'title' => $field['title'],
						'value' => get_blog_option( $site_list['blog_id'], 'ssc_admin_business_hours_settings_' . $field['name'] )
						);
					}
				}
			}
		}
  if ( $site_options ){
	 	return $site_options;
	}
	else {
		return false;
	}
}
/*
 * Shortcode
 * by default, it will show the current post location
 */
function ssc_location_shortcode( $atts ) {
	extract( shortcode_atts( array(
		'sites' => get_current_blog_id(),
		'hours' => 'hide',
		'map' => 'hide',
		'address' => 'show'
		), $atts ) );
	if ( $sites == 'all' ) {
		$site_list = get_blog_list( 0, 'all' );
	}
	else {
		$site_list = array($sites);
	}
	ob_start();
	if ( $map == 'show' ) {
		$pageURL = 'http';
    if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") {
      $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
    } else {
      $pageURL .= $_SERVER["SERVER_NAME"];
    }
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    if ( is_plugin_active( 'osm/osm.php' ) ) {
    	//echo $pageURL;
  	  echo do_shortcode( '[osm_map marker_file="'.$pageURL.'/marker/marker.php?sites='.$sites.'" zoom="3" width="600" height="450" lat="34.37" long="-88.375" ]' );
  	}
	}
  echo '<ul class="site-list">';
  foreach ( $site_list as $blog ) {
  	echo '<li>';
  	$site_contacts = get_site_contacts( $blog['blog_id'] );	
  	if ( $address == 'show') {
    	echo '<h4><a href="' . $site_contacts[$blog['blog_id']]['path'] .'">' . $site_contacts[$blog['blog_id']]['name'] . '</a></h4>';
     	echo '<address>' . $site_contacts[$blog['blog_id']]['street'] . '<br>';
   	  echo $site_contacts[$blog['blog_id']]['city'] . ', ' . $site_contacts[$blog['blog_id']]['state'] . ' ' . $site_contacts[$blog['blog_id']]['zip'] . '</address>';
   	  echo '<p><a href="tel:' . $site_contacts[$blog['blog_id']]['phone'] . '">' . $site_contacts[$blog['blog_id']]['phone'] . '</a></p>';
   	}
   	if ( $hours == 'show' ) {
      $site_hours = get_site_hours( $blog );
      echo '<dl>';
      $current_day = '';
      foreach ( $site_hours[$blog['blog_id']] as $hours_val ) {
       	$day = explode('_', $hours_val['name'] );
       	$title = explode( ' ', $hours_val['title'] );
      	if ( $day[1] == 'open' ) {
        	echo '<dt>' . $title[0] . '</dt>';
        	if ( $hours_val['value'] ) {
        		echo '<dd>' . $hours_val['value'];
        		$current_day = $day[0];
        	}
        	else {
        		echo '<dd>Closed</dd>';
        	}
        }
        elseif ( $day[1] == 'close' && $day[0] == $current_day ) {
        	echo ' - ' . $hours_val['value'] .'</dd>';
        }
        else {
        	echo '</dd>';
        }
      } // Hours sites loop
      /**/
      echo '</dl>';
	  }
   	echo '</li>';
   } // end sites loop
  echo '</ul>';	  
  $output = ob_get_clean();
  return $output;
}

/*
 * Update Marker Files
 */
function ssc_update_markers() {
	$site_list = get_blog_list( 0, 'all' );
	$current_blog = get_current_blog_id();
	$file = '/marker/marker.txt';
	$sitefile = '/marker/marker-' . $current_blog . '.txt'; 
	foreach( $site_list as $site ){
	  $site_options[$site['blog_id']]['phone'] = get_blog_option( $site['blog_id'], 'ssc_admin_contact_settings_phone' );
	  $site_options[$site['blog_id']]['street'] = get_blog_option( $site['blog_id'], 'ssc_admin_contact_settings_street' );
	  $site_options[$site['blog_id']]['city'] = get_blog_option( $site['blog_id'], 'ssc_admin_contact_settings_city' );
	  $site_options[$site['blog_id']]['state'] = get_blog_option( $site['blog_id'], 'ssc_admin_contact_settings_state' );
	  $site_options[$site['blog_id']]['zip'] = get_blog_option( $site['blog_id'], 'ssc_admin_contact_settings_zip' );
  }
  $header = "lat\tlon\ttitle\tdescription\ticon\ticonSize\ticonOffset\n";
  $data = $header;
  foreach ($site_options as $blog_id) {
	  $address = 'street=' . str_replace( ' ', '+', $blog_id['street'] ) . '&state=' . str_replace(' ', '+', $blog_id['state'] ) . '&postalcode=' . urlencode( $blog_id['zip'] );
	  $query = '&country=us&format=json&countrycodes=us&polygon=0&addressdetails=0&' . $address;
  	$output = json_decode( file_get_contents( 'http://nominatim.openstreetmap.org/search?' . $query ) );
	  if ( $output[0]->lat){
  	  $data .= $output[0]->lat;
  	  $data .= "\t";
    	$data .= $output[0]->lon;
	    $data .= "\t";
  	  $data .= $blog_id['city'] ;
	    $data .= "\t";
	    $data .= '<div class="map-location"><address>'.$blog_id['street'] . '<br>'.$blog_id['state'] .' ' . $blog_id['zip'] .'</address><a href="tel:'.$blog_id['phone'].'">'.$blog_id['phone'].'</a></div>';
  	  $data .= "\t";
	    $data .= get_stylesheet_directory_uri() . '/marker.png';
	    $data .= "\t";
  	  $data .= '30,30';
	    $data .= "\t";
	    $data .= '0,-15';
  	  $data .= "\n";
	    if ( $site['blog_id'] == $current_blog ) {
	    	$sitedate = $header;
	  	  $sitedata .= $output[0]->lat;
  	    $sitedata .= "\t";
      	$sitedata .= $output[0]->lon;
	      $sitedata .= "\t";
	      $sitedata .= $blog_id['city'] ;
  	    $sitedata .= "\t";
	      $sitedata .= '<div class="map-location"><address>'.$blog_id['street'] . '<br>'.$blog_id['state'] .' ' . $blog_id['zip'] .'</address><a href="tel:'.$blog_id['phone'].'">'.$blog_id['phone'].'</a></div>';//<img src="http://www.scottsawyerconsulting.com/sites/all/themes/sscblck/logo.png"></div>';
	      $sitedata .= "\t";
	      $sitedata .= get_stylesheet_directory_uri() . '/logo.png';
  	    $sitedata .= "\t";
	      $sitedata .= '30,30';
  	    $sitedata .= "\t";
	      $sitedata .= '0,-15';
  	    $sitedata .= "\n";
	    }
    }
  }
  $file_handle = fopen( $_SERVER['DOCUMENT_ROOT'] . $file, 'w' );
  fwrite( $file_handle, $data );
  fclose( $file_handle );
  $file_handle = fopen( $_SERVER['DOCUMENT_ROOT'] . $sitefile, 'w' );
  fwrite( $file_handle, $sitedata );
  fclose( $file_handle );
  return true;
}

/**
 * JS for options form
 */
function ssc_location_js () {
	?>
	<script type="text/javascript">
    jQuery(document).ready(function($){
    	data = { 'action': 'ssc_location_update_form' };
    	//$('form.ssc_location_contact #submit').addClass('contact_submit').removeAttr('id').removeAttr('name');

    	$('form.ssc_location_contact .location_submit').click( function( event ) {
    		data['form'] = 'form.ssc_location_contact';

    		event.preventDefault();
    		//alert( data['form']);
    		/*
    		var locationFields = new Array();
    		locationFields['street'] = $('.ssc_admin_location_settings_street').val();
    		locationFields['city'] = $('.ssc_admin_location_settings_city').val();
    		locationFields['state'] = $('.ssc_admin_location_settings_state').val();
    		locationFields['zip'] = $('.ssc_admin_location_settings_zip').val();
    		alert ( locationFields['street'] );
    		/**/
     		data['street'] = $('.ssc_admin_contact_settings_street').val();
    		data['city'] = $('.ssc_admin_contact_settings_city').val();
    		data['state'] = $('.ssc_admin_contact_settings_state').val();
    		data['zip'] = $('.ssc_admin_contact_settings_zip').val();
    		//alert( data['zip'] );
    		$('#wpwrap').addClass('progress');
    		getLocation( data );
    	});
    	function formSubmit ( data ) {
    		console.log( 'submit form' );
    		$('form.ssc_location_contact').submit();
    		/*
    		$( data['form'] ).submit( function() {
    			console.log('form submit');
    		});
/**/
    	}
    	function getLocation ( data ) {
    		
      	$.post( ajaxurl, data, function( response )  {
          if ( response ) {
          	
            var obj =  jQuery.parseJSON( response ); //response;
            console.log(obj.type);
            if ( obj.type == 'success' ) { 
            	alert( obj.message );
              //updateOptions( data, obj );
              //$('form.ssc_location').submit(); //$(data['form']).submit();
              //return true;
              formSubmit( data );
            }
            else {
              //alert( 'There are no ' + data['data']['type'] + ' please add some.' );
              alert( 'The address ' + data['street'] + ' ' + data['city'] + ' ' + data['state'] + ', ' + data['zip'] + ' could not be found.  Please check the address and be sure you entered it exactly as listed by the postal service. (HINT: Use Google Maps )' );
              $('#wpwrap').removeClass('progress');
              return false;
            }
          }
          else {
          	alert(  );
            return false;
          }
        });
      }
    });
  </script>
	<?php
}
function ssc_location_update_form_callback( ) {
	global $wpdb;
  $ret_val = array( 'type' => 'error', 'message' => 'Nothing received.');
  /*
  print '<pre>';
  print_r( $_POST );
  print '</pre>';
  echo $_POST['zip'];
  $posted = $_POST['data'];
  /**/
	$address = 'street=' . str_replace( ' ', '+', $_POST['street'] ) . '&state=' . str_replace(' ', '+', $_POST['state'] ) . '&postalcode=' . urlencode( $_POST['zip'] );
	$query = '&country=us&format=json&countrycodes=us&polygon=0&addressdetails=0&' . $address;
	$output = json_decode( file_get_contents( 'http://nominatim.openstreetmap.org/search?' . $query ) );
	if ( $output[0]->lat ){
  	update_option( 'ssc_admin_location_settings_lat', $output[0]->lat );
  	update_option( 'ssc_admin_location_settings_lon', $output[0]->lon );
  	if ( ssc_update_markers() == true ) {
  	  $ret_val['type'] = 'success';
  	  $ret_val['message'] = 'Your location has been added to the map.'; // Latitude = ' . $output[0]->lat . ' Longitude = ' . $output[0]->lon ;
    }
    else {
    	$ret_val['message'] = 'Could not validate.  Please try again.';
    }
  }
  else{
  	$ret_val['message'] = 'Could not validate address.';
  }
  echo json_encode($ret_val);
  die();
}
/* 
 * settings functions
 */
/*
function ssc_admin_settings_api_init() {
	global $ssc_options;
	$options = $ssc_options;
	foreach ( $options['settings'] as $settings_group ) {
			$group_name = $settings_group['group_name'];
			$group_title = $settings_group['group_title'];
			$group_section = $settings_group['group_section'];
			add_settings_section(
				'ssc_admin_' . $group_name . '_settings_section',
				$group_title,
				'ssc_admin_settings_section_callback',
				$group_section
				);			
			foreach ($settings_group['group_fields'] as $fields) {
				$args = array();
				$args = $fields;
				$args['group_name'] = $group_name;
				$args['group_section'] = $group_section;
				add_settings_field(
					'ssc_admin_' . $group_name . '_settings_' . $fields['name'],
					$fields['title'],
					'ssc_admin_settings_fields_callback',
					$group_section,
					'ssc_admin_' . $group_name . '_settings_section',
					$args
					);
				register_setting( $group_section, 'ssc_admin_' . $group_name . '_settings_' . $fields['name'] );
			}
	}
}
/**/
/*
function ssc_admin_settings_section_callback( $section_passed ) {
  echo '<p>' . $section_passed['title'] . '</p>';
}
function ssc_admin_settings_fields_callback( array $args ) {
  $field_name = 'ssc_admin_' . $args['group_name'] . '_settings_' .$args['name'];
	if ( 'text' == $args['type'] || 'time' == $args['type'] ){
		echo '<input type="text" name="' . $field_name . '" ';
		if ( get_option( $field_name ) ) {
			echo 'value="' . get_option( $field_name ) . '" ';
		}
		if ( 'time' == $args['type'] ) {
			echo 'class="time" ';
		}
		echo '/>';
	}
	if ( 'select' == $args['type'] ) {
		$options = array();
		echo '<select name="' . $field_name . '" >';
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
/**/

function ssc_us_states () {
// From https://www.usps.com/send/official-abbreviations.htm 

$us_state_abbrevs_names = array(
	'AL'=>'ALABAMA',
	'AK'=>'ALASKA',
	'AS'=>'AMERICAN SAMOA',
	'AZ'=>'ARIZONA',
	'AR'=>'ARKANSAS',
	'CA'=>'CALIFORNIA',
	'CO'=>'COLORADO',
	'CT'=>'CONNECTICUT',
	'DE'=>'DELAWARE',
	'DC'=>'DISTRICT OF COLUMBIA',
	'FM'=>'FEDERATED STATES OF MICRONESIA',
	'FL'=>'FLORIDA',
	'GA'=>'GEORGIA',
	'GU'=>'GUAM GU',
	'HI'=>'HAWAII',
	'ID'=>'IDAHO',
	'IL'=>'ILLINOIS',
	'IN'=>'INDIANA',
	'IA'=>'IOWA',
	'KS'=>'KANSAS',
	'KY'=>'KENTUCKY',
	'LA'=>'LOUISIANA',
	'ME'=>'MAINE',
	'MH'=>'MARSHALL ISLANDS',
	'MD'=>'MARYLAND',
	'MA'=>'MASSACHUSETTS',
	'MI'=>'MICHIGAN',
	'MN'=>'MINNESOTA',
	'MS'=>'MISSISSIPPI',
	'MO'=>'MISSOURI',
	'MT'=>'MONTANA',
	'NE'=>'NEBRASKA',
	'NV'=>'NEVADA',
	'NH'=>'NEW HAMPSHIRE',
	'NJ'=>'NEW JERSEY',
	'NM'=>'NEW MEXICO',
	'NY'=>'NEW YORK',
	'NC'=>'NORTH CAROLINA',
	'ND'=>'NORTH DAKOTA',
	'MP'=>'NORTHERN MARIANA ISLANDS',
	'OH'=>'OHIO',
	'OK'=>'OKLAHOMA',
	'OR'=>'OREGON',
	'PW'=>'PALAU',
	'PA'=>'PENNSYLVANIA',
	'PR'=>'PUERTO RICO',
	'RI'=>'RHODE ISLAND',
	'SC'=>'SOUTH CAROLINA',
	'SD'=>'SOUTH DAKOTA',
	'TN'=>'TENNESSEE',
	'TX'=>'TEXAS',
	'UT'=>'UTAH',
	'VT'=>'VERMONT',
	'VI'=>'VIRGIN ISLANDS',
	'VA'=>'VIRGINIA',
	'WA'=>'WASHINGTON',
	'WV'=>'WEST VIRGINIA',
	'WI'=>'WISCONSIN',
	'WY'=>'WYOMING',
	'AE'=>'ARMED FORCES AFRICA \ CANADA \ EUROPE \ MIDDLE EAST',
	'AA'=>'ARMED FORCES AMERICA (EXCEPT CANADA)',
	'AP'=>'ARMED FORCES PACIFIC'
	);
  return $us_state_abbrevs_names;
}
global $ssc_location_options;
$ssc_location_options = array(
  'settings' => array(
    array(
      'group_name' => 'contact',
      'group_title' => 'Contact',
      'group_description' => 'Enter contact information.',
      'group_section' => 'ssc_location_contact',
      'group_fields' => array(
        array(
          'name' => 'phone',
          'title' => 'Phone Number',
          'type' => 'text',
          ),
        array(
          'name' => 'street',
          'title' => 'Street',
          'type' => 'text',
          ),
        array(
          'name' => 'city',
          'title' => 'City',
          'type' => 'text',
          ),
        array(
          'name' => 'zip',
          'title' => 'ZIP',
          'type' => 'text',
          ),
        array(
          'name' => 'state',
          'title' => 'State',
          'type' => 'select',
          'options' => 'us_state_abbrevs_names',
          ),
        /*
        array(
          'name' => 'lat',
          'title' => 'Latitude',
          'type' => 'hidden',
          ),
        array(
          'name' => 'lon',
          'title' => 'Longitude',
          'type' => 'hidden',
          ),
/**/
        ),
      ),
    array(
      'group_name' => 'socialmedia',
      'group_title' => 'Social Media',
      'group_description' => 'Add social media links.',
      'group_section' => 'ssc_location_socialmedia',
      'group_fields' => array(
        array(
          'name' => 'facebook',
          'title' => 'Facebook',
          'type' => 'text',
          ),
        array(
          'name' => 'twitter',
          'title' => 'Twitter',
          'type' => 'text',
          ),
        array(
          'name' => 'linkedin',
          'title' => 'LinkedIn',
          'type' => 'text',
          ),
        array(
          'name' => 'google',
          'title' => 'Google Plus',
          'type' => 'text',
          ),
        array(
          'name' => 'youtube',
          'title' => 'YouTube',
          'type' => 'text',
          ),
        array(
          'name' => 'flickr',
          'title' => 'Flickr',
          'type' => 'text',
          ),
        ),
      ),    
    array(
      'group_name' => 'business_hours',
      'group_title' => 'Business Hours',
      'group_description' => 'Enter the business hours',
      'group_section' => 'ssc_location_business_hours',
      'group_fields' => array(
        array(
          'name' => 'monday_open',
          'title' => 'Monday Open',
          'type' => 'time',
          ),
        array(
          'name' => 'monday_close',
          'title' => 'Monday Close',
          'type' => 'time',
          ),
        array(
          'name' => 'tuesday_open',
          'title' => 'Tuesday Open',
          'type' => 'time',
          ),
        array(
          'name' => 'tuesday_close',
          'title' => 'Tuesday Close',
          'type' => 'time',
          ),
        array(
          'name' => 'wednesday_open',
          'title' => 'Wednesday Open',
          'type' => 'time',
          ),
        array(
          'name' => 'wednesday_close',
          'title' => 'Wednesday Close',
          'type' => 'time',
          ),
        array(
          'name' => 'thursday_open',
          'title' => 'Thursday Open',
          'type' => 'time',
          ),
        array(
          'name' => 'thursday_close',
          'title' => 'Thursday Close',
          'type' => 'time',
          ),
        array(
          'name' => 'friday_open',
          'title' => 'Friday Open',
          'type' => 'time',
          ),
        array(
          'name' => 'friday_close',
          'title' => 'Friday Close',
          'type' => 'time',
          ),
        array(
          'name' => 'saturday_open',
          'title' => 'Saturday Open',
          'type' => 'time',
          ),
        array(
          'name' => 'saturday_close',
          'title' => 'Saturday Close',
          'type' => 'time',
          ),
        array(
          'name' => 'sunday_open',
          'title' => 'Sunday Open',
          'type' => 'time',
          ),
        array(
          'name' => 'sunday_close',
          'title' => 'Sunday Close',
          'type' => 'time',
          ),
        ),
      ),
    ),
  );
?>