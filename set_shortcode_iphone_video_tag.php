<?php
/**
 * @package Set shortcode for iPhone video-tag
 * @author Frank B&uuml;ltge
 */
/*
Plugin Name: Set shortcode for iPhone video-tag
Plugin URI: http://bueltge.de/
Text Domain: SSIVT_TEXTDOMAIN
Domain Path: /languages
Description: Convert video-html-tag into shortcode for WordPress API and convert this for Frontend with a player
Author: Frank B&uuml;ltge
Version: 0.0.4
Author URI: http://bueltge.de/
Donate URI: http://bueltge.de/wunschliste/
License: GPL
Last change: 02.03.2010 13:07:29
*/ 
/**
License:
==============================================================================
Copyright 2009/2010 Frank Bueltge  (email : frank@bueltge.de)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

Requirements:
==============================================================================
This plugin requires WordPress >= 2.7 and tested with PHP Interpreter >= 5.2.9
*/
//avoid direct calls to this file, because now WP core and framework has been used
if ( !function_exists('add_action') ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
} elseif ( version_compare(phpversion(), '5.0.0', '<') ) {
	$exit_msg = 'The plugin require PHP 5 or newer';
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit($exit_msg);
}

if ( !class_exists('SetShortcodeIphoneVideoTag') ) {
	
	//WordPress definitions
	if ( !defined('WP_CONTENT_URL') )
		define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
	if ( !defined('WP_CONTENT_DIR') )
		define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
	if ( !defined('WP_PLUGIN_URL') )
		define('WP_PLUGIN_URL', WP_CONTENT_URL.'/plugins');
	if ( !defined('WP_PLUGIN_DIR') )
		define('WP_PLUGIN_DIR', WP_CONTENT_DIR.'/plugins');
	if ( !defined('PLUGINDIR') )
		define( 'PLUGINDIR', 'wp-content/plugins' ); // Relative to ABSPATH.  For back compat.
	if ( !defined('WP_LANG_DIR') )
		define('WP_LANG_DIR', WP_CONTENT_DIR . '/languages');
	
	class SetShortcodeIphoneVideoTag {
		
		/*
		 * Key for textdomain
		 * 
		 * @var string
		 */
		protected $textdomain =  'SSIVT_TEXTDOMAIN';
		
		/**
		 * Constructor
		 */
		public function __construct() {
			
			add_action( 'admin_init', array(&$this, 'text_domain') );
			// on activation of the plugin add cap to roles
			register_activation_hook( __FILE__, array(&$this, 'on_activate') );
			
			add_filter( 'wp_insert_post_data', array(&$this, 'set_post_video_shorttag'), 10, 2 );
			add_shortcode( 'video', array(&$this, 'shortcode_video') );
		}
		
		/**
		 * Load language file for WPLANG
		 */
		public function text_domain() {
			
			load_plugin_textdomain( $this->textdomain, false, dirname( plugin_basename(__FILE__) ) . '/languages' );
		}
		
		/**
		 * return plugin comment data
		 * 
		 * @since 1.2.1
		 * @param $value string, default = 'Version'
		 *        Name, PluginURI, Version, Description, Author, AuthorURI, TextDomain, DomainPath, Network, Title
		 * @return string
		 */
		private function get_plugin_data( $value = 'Version' ) {
			
			$plugin_data = get_plugin_data( __FILE__ );
			$plugin_value = $plugin_data[$value];
			
			return $plugin_value;
		}
		
		
		/**
		 * on activate plugin
		 */
		public function on_activate() {
			global $wp_roles, $wp_version;
			
			// check wp version
			if ( !version_compare( $wp_version, '3.0', '>=') ) {
				deactivate_plugins(__FILE__);
				die( 
					wp_sprintf( 
						'<strong>%s:</strong> ' . 
						__( 'Sorry, This plugin requires WordPress 3.0+', $this->textdomain )
						, self::get_plugin_data('Name')
					)
				);
			}
			
			// check php version
			if ( version_compare(PHP_VERSION, '5.0.0', '<') ) {
				deactivate_plugins(__FILE__); // Deactivate ourself
				die( 
					wp_sprintf(
						'<strong>%1s:</strong> ' . 
						__( 'Sorry, This plugin has taken a bold step in requiring PHP 5.0+, Your server is currently running PHP %2s, Please bug your host to upgrade to a recent version of PHP which is less bug-prone. At last count, <strong>over 80%% of WordPress installs are using PHP 5.2+</strong>.', $this->textdomain )
						, self::get_plugin_data('Name'), PHP_VERSION 
					)
				);
			}
		}
		
		/**
		 * parse content and replace tag to shortcode
		 */
		public function set_post_video_shorttag($data, $postarr) {
			
			$data['post_content'] = stripslashes( $data['post_content'] );
			
			// test string - only for atribbutes with ""
			// $ausgangswert = '<video src="http://20101121-131236.mov" controls="controls" width="480" height="360" img-url="http://imagelink">Your browser does not support the video tag</video>';
			/**
			$filmurl = preg_replace('/^.*src="?([^\s"]+)"?.*$/ims', '$1', $data['post_content']);
			$posterurl = preg_replace('/^.*img-url="?([^\s"]+)"?.*$/ims', '$1', $data['post_content']);
			$data['post_content'] = '[video'.
				($filmurl != '' && $filmurl != $data['post_content'] ? " filmurl='".$filmurl."'" : '')
				.($posterurl != '' && $posterurl != $data['post_content'] ? " posterurl='"
				.$posterurl."'" : '')
				.']';
			 * */
			// test string 2, also with '' and ""
			//$ausgangswert = '<video src=\'http://20101121-131236.mov\' controls="controls" width="480" height="360" img-url=\'http://imagelink\'>Your browser does not support the video tag</video>';
			$filmurl = preg_replace('/^.*src=["\']?([^\s"\']+)["\']?.*$/ims','$1',$data['post_content']);
			$posterurl = preg_replace('/^.*img-url=["\']?([^\s"\']+)["\']?.*$/ims','$1',$data['post_content']);
			$data['post_content'] = '[video'
				.($filmurl != '' && $filmurl != $data['post_content'] ? " filmurl='".$filmurl."'" : '')
				.($posterurl != '' && $posterurl != $data['post_content'] ? " posterurl='"
				.$posterurl."'" : '')
				.']';
			
			$data['post_content'] = addslashes( $data['post_content'] );
			
			return $data;
		}
		
		/**
		 * Shortcode
		 *
		 * @author Marcus Zeeh
		 */
		public function shortcode_video($attr, $content) {
			
				$player_markup = '<div class="media-player">
														<video poster="'.$attr['posterurl'].'" controls="controls">
																<source src="'.$attr['filmurl'].'" type="video/mp4" />
																<div class="fallback">
																	<a class="source" href="'.$attr['filmurl'].'" type="video/mp4">
																		<img src="'.$attr['posterurl'].'" alt="Tron Trailer" />
																	</a>
																</div> 
														</video> 
														<div class="media-state"></div> 
														<div class="media-controls">
																<div class="media-controls-box">
																	<a class="play-pause btn" title="play / pause"><span class="ui-icon"> </span><span class="button-text">play / pause</span></a>
																	<div class="media-bar">
																		<div class="timeline-slider">
																			<span class="handle-label">play position</span>
																			<span class="ui-slider-handle" title="play position"></span>
																			<div class="progressbar"></div>
																		</div>
																		<span class="time-display">
																			<span class="current-time" title="current position">00:00</span> / <span class="duration" title="duration">00:00</span>
																		</span>
																		<div class="volume-slider"><span class="handle-label">volume control</span><span class="ui-slider-handle" title="volume control"></span></div>
																		<a class="fullscreen btn" title="zoomin / zoomout"><span class="ui-icon"> </span><span class="button-text">zoomin / zoomout</span></a>
																	</div>
																</div>
														</div>
											</div>
											<script>
												if(location.protocol === "file:"){
													$("")
														.css({opacity: 0})
														.insertAfter("div.media-player")
														.fadeTo(1000, 1)
														.delay(9000)
														.fadeOut(1400, function(){
																	$(this).remove();
														})
													;
												}
											</script>
							';
				
			return $player_markup;
		}
	
	} // end class
	
	function set_shortcode_iphone_video_tag_start() {
	
		new SetShortcodeIphoneVideoTag();
	}
	add_action( 'plugins_loaded', 'set_shortcode_iphone_video_tag_start' );
	
}
?>