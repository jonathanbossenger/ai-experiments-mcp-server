<?php
add_action( 'abilities_api_init', 'ai_experiments_register_siteinfo_ability' );
function ai_experiments_register_siteinfo_ability(){
	wp_register_ability( 'ai-experiments/site-info', array(
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
			),
		),
		'execute_callback' => 'ai_experiments_get_siteinfo',
		'permission_callback' => function( $input ) {
			return current_user_can( 'manage_options' );
		}
	));
}

function ai_experiments_get_siteinfo(){
	$site_info = array(
		'site_name' => get_bloginfo( 'name' ),
		'site_url'  => get_bloginfo( 'url' ),
		'active_theme' => wp_get_theme()->get( 'Name' ),
		'active_plugins' => get_option( 'active_plugins', array() ),
	);
	return $site_info;
}
