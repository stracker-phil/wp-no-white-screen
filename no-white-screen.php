<?php
/**
 * Allows you to defeat the white-screen-of-death!
 *
 * Setup:
 * 1. Save this file as `wp-contents/mu-plugins/no-white-screen.php`
 * 2. In wp-config.php set WP_DEBUG to true
 *
 * If you still don't see any errors, then there's something fundamentally wrong
 * and you should start debugging wp-settings.php by adding some debug output
 * at various points to find the last working line.
 *
 * Remember to remove this file again after debugging the error!
 *
 * -----------------------------------------------------------------------------
 *
 * Author: Philipp Stracker (philipp@stracker.net)
 */

class No_White_Screen_Of_Death {
	static function instance() {
		static $Inst = null;

		if ( null === $Inst ) {
			$Inst = new No_White_Screen_Of_Death();
		}

		return $Inst;
	}

	private function __construct() {
		$this->init();

		// Make sure to use THIS error handler after any action was fired.
		add_action( 'all', array( $this, 'init' ), 1 );
		add_action( 'all', array( $this, 'init' ), 9999 );
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
		switch ( $errno ) {
			case E_STRICT:
			case E_NOTICE:
			case E_DEPRECATED:
			case E_USER_NOTICE:
				$type = 'notice';
				$fatal = false;
				$color = '#0AD';
				break;

			case E_WARNING:
			case E_USER_WARNING:
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


		$trace = debug_backtrace();
		$this->dump( $errstr, $type, $trace, $errfile, $errline, $color );

		if ( $fatal ) {
			echo '<h1>Fatal error. Terminate request!</h1>';
			exit( 1 );
		}
	}

	private function dump( $message, $type, $trace, $err_file = false, $err_line = false, $color = '#AAA' ) {
		if ( ! empty( $err_file ) ) {
			$file_pos = "In $err_file [line $err_line]";
		} else {
			$file_pos = '';
		}

		if ( php_sapi_name() == 'cli' ) {
			if ( ! empty( $file_pos ) ) {
				$file_pos = "\n" . $file_pos;
			}
			echo 'Backtrace from ' . $type . ' "' . $message . '"' . $file_pos . "\n";
			foreach ( $trace as $item ) {
				echo '  ' . (isset($item['file']) ? $item['file'] : '<unknown file>');
				echo ' ' . (isset($item['line']) ? $item['line'] : '<unknown line>') . ' ';
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
				echo '	<li>' . (isset($item['file']) ? $item['file'] : '<unknown file>');
				echo ' [line ' . (isset($item['line']) ? $item['line'] : '?') . '] ';
				echo 'calling ' . $item['function'] . '()</li>' . "\n";
			}
			echo '  </ol>' . "\n";
			echo '</p></div><hr />' . "\n";
		}

		if ( ini_get( 'log_errors' ) ) {
			$items = array();
			foreach ( $trace as $item ) {
				$items[] = (isset($item['file']) ? $item['file'] : '<unknown file>') . ' ' .
					(isset($item['line']) ? $item['line'] : '<unknown line>') .
					' calling ' . $item['function'] . '()';
			}
			$message = 'Backtrace from ' . $type . ' "' . $message . '"' . $file_pos . '' . join( ' | ', $items );
			error_log( $message );
		}

		while ( ob_get_level() ) { ob_end_flush(); }
		flush();
	}

	public function init() {
		if ( defined( 'WP_DEBUG_CORE' ) && ! WP_DEBUG_CORE ) { return; }

		error_reporting( E_ALL ); // Not sure if this is needed, but we'll add it!

		set_error_handler( array( $this, 'process_error' ) );
		set_exception_handler( array( $this, 'process_exception' ) );
	}
}

if ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( defined( 'WP_DEBUG_CORE' ) && WP_DEBUG_CORE ) ) {
	No_White_Screen_Of_Death::instance();
}
