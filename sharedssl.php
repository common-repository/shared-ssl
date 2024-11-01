<?php
/*
Plugin Name: Shared SSL
Plugin URI: http://pledgie.com/campaigns/8660
Description: Fix Wordpress Behavior for using Shared SSL. also include Contact Form 7 Fix
Author: Daishin Doi
Version: 1.0
Author URI: http://dd-web-memo.blogspot.com/
*/

/*  Copyright 2010 Daishin Doi (email: ddaishin at gmail.com)

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
*/

add_filter('activate_sharedssl/wp-sharedssl.php', 'wpsssl_activate_plugin');
add_filter('deactivate_sharedssl/wp-sharedssl.php', 'wpsssl_deactivate_plugin');
add_filter('plugins_loaded', 'wpsssl_plugin_loaded');

/* L10N */
add_filter( 'init', 'wpsssl_load_plugin_textdomain' );
function wpsssl_load_plugin_textdomain() {
	load_plugin_textdomain( 'wpsssl', false, 'sharedssl/languages' );
}

function wpsssl_plugin_loaded() {
	$wpsssl_opt_name = 'wpsssl_sharedssl_url';
	$wpsssl_opt_val = get_option($wpsssl_opt_name);
	
	if ($wpsssl_opt_val &&
		($urls = @parse_url($wpsssl_opt_val))) {
			
			if (!array_key_exists('port', $urls) && $urls['scheme'] == 'https') {
				$urls['port'] = 443;
			}
			
			if ($_SERVER['SERVER_PORT'] == $urls['port']) {
				remove_filter('template_redirect', 'redirect_canonical');
				add_filter('bloginfo_url', 'wpsssl_alter_url');
				add_filter('plugins_url', 'wpsssl_alter_url');
				add_filter('template_directory_uri', 'wpsssl_alter_url');
				
				add_filter('wp_head', 'wpsssl_ob_start', 1);
				add_filter('wp_head', 'wpsssl_ob_end', 999);
				add_filter('wp_footer', 'wpsssl_ob_start', 1);
				add_filter('wp_footer', 'wpsssl_ob_end', 999);
				
				if (function_exists('wpcf7_set_request_uri')) { // Contact Form 7  (2.1.1)
					include_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'contact-form-7.php');
				}
			}
	}
	
	add_filter('admin_menu', 'wpsssl_plugin_menu');
}

function wpsssl_activate_plugin() {
	$wpsssl_opt_name = 'wpsssl_sharedssl_url';
	
	add_option($wpsssl_opt_name, '');
}

function wpsssl_deactivate_plugin() {
	$wpsssl_opt_name = 'wpsssl_sharedssl_url';
	
	delete_option($wpsssl_opt_name);
}

function wpsssl_alter_url($url) {
	$wpsssl_opt_name = 'wpsssl_sharedssl_url';
	$wpsssl_opt_val = get_option($wpsssl_opt_name);
	$urls = parse_url($url);

	return preg_replace('~^https?://' .$urls['host']. '~', rtrim($wpsssl_opt_val, '/'), $url);
}

function wpsssl_alter_content($content) {
	$wpsssl_opt_name = 'wpsssl_sharedssl_url';
	$wpsssl_opt_val = get_option($wpsssl_opt_name);
	$urls = parse_url(get_bloginfo('url'));

	return preg_replace('~https?://' .$urls['host']. '~', rtrim($wpsssl_opt_val, '/'), $content);
}

function wpsssl_ob_start() {
	ob_start();
}

function wpsssl_ob_end() {
	$content = wpsssl_alter_content(ob_get_contents());
	ob_end_clean();
	echo $content;
}

function wpsssl_plugin_menu() {
  add_options_page(__('Shared SSL', 'wpsssl'), __('Shared SSL', 'wpsssl'), 8, __FILE__, 'wpsssl_plugin_options');
}

function wpsssl_plugin_options() {
	$wpsssl_opt_name = 'wpsssl_sharedssl_url';
    $hidden_field_name = 'wpsssl_submit_hidden';
    
    //messages
    $str0 = __('Shared SSL', 'wpsssl');
    $str1 = __('Your setting has been saved', 'wpsssl');
	$str2 = __('URL of Shared SSL', 'wpsssl');
	$str3 = __('Save', 'wpsssl');
    
    $wpsssl_opt_val = get_option($wpsssl_opt_name);
    
	if( $_POST[ $hidden_field_name ] == 'Y' ) {
		$wpsssl_opt_val = $_POST[$wpsssl_opt_name];
		
		update_option( $wpsssl_opt_name, $wpsssl_opt_val );
		
		echo <<<EOF
<div class="updated"><p><strong>{$str1}</strong></p></div>
EOF;
		
	}
	echo <<<EOF
<div class="wrap">
<div class="icon32" id="icon-options-general"><br></div>
<H2>{$str0}</H2>
EOF;

	$action = str_replace( '%7E', '~', $_SERVER['REQUEST_URI']);
	$bloginfo = get_bloginfo('url');
	$alter_bloginfo = wpsssl_alter_url($bloginfo);
	$plugins_url = wpsssl_alter_url(plugins_url());
	$template_directory_uri = wpsssl_alter_url(get_template_directory_uri());
	echo <<<EOF
<form name="form1" method="post" action="{$action}">
<input type="hidden" name="{$hidden_field_name}" value="Y">
<table class="form-table">
<tbody><tr valign="top">
<th scope="row">{$str2}</th>
<td><input type="text" class="regular-text code" value="{$wpsssl_opt_val}" name="{$wpsssl_opt_name}">
</td>
</tr>
</tbody></table>
<p class="submit">
<input type="submit" name="Submit" value="{$str3}" />
</p>
</form>
<p>Sample</p>
<table class="form-table">
<tbody><tr valign="top">
<th scope="row">bloginfo('url')</th>
<td>{$alter_bloginfo}</td>
</tr><tr valign="top">
<th scope="row">get_bloginfo('url', 'raw')</th>
<td>{$bloginfo}</td>
</tr><tr valign="top">
<th scope="row">plugins_url()</th>
<td>{$plugins_url}</td>
</tr><tr valign="top">
<th scope="row">get_template_directory_uri()</th>
<td>{$template_directory_uri}</td>
</tr>
</tbody></table>

</div>
EOF;
}

?>