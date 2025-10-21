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
		'debug/read-log',
		array(
			'label'               => __( 'Debug Log Reader', 'ai-experiments-mcp-server' ),
			'description'         => __( 'Reads the contents of the WordPress debug.log file from wp-content directory.', 'ai-experiments-mcp-server' ),
			'category'            => 'mcp-server-demo',
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

// Register the Debug Log Clear ability
add_action( 'abilities_api_init', function () {
	wp_register_ability(
		'debug/clear-log',
		array(
			'label'               => __( 'Debug Log Clearer', 'ai-experiments-mcp-server' ),
			'description'         => __( 'Clears the contents of the WordPress debug.log file from wp-content directory.', 'ai-experiments-mcp-server' ),
			'category'            => 'mcp-server-demo',
			'input_schema'        => array(),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'success' => array(
						'type'        => 'boolean',
						'description' => __( 'Whether the debug log clearing completed successfully.', 'ai-experiments-mcp-server' ),
					),
					'file_path' => array(
						'type'        => 'string',
						'description' => __( 'Path to the debug log file.', 'ai-experiments-mcp-server' ),
					),
					'previous_size' => array(
						'type'        => 'integer',
						'description' => __( 'Size of the debug log file before clearing in bytes.', 'ai-experiments-mcp-server' ),
					),
					'message' => array(
						'type'        => 'string',
						'description' => __( 'Success or informational message.', 'ai-experiments-mcp-server' ),
					),
					'error'   => array(
						'type'        => 'string',
						'description' => __( 'Error message if the clearing failed.', 'ai-experiments-mcp-server' ),
					),
				),
			),
			'execute_callback'    => 'ai_experiments_clear_debug_log',
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
	if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
		error_log( 'WP_MCP_SERVER_DEBUG_LOG: Starting debug log read with input: ' . print_r( $input, true ) );
	}

	try {
		// Get the number of lines to read (default: 100, max: 1000)
		$lines_to_read = isset( $input['lines'] ) ? (int) $input['lines'] : 100;
		$lines_to_read = max( 1, min( 1000, $lines_to_read ) ); // Clamp between 1 and 1000
		
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: Lines to read: ' . $lines_to_read );
		}

		// Determine the debug log file path
		$debug_log_path = WP_CONTENT_DIR . '/debug.log';
		
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: Debug log path: ' . $debug_log_path );
		}

		// Check if the debug log file exists
		$file_exists = file_exists( $debug_log_path );
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: File exists: ' . ( $file_exists ? 'true' : 'false' ) );
		}
		
		if ( ! $file_exists ) {
			return array(
				'success'   => false,
				'error'     => 'Debug log file does not exist at: ' . $debug_log_path,
				'file_path' => $debug_log_path,
			);
		}

		// Check if the file is readable
		$is_readable = is_readable( $debug_log_path );
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: File is readable: ' . ( $is_readable ? 'true' : 'false' ) );
		}
		
		if ( ! $is_readable ) {
			return array(
				'success'   => false,
				'error'     => 'Debug log file is not readable: ' . $debug_log_path,
				'file_path' => $debug_log_path,
			);
		}

		// Get file size
		$file_size = filesize( $debug_log_path );
		
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: File size: ' . $file_size . ' bytes' );
		}

		// Read the file contents
		if ( $file_size === 0 ) {
			if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
				error_log( 'WP_MCP_SERVER_DEBUG_LOG: File is empty, returning empty content' );
			}
			return array(
				'success'        => true,
				'content'        => '',
				'file_size'      => 0,
				'file_path'      => $debug_log_path,
				'lines_returned' => 0,
			);
		}

		// Read the last N lines efficiently
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: Calling ai_experiments_read_last_lines()' );
		}
		$content = ai_experiments_read_last_lines( $debug_log_path, $lines_to_read );
		$lines_returned = substr_count( $content, "\n" );

		// If content ends with newline, don't count the empty line
		if ( substr( $content, -1 ) === "\n" ) {
			$lines_returned = max( 0, $lines_returned );
		} else {
			$lines_returned = $lines_returned + 1;
		}
		
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: Content read successfully, lines returned: ' . $lines_returned );
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: Content length: ' . strlen( $content ) . ' characters' );
		}

		return array(
			'success'        => true,
			'content'        => $content,
			'file_size'      => $file_size,
			'file_path'      => $debug_log_path,
			'lines_returned' => $lines_returned,
		);

	} catch ( Exception $e ) {
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: Exception caught: ' . $e->getMessage() );
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: Exception trace: ' . $e->getTraceAsString() );
		}
		return array(
			'success' => false,
			'error'   => 'Failed to read debug log: ' . $e->getMessage(),
		);
	}
}

/**
 * Clear the WordPress debug.log file contents.
 *
 * @param array $input Input parameters (none required for clearing).
 *
 * @return array JSON response with clearing status or error.
 */
function ai_experiments_clear_debug_log( $input ) {
	if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
		error_log( 'WP_MCP_SERVER_DEBUG_LOG: Starting debug log clear with input: ' . print_r( $input, true ) );
	}

	try {
		// Determine the debug log file path
		$debug_log_path = WP_CONTENT_DIR . '/debug.log';
		
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: Debug log path: ' . $debug_log_path );
		}

		// Check if the debug log file exists
		$file_exists = file_exists( $debug_log_path );
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: File exists: ' . ( $file_exists ? 'true' : 'false' ) );
		}
		
		if ( ! $file_exists ) {
			return array(
				'success'   => true,
				'message'   => 'Debug log file does not exist, nothing to clear.',
				'file_path' => $debug_log_path,
				'previous_size' => 0,
			);
		}

		// Check if the file is writable
		$is_writable = is_writable( $debug_log_path );
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: File is writable: ' . ( $is_writable ? 'true' : 'false' ) );
		}
		
		if ( ! $is_writable ) {
			return array(
				'success'   => false,
				'error'     => 'Debug log file is not writable: ' . $debug_log_path,
				'file_path' => $debug_log_path,
			);
		}

		// Get file size before clearing
		$previous_size = filesize( $debug_log_path );
		
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: Previous file size: ' . $previous_size . ' bytes' );
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: Opening file for truncation' );
		}

		// Clear the file by truncating it to 0 bytes
		$file_handle = fopen( $debug_log_path, 'w' );
		if ( ! $file_handle ) {
			if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
				error_log( 'WP_MCP_SERVER_DEBUG_LOG: Failed to open file for writing' );
			}
			return array(
				'success'       => false,
				'error'         => 'Unable to open debug log file for writing: ' . $debug_log_path,
				'file_path'     => $debug_log_path,
				'previous_size' => $previous_size,
			);
		}

		fclose( $file_handle );
		
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: File cleared successfully' );
		}

		return array(
			'success'       => true,
			'message'       => 'Debug log file cleared successfully.',
			'file_path'     => $debug_log_path,
			'previous_size' => $previous_size,
		);

	} catch ( Exception $e ) {
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: Exception caught: ' . $e->getMessage() );
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: Exception trace: ' . $e->getTraceAsString() );
		}
		return array(
			'success' => false,
			'error'   => 'Failed to clear debug log: ' . $e->getMessage(),
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
	if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
		error_log( 'WP_MCP_SERVER_DEBUG_LOG: Reading last ' . $lines . ' lines from: ' . $file_path );
	}
	
	$file = fopen( $file_path, 'r' );
	if ( ! $file ) {
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: Unable to open file: ' . $file_path );
		}
		throw new Exception( 'Unable to open file: ' . $file_path );
	}

	// Get file size
	fseek( $file, 0, SEEK_END );
	$file_size = ftell( $file );
	
	if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
		error_log( 'WP_MCP_SERVER_DEBUG_LOG: File size for line reading: ' . $file_size . ' bytes' );
	}

	if ( $file_size === 0 ) {
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: File is empty, returning empty string' );
		}
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
	
	if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
		error_log( 'WP_MCP_SERVER_DEBUG_LOG: Found ' . $lines_found . ' lines in content' );
	}

	// If we have more lines than needed, trim from the beginning
	if ( $lines_found > $lines ) {
		if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
			error_log( 'WP_MCP_SERVER_DEBUG_LOG: Trimming content to last ' . $lines . ' lines' );
		}
		$content_lines = explode( "\n", $content );
		$content_lines = array_slice( $content_lines, -$lines );
		$content = implode( "\n", $content_lines );
	}

	if ( defined( 'WP_MCP_SERVER_DEBUG' ) && WP_MCP_SERVER_DEBUG ) {
		error_log( 'WP_MCP_SERVER_DEBUG_LOG: Final content length: ' . strlen( $content ) . ' characters' );
	}

	return $content;
}