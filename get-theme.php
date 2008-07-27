<?php
/*
Plugin Name: Get_Theme
Plugin URI: http://photozero.net/get_theme
Description: The plugin can download the ZIP formatting theme pack from http://wordpress.org/extend/themes/ or other wp themes' sites quickly, and then UNZIP it into your themes folder.
Author: Neekey
Version: 1.0.0
Author URI: http://photozero.net/
*/

if ( !defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
$plugin_path = WP_CONTENT_DIR.'/plugins/';
$theme_path = WP_CONTENT_DIR.'/themes/';



function display_status(){
	if(!empty($_POST['gettheme'])){
		$downloadtime = substr($GLOBALS['get_theme']['downloadtime'],0,5);
		$unziptime = substr($GLOBALS['get_theme']['unziptime'],0,5);
		$file = $GLOBALS['get_theme']['file'];
		switch ($GLOBALS['get_theme']['status']) {
			case 1:
				$status = "Theme downloaded successful. <br /><i>Download processed in $downloadtime second ,and Unzip processed in $unziptime second.</i><br />ZIP file $file";
				break;
			case 2:
				$status = 'The download file does NOT exist.';
				break;
			case 3:
				$status = 'Cannot unzip the file';
				break;
			case 4:
				$status = 'It is NOT a standard Wordpress Theme ZIP file';
				break;
			case 5:
				$status = 'The theme has been installed or the ZIP file is NOT a standard Wordpress Theme ZIP file';
				break;
			case 6:
				$status = 'Wordpress System Error!';
				break;
			default:
				$status = 'Sorry. Theme downloaded failure.';
		}
	
?><div id="message" class="updated fade">
<p>
<strong><?php echo $status;?></strong>
</p>
</div><?php }
}


function check_download(){
	if($_POST['gettheme'] == 'download'){
		
		global $theme_path;
		
		$theme_before = only_dir(get_dir_list($theme_path));// return like  array( 0 => classic , 1 => default);
		
		$download_return = download_theme();//Try to download the file and unzip it.
		
		
		$GLOBALS['get_theme']['downloadtime'] = $download_return['downloadtime'];
		$GLOBALS['get_theme']['unziptime'] = $download_return['unziptime'];
		$GLOBALS['get_theme']['file'] = get_bloginfo('url') .'/'. get_option('upload_path') .'/'.$download_return['file'];
		
		if($download_return['status'] === true){
			
			$theme_after = only_dir(get_dir_list($theme_path));
			$theme_name_new = array_diff($theme_after,$theme_before);
			$theme_count = count($theme_name_new);
			$theme_name_new = array_values($theme_name_new);
			
			
			if($theme_count>1){
				$theme_install = 0;
				foreach($theme_name_new as $theme_name){
					if(file_exists($theme_path. $theme_name .'/style.css')){
						$theme_install++;
					}
				}
				if($theme_install>=1){
					$GLOBALS['get_theme']['status'] = 1;
				}else{
					$GLOBALS['get_theme']['status'] = 4;
				}
			}elseif($theme_count == 1){
				if(file_exists($theme_path. $theme_name_new[0] .'/style.css')){
					$GLOBALS['get_theme']['status'] = 1;
				}else{
					$GLOBALS['get_theme']['status'] = 4;
				}
			}else{
				$GLOBALS['get_theme']['status'] = 5;
			}
			
			
		}elseif($download_return['status'] == 2){
			$GLOBALS['get_theme']['status'] = 2;
			
		}elseif($download_return['status'] == 3){
			$GLOBALS['get_theme']['status'] = 3;
			
		}else{
			$GLOBALS['get_theme']['status'] = 6;
			
		}
	}
}


function download_theme(){

		
		global $theme_path,$wp_filesystem;
		require_once(ABSPATH . 'wp-admin/includes/admin.php');
		require_once(ABSPATH . 'wp-includes/classes.php');
		
		if ( ! $wp_filesystem || !is_object($wp_filesystem) )	WP_Filesystem();
		
		
		$requesturl = trim($_POST['themeurl']);
		$filename =  basename($requesturl);
		$uploaddir = ABSPATH . '/' .get_option('upload_path') . '/' . $filename ;
		
		
		
		//Download ZIP file
		$download_time_start=explode(" ",microtime());
		$file = download_url($requesturl);
		if ( is_wp_error($file) )
			return array('status' => 2,'downloadtime' => $download_time,'unziptime' => $unzip_time,'file' => $filename);
		$download_time_end=explode(" ",microtime());
		$download_time=$download_time_end[0]+$download_time_end[1]-$download_time_start[0]-$download_time_start[1];
		//------------------------
		
		
		
		$base = $wp_filesystem->get_base_dir();
		if ( empty($base) )
			return array('status' => 6,'downloadtime' => $download_time,'unziptime' => $unzip_time,'file' => $filename);
		$working_dir = $base . 'wp-content/themes';
		
		
		//UNZIP the pack
		$unzip_time_start=explode(" ",microtime());
		
		
		
		
		$unzip_result = unzip($file, $working_dir);

		
		$unzip_time_end=explode(" ",microtime());
		$unzip_time=$unzip_time_end[0]+$unzip_time_end[1]-$unzip_time_start[0]-$unzip_time_start[1];
		//------------------------
		
		//Move it to the folder  wp-content/uploads/ 
		if(file_exists($uploaddir)){
			unlink($uploaddir);
		}
		rename($file,$uploaddir);
		
		
		if(!$file){
			return array('status' => 2,'downloadtime' => $download_time,'unziptime' => $unzip_time,'file' => $filename);
		}elseif(!$unzip_result){
			return array('status' => 3,'downloadtime' => $download_time,'unziptime' => $unzip_time,'file' => $filename);
		}else{
			return array('status' => true,'downloadtime' => $download_time,'unziptime' => $unzip_time,'file' => $filename);
		}
}



function unzip($file, $to) {
	
	global $wp_filesystem,$theme_path;
	$fs =& $wp_filesystem;
	require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');
	
	$archive = new PclZip($file);
	
	if ( false == ($archive_files = $archive->extract(PCLZIP_OPT_EXTRACT_AS_STRING)) ){
		return false;
	}
	if ( 0 == count($archive_files) ){
		return false;
	}
	
	// Create PATH
	$to = trailingslashit($to);
	$path = explode('/', $to);
	for ( $j = 0; $j < count($path) - 1; $j++ ) {
		$tmppath .= $path[$j] . '/';
		if ( ! $fs->is_dir($tmppath) ) $fs->mkdir($tmppath, 0777);
	}
	
	foreach ($archive_files as $file) {
		$path = explode('/', $file['filename']);
		$tmppath = '';
		
		$complete_path = dirname($theme_path . $file['filename']);
		$complete_name = $theme_path . $file['filename'];

		if(!file_exists($complete_path)) {
			$tmp = '';
			foreach(explode('/',$complete_path) AS $k) {
				$tmp .= $k.'/';
				if(!file_exists($tmp)) {
					mkdir($tmp, 0777);
				}
			}
		}
		
		$fh = @fopen($complete_name, 'w');
		@fwrite($fh,$file['content']);
		@fclose($fh);	
	}
	return true;
}


function display_get_theme() {
	if (function_exists('add_options_page')) {
		add_options_page('Get_Theme', 'Get_Theme', 'manage_options', 'get-theme/startpage.php') ;
	}
}


function get_dir_list($path){
	$handle = @opendir($path);
	while (false !== ($file = readdir($handle))) {
		if ($file != "." && $file != "..") {
			$arr[] = $file;
		}
	}
    closedir($handle);
	return $arr;
}


function only_dir($arr){
	foreach($arr as $key){
		if(strpos($key,'.') === false)
			$new_arr[] = $key;
	}
	return $new_arr;
}


function display_in_dashboard(){
	_e('<p class="youhave">One click download &amp; unzip the Wordpress Theme ZIP pack to your blog by the plugin <a href="options-general.php?page=get-theme/startpage.php"><b>Get_Theme</b></b></p>');
}


add_action('init', 'check_download');
add_action('admin_menu', 'display_get_theme');
add_action('activity_box_end','display_in_dashboard');

?>