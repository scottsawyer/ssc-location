<?php
/*
 * Marker file, returns all of the markers from all of the domains
 */
//ob_clean();
//flush();
//readfile( './marker.txt' );
define( 'WP_USE_THEMES', false );
//require( '../../../../wp-blog-header.php' );
require( '../wp-blog-header.php' );
if ( array_key_exists( 'sites', $_REQUEST ) ) {
	$requested_site = $_REQUEST['sites'];
	if ( $requested_site != 'all' ) {
		$site_list = array( 0 => array( 'blog_id' => $requested_site ) );
		$file = '/marker/marker-' . $requested_site . '.txt';
	}
	else {
		$site_list = get_blog_list( 0, 'all' );
		$file = '/marker/marker.txt';
	}
}
else {
  $site_list = get_blog_list( 0, 'all' );
  $file = '/marker/marker.txt';
}

header( "location:" . $pageURL . $file );
exit;
?>