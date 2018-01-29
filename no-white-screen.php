<?php
/**
 * Plugin Name: No White-Screen-Of-Death
 * Plugin URI:  https://github.com/stracker-phil/wp-no-white-screen/
 * Description: Small plugin that displays actual PHP errors instead of a white-screen-of-death
 * Version:     1.1.0
 * Author:      Philipp Stracker
 * Author URI:  http://www.stracker.net/
 */

/**
 * Allows you to defeat the white-screen-of-death!
 *
 * Setup:
 * 1. Save this file as `wp-contents/mu-plugins/no-white-screen.php`
 * 2. In wp-config.php set WP_DEBUG to true
 *
 * The plugin recognizes the flag WP_DEBUG_CORE:
 *   // 0 | false: Disable this plugin, even if WP_DEBUG is enabled.
 *   //            Default if WP_DEBUG is false.
 *   define( 'WP_DEBUG_CORE', 0 );
 *
 *   // 1 | true: Use the default PHP error reporting output for errors.
 *   //           Default if WP_DEBUG is true.
 *   define( 'WP_DEBUG_CORE', 1 );
 *
 *   // 2: Use a custom more detailed error reporting for errors.
 *   define( 'WP_DEBUG_CORE', 2 );
 *
 * If you still don't see any errors, then there's something fundamentally wrong
 * and you should start debugging wp-settings.php by adding some debug output
 * at various points to find the last working line.
 *
 * -----------------------------------------------------------------------------
 *
 *   This process has a considerable performance impact!
 *   Therefore when not actively debugging:
 *
 *       SET WP_DEBUG_CORE TO FALSE/0   -or-
 *       COMPLETELY REMOVE THIS FILE AGAIN.
 *
 * -----------------------------------------------------------------------------
 *
 * Author: Philipp Stracker (philipp@stracker.net)
 */

class No_White_Screen_Of_Death {
	static function instance() {
		static $_inst = null;

		if ( null === $_inst ) {
			$_inst = new No_White_Screen_Of_Death();
		}

		return $_inst;
	}

	private function __construct() {
		if ( $this->init() ) {
			// Make sure to use our custom error handler after any action was fired.
			add_action( 'all', array( $this, 'init' ), 1 );
			add_action( 'all', array( $this, 'init' ), 9999 );

			$this->check_common_issues();
		}
	}

	public function process_exception( $exception ) {
		$this->dump(
			$exception->getMessage(),
			'Exception',
			$exception->getTrace(),
			$exception->getFile(),
			$exception->getLine()
		);
	}

	public function process_error( $errno, $errstr, $errfile, $errline ) {
		$silent = false;
		$fatal = false;

		switch ( $errno ) {
			case E_DEPRECATED:
			case E_STRICT:
			case E_NOTICE:
				$silent = true;
				$type = 'notice';
				$fatal = false;
				$color = '#0AD';
				break;

			case E_WARNING:
			case E_USER_WARNING:
			case E_USER_NOTICE:
				$type = 'warning';
				$fatal = false;
				$color = '#EA0';
				break;

			default:
				$type = 'fatal error';
				$fatal = true;
				$color = '#F00';
				break;
		}

		if ( $silent ) {
			return;
		}

		$trace = debug_backtrace();
		$this->dump( $errstr, $type, $trace, $errfile, $errline, $color );

		if ( $fatal ) {
			$this->terminate_fatal();
		}
	}

	private function dump( $message, $type, $trace, $err_file = false, $err_line = false, $color = '#AAA' ) {
		if ( ! empty( $err_file ) ) {
			$file_pos = "In $err_file [line $err_line]";
		} else {
			$file_pos = '';
		}

		if ( 'cli' == php_sapi_name() ) {
			if ( ! empty( $file_pos ) ) {
				$file_pos = "\n" . $file_pos;
			}
			echo 'Backtrace from ' . $type . ' "' . $message . '"' . $file_pos . "\n";
			foreach ( $trace as $item ) {
				echo '  ' . (isset( $item['file'] ) ? $item['file'] : '<unknown file>');
				echo ' ' . (isset( $item['line'] ) ? $item['line'] : '<unknown line>') . ' ';
				echo 'calling ' . $item['function'] . '()' . "\n";
			}
		} else {
			if ( ! empty( $file_pos ) ) {
				$file_pos = '<br />' . $file_pos;
			}

			$style_list = array(
				'padding' => '1px 10px',
				'border-left' => '5px solid ' . $color,
			);
			$styles = '';
			foreach ( $style_list as $name => $value ) {
				$styles .= $name . ':' . $value . ';';
			}

			echo '<div style="' . $styles . '">';
			echo '<p class="error_backtrace">' . "\n";
			echo '  <strong>' . $message . '</strong><br />' . "\n";
			echo '  Backtrace from ' . $type . $file_pos . ':' . "\n";
			echo '  <ol>' . "\n";
			foreach ( $trace as $item ) {
				echo '	<li>' . (isset( $item['file'] ) ? $item['file'] : '<unknown file>');
				echo ' [line ' . (isset( $item['line'] ) ? $item['line'] : '?') . '] ';
				echo 'calling ' . $item['function'] . '()</li>' . "\n";
			}
			echo '  </ol>' . "\n";
			echo '</p></div><hr />' . "\n";
		}

		if ( ini_get( 'log_errors' ) ) {
			$items = array();
			foreach ( $trace as $item ) {
				$items[] = (isset( $item['file'] ) ? $item['file'] : '<unknown file>') . ' ' .
					(isset( $item['line'] ) ? $item['line'] : '<unknown line>') .
					' calling ' . $item['function'] . '()';
			}
			$message = 'Backtrace from ' . $type . ' "' . $message . '"' . $file_pos . '' . join( ' | ', $items );
			error_log( $message );
		}

		while ( ob_get_level() ) {
			ob_end_flush();
		}
		flush();
	}

	protected function terminate_fatal() {
		echo '<h1>Fatal error. Terminate request!</h1>';
		exit( 1 );
	}

	protected function check_common_issues() {
		global $wpdb;

		// Check paths.
		if ( ! defined( 'ABSPATH' ) || ! ABSPATH ) {
			$this->dump( 'ABSPATH is not initialized', 'config issue', array() );
			$this->terminate_fatal();
		}
		if ( ! defined( 'WP_CONTENT_DIR' ) || ! WP_CONTENT_DIR ) {
			$this->dump( 'WP_CONTENT_DIR is not initialized', 'config issue', array() );
			$this->terminate_fatal();
		}

		// Check DB connection.
		if ( ! $wpdb ) {
			$this->dump( '$wpdb is not initialized', 'config issue', array() );
			$this->terminate_fatal();
		}
		if ( ! $wpdb->ready ) {
			$this->dump( '$wpdb is not ready', 'config issue', array() );
			$this->terminate_fatal();
		}
		if ( ! $wpdb->check_connection() ) {
			$this->dump( '$wpdb connection not open', 'config issue', array() );
			$this->terminate_fatal();
		}

		// Check theme settings
		$sql = sprintf(
			'SELECT option_value
			FROM %s
			WHERE option_name IN ("template", "stylesheet");',
			$wpdb->options
		);
		$templates = $wpdb->get_col( $sql );
		$theme_root = WP_CONTENT_DIR . '/themes';

		foreach ( $templates as $template ) {
			if ( ! file_exists( "$theme_root/$template" ) ) {
				$this->dump( "Theme folder does not exist: $theme_root/$template", 'config issue', array() );
				$this->terminate_fatal();
			}
			if ( ! file_exists( "$theme_root/$template/style.css" ) ) {
				$this->dump( "Theme does not have style.css: $theme_root/$template", 'config issue', array() );
				$this->terminate_fatal();
			}
		}
	}

	public function init() {
		if ( ! WP_DEBUG_CORE ) {
			return false;
		}

		error_reporting( E_ALL );

		if ( 2 === (int) WP_DEBUG_CORE ) {
			set_error_handler( array( $this, 'process_error' ) );
			set_exception_handler( array( $this, 'process_exception' ) );
		} else {
			set_error_handler( '__return_false' );
			set_exception_handler( null );
		}

		return true;
	}
}

/**
 * Load the Debugger if WP_DEBUG or WP_DEBUG_CORE is enabled.
 */
if ( (defined( 'WP_DEBUG' ) && WP_DEBUG) || (defined( 'WP_DEBUG_CORE' ) && WP_DEBUG_CORE) ) {
	if ( ! defined( 'WP_DEBUG_CORE' ) ) {
		define( 'WP_DEBUG_CORE', 1 );
	}

	No_White_Screen_Of_Death::instance();
}
