<?php
/**
 * Site Info Ability for AI Experiments MCP Server
 *
 * @package AI_Experiments_MCP_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Register the Site Info ability
add_action( 'abilities_api_init', function(){
	wp_register_ability( 'site/site-info', array(
		'label' => __( 'Site Info', 'ai-experiments' ),
		'description' => __( 'Returns information about this WordPress site', 'ai-experiments' ),
		'input_schema' => array(),
		'output_schema' => array(
			'type' => 'object',
			'properties' => array(
				'site_name' => array(
					'type' => 'string',
					'description' => __( 'The name of the WordPress site', 'ai-experiments' )
				),
				'site_url' => array(
					'type' => 'string',
					'description' => __( 'The URL of the WordPress site', 'ai-experiments' )
				),
				'active_theme' => array(
					'type' => 'string',
					'description' => __( 'The active theme of the WordPress site', 'ai-experiments' )
				),
				'active_plugins' => array(
					'type' => 'array',
					'items' => array(
						'type' => 'string',
					),
					'description' => __( 'List of active plugins on the WordPress site', 'ai-experiments' )
				),
				'php_version' => array(
					'type' => 'string',
					'description' => __( 'The PHP version of the WordPress site', 'ai-experiments' )
				),
				'wordpress_version' => array(
					'type' => 'string',
					'description' => __( 'The WordPress version of the site', 'ai-experiments' )
				)
			),
		),
		'execute_callback' => 'ai_experiments_get_siteinfo',
		'permission_callback' => function( $input ) {
			return current_user_can( 'manage_options' );
		},
        'meta' => array(
            'mimeType' => 'application/json',
            'uri'      => 'site://wordpress/site-info',
        ),
	));
});

function ai_experiments_get_siteinfo(){
	$site_info = array();
	$site_info['site_name'] = get_bloginfo( 'name' );
	$site_info['site_url'] = get_bloginfo( 'url' );
	$site_info['active_theme'] = wp_get_theme()->get( 'Name' );
	$site_info['active_plugins'] = get_option( 'active_plugins', array() );
	$site_info['php_version'] = phpversion();
	$site_info['wordpress_version'] = get_bloginfo( 'version' );
	return $site_info;
}
