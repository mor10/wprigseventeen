<?php
/**
 * Custom template tags for this theme
 *
 * Eventually, some of the functionality here could be replaced by core features.
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 */

/**
 * Determine whether this is an AMP response.
 *
 * Note that this must only be called after the parse_query action.
 *
 * @link https://github.com/Automattic/amp-wp
 * @return bool Is AMP endpoint (and AMP plugin is active).
 */
function wprig_is_amp() {
	return isset( $_GET['amp'] );
}

/**
 * Determine whether amp-live-list should be used for the comment list.
 *
 * @return bool Whether to use amp-live-list.
 */
function wprig_using_amp_live_list_comments() {
	if ( ! wprig_is_amp() ) {
		return false;
	}
	$amp_theme_support = get_theme_support( 'amp' );
	return ! empty( $amp_theme_support[0]['comments_live_list'] );
}

/**
 * Add pagination reference point attribute for amp-live-list when theme supports AMP.
 *
 * This is used by the navigation_markup_template filter in the comments template.
 *
 * @link https://www.ampproject.org/docs/reference/components/amp-live-list#pagination
 *
 * @param string $markup Navigation markup.
 * @return string Markup.
 */
function wprig_add_amp_live_list_pagination_attribute( $markup ) {
	return preg_replace( '/(\s*<[a-z0-9_-]+)/i', '$1 pagination ', $markup, 1 );
}

if ( ! function_exists( 'wprig_posted_on' ) ) :
	/**
	 * Prints HTML with meta information for the current post-date/time and author.
	 */
	function wprig_posted_on() {

		// Get the author name; wrap it in a link.
		$byline = sprintf(
			/* translators: %s: post author */
			__( 'by %s', 'wprig' ),
			'<span class="author vcard"><a class="url fn n" href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . get_the_author() . '</a></span>'
		);

		// Finally, let's write all of this to the page.
		echo '<span class="posted-on">' . wprig_time_link() . '</span><span class="byline"> ' . $byline . '</span>';
	}
endif;


if ( ! function_exists( 'wprig_time_link' ) ) :
	/**
	 * Gets a nicely formatted string for the published date.
	 */
	function wprig_time_link() {
		$time_string = '<time class="entry-date published updated" datetime="%1$s">%2$s</time>';
		if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
			$time_string = '<time class="entry-date published" datetime="%1$s">%2$s</time><time class="updated" datetime="%3$s">%4$s</time>';
		}

		$time_string = sprintf(
			$time_string,
			get_the_date( DATE_W3C ),
			get_the_date(),
			get_the_modified_date( DATE_W3C ),
			get_the_modified_date()
		);

		// Wrap the time string in a link, and preface it with 'Posted on'.
		return sprintf(
			/* translators: %s: post date */
			__( '<span class="screen-reader-text">Posted on</span> %s', 'wprig' ),
			'<a href="' . esc_url( get_permalink() ) . '" rel="bookmark">' . $time_string . '</a>'
		);
	}
endif;


if ( ! function_exists( 'wprig_entry_footer' ) ) :
	/**
	 * Prints HTML with meta information for the categories, tags and comments.
	 */
	function wprig_entry_footer() {

		/* translators: used between list items, there is a space after the comma */
		$separate_meta = __( ', ', 'wprig' );

		// Get Categories for posts.
		$categories_list = get_the_category_list( $separate_meta );

		// Get Tags for posts.
		$tags_list = get_the_tag_list( '', $separate_meta );

		// We don't want to output .entry-footer if it will be empty, so make sure its not.
		if ( ( ( wprig_categorized_blog() && $categories_list ) || $tags_list ) || get_edit_post_link() ) {

			echo '<footer class="entry-footer">';

			if ( 'post' === get_post_type() ) {
				if ( ( $categories_list && wprig_categorized_blog() ) || $tags_list ) {
					echo '<span class="cat-tags-links">';

						// Make sure there's more than one category before displaying.
					if ( $categories_list && wprig_categorized_blog() ) {
						echo '<span class="cat-links">' . wprig_get_svg( array( 'icon' => 'folder-open' ) ) . '<span class="screen-reader-text">' . __( 'Categories', 'wprig' ) . '</span>' . $categories_list . '</span>';
					}

					if ( $tags_list && ! is_wp_error( $tags_list ) ) {
						echo '<span class="tags-links">' . wprig_get_svg( array( 'icon' => 'hashtag' ) ) . '<span class="screen-reader-text">' . __( 'Tags', 'wprig' ) . '</span>' . $tags_list . '</span>';
					}

					echo '</span>';
				}
			}

			wprig_edit_link();

			echo '</footer> <!-- .entry-footer -->';
		}
	}
endif;


if ( ! function_exists( 'wprig_edit_link' ) ) :
	/**
	 * Returns an accessibility-friendly link to edit a post or page.
	 *
	 * This also gives us a little context about what exactly we're editing
	 * (post or page?) so that users understand a bit more where they are in terms
	 * of the template hierarchy and their content. Helpful when/if the single-page
	 * layout with multiple posts/pages shown gets confusing.
	 */
	function wprig_edit_link() {
		edit_post_link(
			sprintf(
				/* translators: %s: Name of current post */
				__( 'Edit<span class="screen-reader-text"> "%s"</span>', 'wprig' ),
				get_the_title()
			),
			'<span class="edit-link">',
			'</span>'
		);
	}
endif;

/**
 * Display a front page section.
 *
 * @param WP_Customize_Partial $partial Partial associated with a selective refresh request.
 * @param integer              $id Front page section to display.
 */
function wprig_front_page_section( $partial = null, $id = 0 ) {
	if ( is_a( $partial, 'WP_Customize_Partial' ) ) {
		// Find out the id and set it up during a selective refresh.
		global $wprigcounter;
		$id                     = str_replace( 'panel_', '', $partial->id );
		$wprigcounter = $id;
	}

	global $post; // Modify the global post object before setting up post data.
	if ( get_theme_mod( 'panel_' . $id ) ) {
		$post = get_post( get_theme_mod( 'panel_' . $id ) );
		setup_postdata( $post );
		set_query_var( 'panel', $id );

		get_template_part( 'template-parts/page/content', 'front-page-panels' );

		wp_reset_postdata();
	} elseif ( is_customize_preview() ) {
		// The output placeholder anchor.
		echo '<article class="panel-placeholder panel wprig-panel wprig-panel' . $id . '" id="panel' . $id . '"><span class="wprig-panel-title">' . sprintf( __( 'Front Page Section %1$s Placeholder', 'wprig' ), $id ) . '</span></article>';
	}
}

/**
 * Returns true if a blog has more than 1 category.
 *
 * @return bool
 */
function wprig_categorized_blog() {
	$category_count = get_transient( 'wprig_categories' );

	if ( false === $category_count ) {
		// Create an array of all the categories that are attached to posts.
		$categories = get_categories(
			array(
				'fields'     => 'ids',
				'hide_empty' => 1,
				// We only need to know if there is more than one category.
				'number'     => 2,
			)
		);

		// Count the number of categories that are attached to the posts.
		$category_count = count( $categories );

		set_transient( 'wprig_categories', $category_count );
	}

	// Allow viewing case of 0 or 1 categories in post preview.
	if ( is_preview() ) {
		return true;
	}

	return $category_count > 1;
}


/**
 * Flush out the transients used in wprig_categorized_blog.
 */
function wprig_category_transient_flusher() {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	// Like, beat it. Dig?
	delete_transient( 'wprig_categories' );
}
add_action( 'edit_category', 'wprig_category_transient_flusher' );
add_action( 'save_post', 'wprig_category_transient_flusher' );
