<?php
/**
 * Create Post Ability for AI Experiments MCP Server
 *
 * @package AI_Experiments_MCP_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Register an ability to create a post
add_action( 'abilities_api_init', function () {
	wp_register_ability( 'mcp-server/create-post', array(
		'label'               => __( 'Create Post', 'mcp-server' ),
		'description'         => __( 'Creates a new blog post with the provided content', 'mcp-server' ),
		'input_schema'        => array(
			'type'       => 'object',
			'properties' => array(
				'title'   => array(
					'type'        => 'string',
					'description' => __( 'The title of the post', 'mcp-server' )
				),
				'content' => array(
					'type'        => 'string',
					'description' => __( 'The content of the post. Must be valid block editor markup', 'mcp-server' )
				),
				'status'  => array(
					'type'        => 'string',
					'description' => __( 'The status of the post', 'mcp-server' ),
					'default'     => 'draft',
					'enum'        => array( 'draft', 'publish' )
				)
			),
			'required'   => array( 'title', 'content' )
		),
		'output_schema'       => array(
			'type'       => 'object',
			'properties' => array(
				'url' => array(
					'type'        => 'string',
					'description' => __( 'The URL of the created post', 'mcp-server' )
				)
			)
		),
		'execute_callback'    => 'mcp_server_create_post',
		'permission_callback' => function ( $input ) {
			return current_user_can( 'publish_posts' );
		}
	) );
} );

/**
 * Callback function to create a post.
 *
 * @param array $input Input data for the post.
 *
 * @return array Result containing post ID and URL.
 */
function mcp_server_create_post( $input ) {
	// Validate input
	if ( ! isset( $input['title'], $input['content'] ) ) {
		return new WP_Error( 'invalid_input', __( 'Invalid input data', 'mcp-server' ), array( 'status' => 400 ) );
	}
	// Create the post
	$post_data = array(
		'post_title'   => sanitize_text_field( $input['title'] ),
		'post_content' => wp_kses_post( $input['content'] ),
		'post_status'  => ( isset( $input['status'] ) && in_array( sanitize_text_field( $input['status'] ), array(
				'draft',
				'publish'
			), true ) )
			? sanitize_text_field( $input['status'] )
			: 'draft',
		'post_author'  => get_current_user_id(),
	);
	$post_id   = wp_insert_post( $post_data );
	if ( is_wp_error( $post_id ) ) {
		return new WP_Error( 'post_creation_failed', __( 'Failed to create post', 'mcp-server' ), array( 'status' => 500 ) );
	}

	// Return the result
	return array(
		'url' => get_permalink( $post_id ),
	);
}
