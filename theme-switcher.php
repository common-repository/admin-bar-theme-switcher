<?php

/*
Plugin Name: Admin Bar Theme Switcher
Plugin URI:
Description: Adds a dropdown to the admin bar allowing you to switch themes.
Version: 2.0.0
Author: Ryan Duff / Fusionized Technology
Author URI: http://fusionized.com/

Adapted from Alex King's style switcher.
http://www.alexking.org/software/wordpress/

and

Ryan Boren's Theme Switcher.
http://wordpress.org/extend/plugins/theme-switcher/

*/

class ThemeSwitcher {

	function __construct() {
		add_action( 'init', array( $this, 'set_theme_cookie' ) );

		add_filter( 'stylesheet', array( $this, 'get_stylesheet' ) );
		add_filter( 'template', array( $this, 'get_template' ) );
		add_action( 'admin_bar_menu', array( $this, 'switcher_dropdown' ), 1000 );
		add_action( 'wp_head', array( $this, 'hide_switcher_a_tag' ) );
		add_action( 'admin_head', array( $this, 'hide_switcher_a_tag' ) );

	}

	function set_theme_cookie() {
		load_plugin_textdomain( 'theme-switcher' );

		if ( ! empty( $_GET["wptheme"] ) ) {

			$expire = time() + 30000000;

			setcookie( 'wptheme' . COOKIEHASH, stripslashes( $_GET["wptheme"] ), $expire, COOKIEPATH );

			wp_redirect( remove_query_arg( 'wptheme' ) );
			exit;
		}
	}

	function get_stylesheet( $stylesheet  = '' ) {
		$theme = $this->get_theme();

		if ( empty( $theme ) ) {
			return $stylesheet;
		}

		$theme = wp_get_theme( $theme );

		// Don't let people peek at unpublished themes.
		if ( isset( $theme['Status'] ) && $theme['Status'] != 'publish' )
			return $stylesheet;

		if ( empty( $theme ) ) {
			return $stylesheet;
		}

		return $theme['Stylesheet'];
	}

	function get_template( $template ) {
		$theme = $this->get_theme();

		if ( empty( $theme ) ) {
			return $template;
		}

		$theme = wp_get_theme( $theme );

		if ( empty( $theme ) ) {
			return $template;
		}

		// Don't let people peek at unpublished themes.
		if ( isset( $theme['Status'] ) && $theme['Status'] != 'publish' ) {
			return $template;
		}

		return $theme['Template'];
	}

	function get_theme() {
		if ( ! empty( $_COOKIE['wptheme' . COOKIEHASH] ) ) {
			return $_COOKIE['wptheme' . COOKIEHASH];
		} else {
			return '';
		}
	}

	function switcher_dropdown() {
		global $wp_admin_bar, $wpdb;

		if ( ! is_super_admin() || ! is_admin_bar_showing() ) {
			return;
		}

		/* Add the main siteadmin menu item */
		$wp_admin_bar->add_menu( array(
			'id'    => 'switch_themes',
			'title' => 'Switch Theme',
			'href'  => '#',
			'meta'  => array ( 'class' => 'themeswitchermenumain' )
		) );

		$wp_admin_bar->add_menu( array(
			'id'     => 'switch_themes_dropdown',
			'parent' => 'switch_themes',
			'title'  => $this->theme_switcher_markup( 'dropdown' ),
			'href'   => '#',
			'meta'   => array ( 'class' => 'themeswitchermenu' )
		) );

	}

	function theme_switcher_markup( $style = 'text', $instance = array() ) {

		if ( ! $theme_data = get_transient( 'theme-switcher-themes-data' ) ) {
			$themes = (array) wp_get_themes();
			if ( function_exists( 'is_site_admin' ) ) {

				$allowed_themes = (array) get_site_option( 'allowedthemes' );

				foreach( $themes as $key => $theme ) {
				    if ( isset( $allowed_themes[ wp_specialchars( $theme[ 'Stylesheet' ] ) ] ) == false ) {
						unset( $themes[ $key ] );
				    }
				}
			}

			$default_theme = wp_get_theme()->get( 'Name' );

			$theme_data = array();
			foreach ( (array) $themes as $theme_name => $data ) {
				// Skip unpublished themes.
				if ( empty( $theme_name ) || isset( $themes[ $theme_name ]['Status'] ) && $themes[ $theme_name ]['Status'] != 'publish' ) {
					continue;
				}
				$theme_data[ add_query_arg( 'wptheme', $theme_name ) ] = $theme_name;
			}

			asort( $theme_data );
			set_transient( 'theme-switcher-themes-data', $theme_data, DAY_IN_SECONDS );
		}

		$ts = '';

		if ( $style == 'dropdown' ) {
			$ts .= '</a><select name="themeswitcher" onchange="location.href=this.options[this.selectedIndex].value;" style="color: #000; text-shadow: none; margin: 5px 10px;">'."\n";
		}

		foreach ( $theme_data  as $url => $theme_name ) {
			if (
				! empty( $_COOKIE['wptheme' . COOKIEHASH] ) && $_COOKIE['wptheme' . COOKIEHASH] == $theme_name ||
				empty( $_COOKIE['wptheme' . COOKIEHASH] ) && ( $theme_name  == $default_theme )
			) {
				$pattern = 'dropdown' == $style ? '<option value="%1$s" selected="selected" style="color: #000; text-shadow: none;">%2$s</option>' : '<li>%2$s</li>';
			} else {
				$pattern = 'dropdown' == $style ? '<option value="%1$s" style="color: #000; text-shadow: none;">%2$s</option>' : '<li><a href="%1$s">%2$s</a></li>';
			}
			$ts .= sprintf( $pattern, esc_attr( $url ), esc_html( $theme_name ) );

		}

		if ( 'dropdown' == $style ) {
			$ts .= '</select><a href="#">';
		}

		return $ts;
	}

	function hide_switcher_a_tag() {
		?>
		<style type="text/css" media="screen"> #wpadminbar .themeswitchermenu a {display:none;} </style>
		<?php
	}

}

$theme_switcher = new ThemeSwitcher();
