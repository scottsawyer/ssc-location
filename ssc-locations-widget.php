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

add_action( 'widgets_init', create_function('', 'return register_widget("SSC_Locations_Widget");') );
add_shortcode( 'location', 'ssc_location_shortcode' );
add_action( 'admin_init', 'ssc_admin_settings_api_init' );

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

class SSC_Locations_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'SSC_Locations_Widget', // Base ID
			__('Locations Widget', 'text_domain'), // Name
			array( 'description' => __('Display location settings', 'text_domain'), ) // args
			);
	}

  public function widget( $args, $instance ) {
  	$title = apply_filters( 'widget_title', $instance['title'] );
  	if ( $instance['site_list'] == 'all' ){
  		$sites_list = ssc_get_blog_list( array( 'offset' => 0, 'limit' => 'all' ) );  //get_blog_list( 0, 'all' ); //ssc_get_blog_list( array( 'offset' => 0, 'limit' => 'all' ) ;
  	}
  	else {
  		$sites_list = array( array( 'blog_id' => $instance['site_list'] ) );
  	}
  	echo $args['before_widget'];
  	if ( $title ) {
  		echo $args['before_title'] . $title . $args['after_title'];
  	}
  	
  	switch ( $instance['location_show_option'] ) {
  		case 'address':
  			//$site_options = get_site_contacts( $sites_list );
  			echo '<ul class="site-list">';
        foreach ( $sites_list as $blog ) {
  	      echo '<li>';
  	      $site_contacts = get_site_contacts( $blog['blog_id'] );	
  	      echo '<h4><a href="' . $site_contacts[$blog['blog_id']]['path'] .'">' . $site_contacts[$blog['blog_id']]['name'] . '</a></h4>';
   	      echo '<address>' . $site_contacts[$blog['blog_id']]['street'] . '<br>';
   	      echo $site_contacts[$blog['blog_id']]['city'] . ', ' . $site_contacts[$blog['blog_id']]['state'] . ' ' . $site_contacts[$blog['blog_id']]['zip'] . '</address>';
   	      echo '<p><a href="tel:' . $site_contacts[$blog['blog_id']]['phone'] . '">' . $site_contacts[$blog['blog_id']]['phone'] . '</a></p>';
   	      if ( $instance['location_hours'] == 'show' ) {
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
            }
      /**/
            echo '</dl>';
   	      }
    	    echo '</li>';
        }
        echo '</ul>';
  			break;
  		case 'socialmedia':
  		  echo '<ul class="site-list">';
  		  foreach ($sites_list as $blog ) {
  		  	$site_options = get_site_socialmedia( $blog );
  		  	if ( is_array( $site_options )) {
    		  	echo '<li><ul class="socialmedia">';
  		  	/*
  		  	print '<pre>';
  		  	print_r($site_options);
  		  	print_r( $blog );
  		  	print '</pre>';
  		  	/**/
    		  	foreach ($site_options[$blog['blog_id']] as $key => $value) {
    		  		if ( is_array( $value ) ) {
    		  			echo '<li><a href="' . $value['value'] . '" class="' . $value['name'] . '">' . $value['title'] . '</a></li>';
    		  		}
  	  	  	}
  		    	echo '</ul></li>';
  		    }
  		}
  	    echo '</ul>';
  	    break;
  	    /**/
  	}

    echo $args['after_widget'];
  }
   	public function form( $instance ) {
		  if ( isset ( $instance['title'])) {
		  	$title = $instance['title'];
		  }
		  else {
		  	$title = __( 'Locations', 'text_domain' );
		  }
		  if ( isset ( $instance['location_show_option'] ) ) {
		  	$location_show_option = $instance['location_show_option'];
		  }
		  else {
		  	$location_show_option = 'address';
		  }
		  if ( isset ( $instance['location_hours'] ) ) {
		  	$location_hours = $instance['location_hours'];
		  }

		  echo '<p><label for="' . $this->get_field_id( 'title' ) . '">';
		  _e( 'Title:' );
		  echo '</label>';
		  echo '<input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' .$this->get_field_name( 'title' ) . '" type="text" value="' . esc_attr( $title ) .'" /></p>';
		  echo '<p><input type="radio" id="' . $this->get_field_id( 'location_show_option' ) . '" name="' . $this->get_field_name( 'location_show_option' ) . '" type="radio" value="address" ';
		  if ( $location_show_option == 'address' ){
		  	echo 'checked';
		  }
		  echo '><label for="' . $this->get_field_name( 'location_show_option' ) . '">Address</label>';
		  echo ' <span class="ssc-show-hours"><input type="checkbox" name="' . $this->get_field_name( 'location_hours' ) .'" id="' . $this->get_field_id( 'location_hours' ) . '" value="show"';
		  if ( $location_hours == 'show' ) {
		  	echo 'checked';

		  }
		  echo '><label for="' . $this->get_field_id( 'location_hours' ) . '" ';
		  echo '>Show Hours</label></p>';
		  echo '<p><input type="radio" id="' . $this->get_field_id( 'location_show_option' ) . '" name="' . $this->get_field_name( 'location_show_option' ) . '" type="radio" value="socialmedia" ';
		  if ( $location_show_option == 'socialmedia' ){
		  	echo 'checked';
		  }
		  echo '>Social Media</p>';
		  echo '<p><select id="' . $this->get_field_id( 'site_list' ) . '" name="' . $this->get_field_name( 'site_list' ) . '">';
		  echo '<option value="all" ';
		  if ( $instance['site_list'] == 'all' ) {
		  	echo 'selected';
		  }
		  echo '>All</option>';
		  $sites_list = ssc_get_blog_list( array( 'offset' => 0, 'limit' => 'all' ) );  //get_blog_list( 0, 'all' ); //ssc_get_blog_list( array( 'offset' => 0, 'limit' => 'all' ) ); //get_blog_list( 0, 'all' ); //ssc_get_blog_list( array( 'offset' => 0, 'limit' => 'all' ) );
		  foreach ( $sites_list as $sites ) {
		  	echo '<option value="' . $sites['blog_id'] . '" ';
		  	if ( $instance['site_list'] == $sites['blog_id'] ) {
		  		echo 'selected';
		  	}
		  	echo '>' . get_blog_details( $sites['blog_id'])->blogname . '</option>';
		  	# code...
		  }
		  echo '</select></p>';



	}
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( !empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['location_show_option'] = ( !empty( $new_instance['location_show_option'] ) ) ? strip_tags( $new_instance['location_show_option'] ) : 'address';
		$instance['site_list'] = ( !empty( $new_instance['site_list'] ) ) ? strip_tags( $new_instance['site_list'] ) : 'all';
		$instance['location_hours'] = ( !empty( $new_instance['location_hours'])) ? strip_tags( $new_instance['location_hours'] ) : '';
		return $instance;
	}
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
    $site_options[$site['blog_id']]['street'] = get_blog_option( $site['blog_id'], 'ssc_admin_location_settings_street' );
    $site_options[$site['blog_id']]['city'] = get_blog_option( $site['blog_id'], 'ssc_admin_location_settings_city' );
    $site_options[$site['blog_id']]['state'] = get_blog_option( $site['blog_id'], 'ssc_admin_location_settings_state' );
    $site_options[$site['blog_id']]['zip'] = get_blog_option( $site['blog_id'], 'ssc_admin_location_settings_zip' );
    $site_options[$site['blog_id']]['phone'] = get_blog_option( $site['blog_id'], 'ssc_admin_location_settings_phone' );
    $site_options[$site['blog_id']]['path'] = get_blog_details( $site['blog_id'])->path;
  }
  return $site_options;
}
/*
 * Get Social Media
 */
function get_site_socialmedia( $site_list ) {
	global $ssc_options;
	$options = $ssc_options;
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
	global $ssc_options;
	$options = $ssc_options;
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
	  echo do_shortcode( '[osm_map marker_file="'.$pageURL.'/marker/marker.php?sites='.$sites.'" zoom="3" width="600" height="450" lat="34.37" long="-88.375" ]' );
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
 * settings functions
 */

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
?>