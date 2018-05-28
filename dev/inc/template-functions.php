<?php
/**
 * Additional features to allow styling of the templates
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 */

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function wprig_body_classes( $classes ) {
	// Add class of group-blog to blogs with more than 1 published author.
	if ( is_multi_author() ) {
		$classes[] = 'group-blog';
	}

	// Add class of hfeed to non-singular pages.
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	// Add class if we're viewing the Customizer for easier styling of theme options.
	if ( is_customize_preview() ) {
		$classes[] = 'wprig-customizer';
	}

	// Add class on front page.
	if ( is_front_page() && 'posts' !== get_option( 'show_on_front' ) ) {
		$classes[] = 'wprig-front-page';
	}

	// Add a class if there is a custom header.
	if ( has_header_image() ) {
		$classes[] = 'has-header-image';
	}

	// Add class if sidebar is used.
	if ( is_active_sidebar( 'sidebar-1' ) && ! is_page() ) {
		$classes[] = 'has-sidebar';
	}

	// Add class for one or two column page layouts.
	if ( is_page() || is_archive() ) {
		if ( 'one-column' === get_theme_mod( 'page_layout' ) ) {
			$classes[] = 'page-one-column';
		} else {
			$classes[] = 'page-two-column';
		}
	}

	// Add class if the site title and tagline is hidden.
	if ( 'blank' === get_header_textcolor() ) {
		$classes[] = 'title-tagline-hidden';
	}

	// Get the colorscheme or the default if there isn't one.
	$colors    = wprig_sanitize_colorscheme( get_theme_mod( 'colorscheme', 'light' ) );
	$classes[] = 'colors-' . $colors;

	return $classes;
}
add_filter( 'body_class', 'wprig_body_classes' );

/**
 * Count our number of active panels.
 *
 * Primarily used to see if we have any panels active, duh.
 */
function wprig_panel_count() {

	$panel_count = 0;

	/**
	 * Filter number of front page sections in Twenty Seventeen.
	 *
	 * @since Twenty Seventeen 1.0
	 *
	 * @param int $num_sections Number of front page sections.
	 */
	$num_sections = apply_filters( 'wprig_front_page_sections', 4 );

	// Create a setting and control for each of the sections available in the theme.
	for ( $i = 1; $i < ( 1 + $num_sections ); $i++ ) {
		if ( get_theme_mod( 'panel_' . $i ) ) {
			$panel_count++;
		}
	}

	return $panel_count;
}

/**
 * Checks to see if we're on the front page or not.
 */
function wprig_is_frontpage() {
	return ( is_front_page() && ! is_home() );
}

/**
 * Adds async/defer attributes to enqueued / registered scripts.
 *
 * If #12009 lands in WordPress, this function can no-op since it would be handled in core.
 *
 * @link https://core.trac.wordpress.org/ticket/12009
 * @param string $tag    The script tag.
 * @param string $handle The script handle.
 * @return array
 */
function wprig_filter_script_loader_tag( $tag, $handle ) {

	foreach ( array( 'async', 'defer' ) as $attr ) {
		if ( ! wp_scripts()->get_data( $handle, $attr ) ) {
			continue;
		}

		// Prevent adding attribute when already added in #12009.
		if ( ! preg_match( ":\s$attr(=|>|\s):", $tag ) ) {
			$tag = preg_replace( ':(?=></script>):', " $attr", $tag, 1 );
		}

		// Only allow async or defer, not both.
		break;
	}

	return $tag;
}

add_filter( 'script_loader_tag', 'wprig_filter_script_loader_tag', 10, 2 );

/**
 * Generate preload markup for stylesheets.
 *
 * @param object $wp_styles Registered styles.
 * @param string $handle The style handle.
 */
function wprig_get_preload_stylesheet_uri( $wp_styles, $handle ) {
	$preload_uri = $wp_styles->registered[ $handle ]->src . '?ver=' . $wp_styles->registered[ $handle ]->ver;
	return $preload_uri;
}

/**
 * Adds preload for in-body stylesheets depending on what templates are being used.
 * Disabled when AMP is active as AMP injects the stylesheets inline.
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Preloading_content
 */
function wprig_add_body_style() {

	if ( ! wprig_is_amp() ) {

		// Get registered styles.
		$wp_styles = wp_styles();

		$preloads = array();

		// Preload singular.css.
		if ( is_singular() && ! is_front_page() ) {
			$preloads['wprig-singular'] = wprig_get_preload_stylesheet_uri( $wp_styles, 'wprig-singular' );
		}

		// Preload widgets.css.
		if ( is_active_sidebar( 'sidebar-1' ) && ! is_front_page() ) {
			$preloads['wprig-widgets'] = wprig_get_preload_stylesheet_uri( $wp_styles, 'wprig-widgets' );
		}

		// Preload comments.css.
		if ( ! post_password_required() && ! is_front_page() && is_singular() && ( comments_open() || get_comments_number() ) ) {
			$preloads['wprig-comments'] = wprig_get_preload_stylesheet_uri( $wp_styles, 'wprig-comments' );
		}

		// Preload front-page.css.
		if ( is_front_page() && 0 !== wprig_panel_count() || is_customize_preview() ) {
			$preloads['wprig-front-page'] = wprig_get_preload_stylesheet_uri( $wp_styles, 'wprig-front-page' );
		}

		// Output the preload markup in <head>.
		foreach ( $preloads as $handle => $src ) {
			echo '<link rel="preload" id="' . esc_attr( $handle ) . '-preload" href="' . esc_url( $src ) . '" as="style" />';
			echo "\n";
		}
	}

}
add_action( 'wp_head', 'wprig_add_body_style' );

/**
 * Adds print-only stylesheet.
 */
function wprig_add_print_stylesheet() {

	// Get registered styles.
	$wp_styles = wp_styles();

	echo '<link id="print-styles" href="' . esc_url( wprig_get_preload_stylesheet_uri( $wp_styles, 'wprig-print-styles' ) ) . '" media="print" />';
	echo "\n";

}
add_action( 'wp_head', 'wprig_add_print_stylesheet' );
