<?php
/**
 * Plugin Name: Inpsyde oEmbed Gist
 * Plugin URI:  https://github.com/inpsyde/Inpsyde-oEmbed-Gist
 * Text Domain: oembed_gist
 * Domain Path: /languages
 * Description: Autoembedding of Gists in WordPress via oEmbed
 * Version:     1.0.2
 * Author:      Inpsyde GmbH
 * Author URI:  http://inpsyde.com
 * License:     GPLv3
 */

! defined( 'ABSPATH' ) and exit;

add_action( 'plugins_loaded', array( 'Fb_Oembed_Gist', 'init' ) );
/**
 * Class for autoembedding of gists via URL
 * 
 * Usage:
 * Paste a gist link into a blog post or page and it will be embedded eg:
 * https://gist.github.com/bueltge/2290887
 *
 * If a gist has multiple files you can select one using a url in the following format:
 * https://gist.github.com/bueltge/2290887#file-class_fb_backlink_checker-php
 */
class Fb_Oembed_Gist {
	
	/*
	 * Var for the string on noscript view
	 */
	public static    $noscript_string = '';
	
	public	static   $link_string = '';
	
	protected static $classobj = NULL;
	
	public static function init() {
		
		NULL === self::$classobj && self::$classobj = new self();
		
		return self::$classobj;
	}
	
	/**
	 * Constructor
	 * 
	 * @return  void
	 */
	public function __construct() {
		
		self::$noscript_string = __( 'View the code on', 'oembed_gist' );
		self::$link_string = __( 'Gist', 'oembed_gist' );
		
		add_action( 'init', array( $this, 'localize_plugin' ) );
		add_action( 'init', array( $this, 'maybe_load_embed_gist' ) );
	}
	
	/**
	 * Localize_plugin function.
	 *
	 * @uses	load_plugin_textdomain, plugin_basename
	 * @access  public
	 * @return  void
	 */
	public function localize_plugin() {
		
		load_plugin_textdomain( 'oembed_gist', FALSE, dirname( plugin_basename(__FILE__) ) . '/languages' );
	}
	
	/**
	 * Determines if default embed handlers should be loaded.
	 *
	 * Checks to make sure that the embeds library hasn't already been loaded. If
	 * it hasn't, then it will load the embeds library.
	 * 
	 * @return  void
	 */
	public function maybe_load_embed_gist() {
		
		if ( ! apply_filters( 'load_default_embeds', TRUE ) )
			return;
		
		// @see  http://codex.wordpress.org/Function_Reference/wp_embed_register_handler
		// $id, $regex, $callback, $priority = 10 
		wp_embed_register_handler(
			'gist',
			'#https://gist.github.com/(?:[a-z0-9-]*/)?([a-z0-9]+)(\#file-(.+))?$#i',
			array( $this, 'embed_handler_gist' ),
			10
		);
	}
	
	/**
	 * Gist oembed handler callback.
	 * 
	 * @see WP_Embed::register_handler()
	 * @see WP_Embed::shortcode()
	 *
	 * @param  array  $matches The regex matches from the provided regex when calling {@link wp_embed_register_handler()}.
	 * @param  array  $attr    Embed attributes.
	 * @param  string $url     The original URL that was matched by the regex.
	 * @param  array  $rawattr The original unmodified attributes.
	 * @return string          The embed HTML for script.
	 */
	public function embed_handler_gist( $matches, $attr, $url, $rawattr ) {
		
		if ( ! isset( $matches[1] ) )
			return NULL;
		
		if ( ! isset( $matches[2] ) )
			$matches[2] = NULL;
		
		if ( ! isset( $matches[3] ) )
			$matches[3] = NULL;
		
		/**
		 * Helper ;)
		 * Full Gist: 	Permalink: https://gist.github.com/Tarendai/3690149 --> 
		 * 				Embed JS:  https://gist.github.com/Tarendai/3690149.js
		 * Single File: Permalink: https://gist.github.com/Tarendai/3690149#file-typekit-tinymce-js -->
		 * 				Embed JS:  https://gist.github.com/Tarendai/3690149.js?file=typekit.tinymce.js
		 * 
		 * 0      == URL
		 * 1 %1$s == 3690149  Gist ID
		 * 2 %2$s == #file-typekit-tinymce-js  hash + String of File
		 * 3 %3$s == typekit-tinymce-js  String of file
		 */
		
		// set right string, if single file was used
		if ( $matches[3] ) {
			$hash = '#file-' . esc_attr( $matches[3] );
			$file = '?file=' . str_replace( '-', '.', esc_attr( $matches[3] ) );
		} else {
			$hash = $file = '';
		}
		
		// different content on feeds, scripts are not usable on feeds
		if ( is_feed() )
			$embed = sprintf(
				'<p>%4$s <a href="https://gist.github.com/%1$s%2$s">%5$s</a>.</p>',
				esc_attr( $matches[1] ),
				$hash, // Permalink
				$file, // embed url
				self::$noscript_string,
				self::$link_string
			);
		else
			$embed = sprintf(
				'<script type="text/javascript" src="https://gist.github.com/%1$s.js%3$s"></script>' .
				'<noscript><p>%4$s <a href="https://gist.github.com/%1$s%2$s">%5$s</a>.</p></noscript>',
				esc_attr( $matches[1] ),
				$hash, // Permalink
				$file, // embed url
				self::$noscript_string,
				self::$link_string
			);
		
		return apply_filters( 'embed_gist', $embed, $matches, $attr, $url, $rawattr );
	}
	
} // end class
