<?php
/**
 * Debug Log Reading Ability for AI Experiments MCP Server
 *
 * @package AI_Experiments_MCP_Server
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Register the Debug Log Reading ability
add_action( 'abilities_api_init', function () {
	wp_register_ability(
		'debug-log/read-log',
		array(
			'label'               => __( 'Debug Log Reader', 'ai-experiments-mcp-server' ),
			'description'         => __( 'Reads the contents of the WordPress debug.log file from wp-content directory.', 'ai-experiments-mcp-server' ),
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'lines' => array(
						'type'        => 'integer',
						'description' => __( 'Number of lines to read from the end of the file (default: 100, max: 1000)', 'ai-experiments-mcp-server' ),
						'minimum'     => 1,
						'maximum'     => 1000,
						'default'     => 100,
					),
				),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array(
						'type'        => 'boolean',
						'description' => __( 'Whether the debug log reading completed successfully.', 'ai-experiments-mcp-server' ),
					),
					'content' => array(
						'type'        => 'string',
						'description' => __( 'Contents of the debug log file.', 'ai-experiments-mcp-server' ),
					),
					'file_size' => array(
						'type'        => 'integer',
						'description' => __( 'Size of the debug log file in bytes.', 'ai-experiments-mcp-server' ),
					),
					'file_path' => array(
						'type'        => 'string',
						'description' => __( 'Path to the debug log file.', 'ai-experiments-mcp-server' ),
					),
					'lines_returned' => array(
						'type'        => 'integer',
						'description' => __( 'Number of lines actually returned.', 'ai-experiments-mcp-server' ),
					),
					'error'   => array(
						'type'        => 'string',
						'description' => __( 'Error message if the reading failed.', 'ai-experiments-mcp-server' ),
					),
				),
			),
			'execute_callback'    => 'ai_experiments_read_debug_log',
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			}
		)
	);
} );

/**
 * Read the WordPress debug.log file contents.
 *
 * @param array $input Input parameters containing optional 'lines' parameter.
 *
 * @return array JSON response with debug log contents or error.
 */
function ai_experiments_read_debug_log( $input ) {
	try {
		// Get the number of lines to read (default: 100, max: 1000)
		$lines_to_read = isset( $input['lines'] ) ? (int) $input['lines'] : 100;
		$lines_to_read = max( 1, min( 1000, $lines_to_read ) ); // Clamp between 1 and 1000

		// Determine the debug log file path
		$debug_log_path = WP_CONTENT_DIR . '/debug.log';

		// Check if the debug log file exists
		if ( ! file_exists( $debug_log_path ) ) {
			return array(
				'success'   => false,
				'error'     => 'Debug log file does not exist at: ' . $debug_log_path,
				'file_path' => $debug_log_path,
			);
		}

		// Check if the file is readable
		if ( ! is_readable( $debug_log_path ) ) {
			return array(
				'success'   => false,
				'error'     => 'Debug log file is not readable: ' . $debug_log_path,
				'file_path' => $debug_log_path,
			);
		}

		// Get file size
		$file_size = filesize( $debug_log_path );

		// Read the file contents
		if ( $file_size === 0 ) {
			return array(
				'success'        => true,
				'content'        => '',
				'file_size'      => 0,
				'file_path'      => $debug_log_path,
				'lines_returned' => 0,
			);
		}

		// Read the last N lines efficiently
		$content = ai_experiments_read_last_lines( $debug_log_path, $lines_to_read );
		$lines_returned = substr_count( $content, "\n" );

		// If content ends with newline, don't count the empty line
		if ( substr( $content, -1 ) === "\n" ) {
			$lines_returned = max( 0, $lines_returned );
		} else {
			$lines_returned = $lines_returned + 1;
		}

		return array(
			'success'        => true,
			'content'        => $content,
			'file_size'      => $file_size,
			'file_path'      => $debug_log_path,
			'lines_returned' => $lines_returned,
		);

	} catch ( Exception $e ) {
		return array(
			'success' => false,
			'error'   => 'Failed to read debug log: ' . $e->getMessage(),
		);
	}
}

/**
 * Efficiently read the last N lines from a file.
 *
 * @param string $file_path Path to the file.
 * @param int    $lines     Number of lines to read from the end.
 *
 * @return string Content of the last N lines.
 */
function ai_experiments_read_last_lines( $file_path, $lines ) {
	$file = fopen( $file_path, 'r' );
	if ( ! $file ) {
		throw new Exception( 'Unable to open file: ' . $file_path );
	}

	// Get file size
	fseek( $file, 0, SEEK_END );
	$file_size = ftell( $file );

	if ( $file_size === 0 ) {
		fclose( $file );
		return '';
	}

	// Start from the end and work backwards
	$buffer_size = 4096;
	$lines_found = 0;
	$content = '';
	$position = $file_size;

	while ( $lines_found < $lines && $position > 0 ) {
		// Calculate how much to read
		$read_size = min( $buffer_size, $position );
		$position -= $read_size;

		// Read chunk
		fseek( $file, $position, SEEK_SET );
		$chunk = fread( $file, $read_size );

		// Prepend to content
		$content = $chunk . $content;

		// Count newlines in the chunk
		$lines_found += substr_count( $chunk, "\n" );
	}

	fclose( $file );

	// If we have more lines than needed, trim from the beginning
	if ( $lines_found > $lines ) {
		$content_lines = explode( "\n", $content );
		$content_lines = array_slice( $content_lines, -$lines );
		$content = implode( "\n", $content_lines );
	}

	return $content;
}