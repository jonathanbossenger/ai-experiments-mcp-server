<?php
/**
 * Create Post Ability for AI Experiments MCP Server
 *
 * @package AI_Experiments_MCP_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Helper function to convert WP_Error to array format for MCP compatibility.
 *
 * @param WP_Error $wp_error The WP_Error object to convert.
 * @param string   $context  Optional context for the error.
 *
 * @return array Formatted error response.
 */
function ai_experiments_wp_error_to_array( $wp_error, $context = '' ) {
	$error_message = $wp_error->get_error_message();
	if ( $context ) {
		$error_message = $context . ': ' . $error_message;
	}
	
	return array(
		'success' => false,
		'error'   => $error_message,
	);
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
				'success' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the post creation completed successfully', 'mcp-server' )
				),
				'url' => array(
					'type'        => 'string',
					'description' => __( 'The URL of the created post', 'mcp-server' )
				),
				'error' => array(
					'type'        => 'string',
					'description' => __( 'Error message if post creation failed', 'mcp-server' )
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
		return array(
			'success' => false,
			'error'   => __( 'Invalid input data', 'mcp-server' ),
		);
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
		return array(
			'success' => false,
			'error'   => __( 'Failed to create post', 'mcp-server' ) . ': ' . $post_id->get_error_message(),
		);
	}

	// Return the result
	return array(
		'success' => true,
		'url'     => get_permalink( $post_id ),
	);
}
