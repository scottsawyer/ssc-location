<?php
/*
 * Widget Class
 */
if(!class_exists('SSC_Locations_Widget')) {
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
    		  	echo '<li>';
    		  	if ($instance['site_list'] == 'all' ) {
    		  		echo '<h4><a href="' . get_blog_details( $blog['blog_id'])->path;
    		  		echo '">';
    		  		echo get_blog_details( $blog['blog_id'])->blogname;
    		  		echo '</a><h4>';
    		  	}
    		  	echo '<ul class="socialmedia">';
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
}
?>