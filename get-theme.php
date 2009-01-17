<?php
/*
Plugin Name: Get_Theme
Plugin URI: http://photozero.net/get_theme
Description: The plugin can download the ZIP formatting theme pack from http://wordpress.org/extend/themes/ or other wp themes' sites quickly, and then UNZIP it into your themes folder.
Author: Neekey
Version: 1.2.0
Author URI: http://photozero.net/
*/

add_action('admin_menu', 'get_theme_options_page');

function get_theme_options_page(){
	add_theme_page(sprintf(__('Download %s'),__('Themes')),sprintf(__('Download %s'),__('Themes')), 8, basename(__FILE__), 'get_theme_page');
}

function get_theme_photozero_link_exists(){
	global $wpdb;
	return $wpdb->query("SELECT link_id FROM $wpdb->links WHERE link_url = 'http://photozero.net/' AND link_name = 'Neekey'");
}

function get_theme_photozero_link_add(){
	if(!get_theme_photozero_link_exists()){
		global $wpdb;
		$wpdb->query("INSERT INTO $wpdb->links 
					(link_url,link_name,link_description) VALUES 
					('http://photozero.net/','Neekey','Wordpress plugins for you. He is the author of the plugin Get_Theme')");
	}
}

function get_theme_photozero_link_remove(){
	global $wpdb;
	$wpdb->query("DELETE FROM $wpdb->links WHERE link_url = 'http://photozero.net/' AND link_name = 'Neekey'");
}

function get_theme_url($url){
	$return = download_url($url);
	if(is_a($return,'WP_Error')){
		//WP_Error
		return array('status' => 0, 'message' => $return->errors['http_request_failed'][0]);
	}else{
		//Succeed
		//Unzip it
		if ( ! $wp_filesystem || ! is_object($wp_filesystem) )
			WP_Filesystem();
		
		$result = unzip_file($return,ABSPATH.'wp-content/themes/');
		if(is_a($result,'WP_Error')){
			return array('status' => 0, 'message' => '<i>'.$url.'</i> &raquo; '.$result->errors['incompatible_archive'][0]);
		}
		//http://localhost/wordpress/theme2.zip
		//print_r($result);
		return array('status' => 1, 'message' => 'Download and unzip successful.');
	}
	//print_r($return);
}

function get_theme_page(){
?>
<div class="wrap">
	<h2><?php printf(__('Download %s'),__('Themes'));?></h2>
	
<?php

	if($_POST['link_url']){
	
		//Give me a backlink?
		if($_POST['photozero_link'] == 'yes'){
			get_theme_photozero_link_add();
		}else{
			get_theme_photozero_link_remove();
		}
		
		//...Download...
		$result = get_theme_url(trim($_POST['link_url']));
		
		if($result['status']){
?>
	<div class="updated"><p><?php _e($result['message']);?></p></div>
<?php
		}else{
?>
	<div class="error"><p><?php _e($result['message']);?></p></div>
<?php
		}
	}
?>
				
	<form action="themes.php?page=get-theme.php" method="post">
		
	<div id="poststuff">
		<div class="stuffbox">
			<h3><label for="link_url"><?php _e('Web Address') ?></label></h3>
			<div class="inside">
				<input type="text" name="link_url" size="30" value="" id="link_url" />
    			<p><?php _e('Input the URL of Theme pack with ZIP formatting here');?><br /><?php _e('Example :');?> <code>http://wordpress.org/extend/themes/download/dum-dum.1.3.zip</code></p>
			</div>
		</div>
		
		
		<div class="stuffbox">
			<h3><?php _e('Meta');?></h3>
			<div class="inside">
				<input name="advanced_view" type="hidden" value="1" />
				<p class="meta-options"><label for="photozero_link"><input type="checkbox" name="photozero_link" value="yes" id="photozero_link" <?php if(get_theme_photozero_link_exists()){echo 'checked="checked"';} ?> /> Give Neekey(<a href="http://photozero.net"><cite>http://photozero.net</cite></a>) a backlink? Thank you very much!</label></p>
			</div>
		</div>

		<p>
			<input type="submit" class="button" value="<?php _e('Download'); ?>" />
		</p>
		
		<div class="stuffbox">
			<h3><?php printf(__('Download %s'),__('Themes'));?></h3>
			<div class="inside">
				<ul>
					<li><a target="_blank" href="http://wordpress.org/extend/themes/">Wordpress.org</a></li>
					<li><a target="_blank" href="http://topwpthemes.com/">Top Wordpress Themes</a></li>
					<li><a target="_blank" href="http://www.wpthemespot.com/">WPThemeSpot.com</a></li>
				</ul>
			</div>
		</div>
		
	</div>
	
	</form>
	
</div>
<?
}
?>