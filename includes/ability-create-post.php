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
	wp_register_ability( 'post/create-post', array(
		'label'               => __( 'Create Post', 'mcp-server' ),
		'description'         => __( 'Creates a new blog post with the provided content', 'mcp-server' ),
		'category'            => 'mcp-server-demo',
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
	if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
		error_log( 'AI_EXPERIMENTS_CREATE_POST: Starting post creation with input: ' . print_r( $input, true ) );
	}

	// Validate input
	if ( ! isset( $input['title'], $input['content'] ) ) {
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_CREATE_POST: Invalid input - missing title or content' );
		}
		return array(
			'success' => false,
			'error'   => __( 'Invalid input data', 'mcp-server' ),
		);
	}
	
	$sanitized_title = sanitize_text_field( $input['title'] );
	$sanitized_content = wp_kses_post( $input['content'] );
	$sanitized_status = ( isset( $input['status'] ) && in_array( sanitize_text_field( $input['status'] ), array(
			'draft',
			'publish'
		), true ) )
		? sanitize_text_field( $input['status'] )
		: 'draft';
	$current_user_id = get_current_user_id();
	
	if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
		error_log( 'AI_EXPERIMENTS_CREATE_POST: Sanitized values:' );
		error_log( 'AI_EXPERIMENTS_CREATE_POST: - Title: ' . $sanitized_title );
		error_log( 'AI_EXPERIMENTS_CREATE_POST: - Content length: ' . strlen( $sanitized_content ) );
		error_log( 'AI_EXPERIMENTS_CREATE_POST: - Status: ' . $sanitized_status );
		error_log( 'AI_EXPERIMENTS_CREATE_POST: - Author ID: ' . $current_user_id );
	}
	
	// Create the post
	$post_data = array(
		'post_title'   => $sanitized_title,
		'post_content' => $sanitized_content,
		'post_status'  => $sanitized_status,
		'post_author'  => $current_user_id,
	);

	if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
		error_log( 'AI_EXPERIMENTS_CREATE_POST: Calling wp_insert_post()' );
	}
	
	$post_id = wp_insert_post( $post_data );
	
	if ( is_wp_error( $post_id ) ) {
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'AI_EXPERIMENTS_CREATE_POST: wp_insert_post() returned WP_Error: ' . $post_id->get_error_message() );
		}
		return array(
			'success' => false,
			'error'   => __( 'Failed to create post', 'mcp-server' ) . ': ' . $post_id->get_error_message(),
		);
	}

	$post_url = get_permalink( $post_id );
	
	if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
		error_log( 'AI_EXPERIMENTS_CREATE_POST: Post created successfully' );
		error_log( 'AI_EXPERIMENTS_CREATE_POST: - Post ID: ' . $post_id );
		error_log( 'AI_EXPERIMENTS_CREATE_POST: - Post URL: ' . $post_url );
	}

	// Return the result
	return array(
		'success' => true,
		'url'     => $post_url,
	);
}
