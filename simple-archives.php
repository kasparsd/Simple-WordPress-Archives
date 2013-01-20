<?php
/*
Plugin Name: Simple Archives
Plugin URI: https://github.com/kasparsd/Simple-WordPress-Archives
Description: Provides a few shortcodes for listing post archives grouped by categories or taxonomies
Version: 0.2
Author: Kaspars Dambis
Author URI: http://konstruktros.com
*/


add_shortcode( 'taxonomy_archive', 'taxonomy_archive' );

function taxonomy_archive( $atts = array() ) {
	global $wpdb, $wp_query, $post;

	$terms = array();
	$term_taxonomy_ids = array();
	$items = array();
	$html = array();

	extract( shortcode_atts( array(
		'post_type' => 'post',
		'taxonomy' => 'category',
		'template_items' => '<div class="term-posts term-%term_id%"><h2>%term_name%</h2> <ul class="posts-in-term">%items%</ul></div>',
		'template_item' => '<li><a href="%the_permalink%" title="%the_title_attribute%">%the_title%</a></li>',
		'template_terms' => '<div class="taxonomy-archive">%terms%</div>',
     ), $atts ));

	$taxonomy_terms = get_terms( $taxonomy, array( 'orderby' => 'count', 'order' => 'DESC' ) );

	if ( empty( $taxonomy_terms ) )
		return;
	
	foreach ( $taxonomy_terms as $t => $term ) {
		$terms[$term->term_id] = $term;
		$term_taxonomy_ids[$term->term_taxonomy_id] = $term->term_id;
	}
	
	$posts = query_posts( array( 
			'posts_per_page' => -1,
			'post_type' => $post_type,
			'category__in' => array_keys( $terms ),
			'term_taxonomy_ids' => array_keys( $term_taxonomy_ids ),
			'taxonomy_archive_shortcode' => true
		));

	if ( have_posts() ) :
		while ( have_posts() ) : the_post();
			
			$item_replace = array(
					'%the_title%' => get_the_title(),
					'%the_permalink%' => get_permalink(),
					'%the_title_attribute%' => the_title_attribute( array( 'echo' => false ) )
				);

			$item_replace = apply_filters( 'taxonomy_archive_replace_item', $item_replace, $post );
			$items[ $post->term_taxonomy_id ][] = str_replace( array_keys( $item_replace ), array_values( $item_replace ), $template_item );
			
			/*
			if ( ( $counter > 1 && $prev_term_taxonomy_id != $post->term_taxonomy_id ) || count( $posts ) == $counter ) {
				// Convert term_taxonomy_id into term_id and get the name
				$previous_term = $terms[ $term_taxonomy_ids[ $prev_term_taxonomy_id ] ];
				
				$items_replace = array(
						'%term_name%' => apply_filters( 'taxonomy_archive_term_name', $previous_term->name, $previous_term ),
						'%term_id%' => $prev_term_id,
						'%the_permalink%' => get_permalink(),
						'%items%' => $items_prev
					);

				if ( count( $posts ) == $counter )
					$items_replace['%items%'] = $items;

				$items_replace = apply_filters( 'taxonomy_archive_replace_item', $items_replace, $post );
				$html .= str_replace( array_keys( $items_replace ), array_values( $items_replace ), $template_items );

				$items = '';
			}
			*/

		endwhile;

		foreach ( $items as $term_taxonomy_id => $posts ) {
			$term = $terms[ $term_taxonomy_ids[ $term_taxonomy_id ] ];

			$term_replace = array(
					'%term_name%' => apply_filters( 'taxonomy_archive_term_name', $term->name, $term ),
					'%term_id%' => $term->term_id,
					'%term_slug%' => $term->slug,
					'%items%' => implode( '', $posts)
				);

			$html[] = str_replace( array_keys( $term_replace ), array_values( $term_replace ), $template_items );
		}
	
		return str_replace( '%terms%', implode( '', $html ), $template_terms );

	endif;

	wp_reset_query();
}

// Don't group posts by ID, because we want to add even repeat posts
add_action( 'posts_groupby', 'force_remove_groupby' );

function force_remove_groupby( $groupby ) {
	global $wp_query;

	if ( $wp_query->get('taxonomy_archive_shortcode') )
		return;

	return $groupby;
}

// Order posts by their taxonomy, then by date published
add_action( 'posts_orderby', 'force_orderby_terms' );

function force_orderby_terms( $orderby ) {
	global $wp_query, $wpdb;

	if ( $wp_query->get('taxonomy_archive_shortcode') )
		return ' FIELD( term_taxonomy_id, '. implode( ',', $wp_query->get('term_taxonomy_ids') ) .' ), ' . $orderby;
	
	return $orderby;
}

// Add term_id to post query, so that we know how to group the posts
add_action( 'posts_fields', 'force_fields_terms' );

function force_fields_terms( $fields ) {
	global $wp_query, $wpdb;
	
	if ( $wp_query->get('taxonomy_archive_shortcode') )
		return $fields . ", {$wpdb->term_relationships}.term_taxonomy_id ";

	return $fields;
}

