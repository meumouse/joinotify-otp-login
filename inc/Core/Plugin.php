<?php

namespace MeuMouse\Joinotify\Otp_Login\Core;

use ReflectionException;
use ReflectionClass;
use Exception;

defined('ABSPATH') || exit;

/**
 * Initialize plugin classes
 *
 * @since 1.0.0
 * @package MeuMouse\Joinotify\Otp_Login\Core
 * @author MeuMouse.com
 */
final class Plugin {

	/**
	 * Plugin main file path.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Plugin version
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $plugin_version;

	/**
	 * Plugin directory.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $directory;

	/**
	 * Plugin basename.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $basename;

	/**
	 * Cache for instantiated classes to prevent duplicate instantiation.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $instantiated_classes = array();


	/**
	 * Construct function.
	 *
	 * @since 1.0.0
	 * @param string $plugin_file | Plugin main file path.
	 * @param string $plugin_version | Plugin version.
	 * @return void
	 */
	public function __construct( $plugin_file, $plugin_version ) {
		$this->plugin_file = $plugin_file;
		$this->plugin_version = $plugin_version;

		/**
		 * Fire hook before plugin initialize.
		 *
		 * @since 1.1.0
		 */
		do_action('Joinotify/Otp_Login/Before_Init');

		// Display notice if PHP version is below 7.4.
		if ( version_compare( phpversion(), '7.4', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
			return;
		}

		$this->setup_constants();
		$this->directory = JOINOTIFY_OTP_LOGIN_DIR;
		$this->basename = JOINOTIFY_OTP_LOGIN_BASENAME;

		$this->register_hooked_classes();

		// Add settings link on plugins list.
	//	add_filter( 'plugin_action_links_' . $this->basename, array( $this, 'add_action_plugin_links' ), 10, 4 );

		// Add docs link on plugins list.
	//	add_filter( 'plugin_row_meta', array( $this, 'add_row_meta_links' ), 10, 4 );

		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_text_domain' ) );

		/**
		 * Fire hook after plugin initialize.
		 *
		 * @since 1.1.0
		 */
		do_action('Joinotify/Otp_Login/Init');
	}


	/**
	 * Load text domain after init hook.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_text_domain() {
		load_plugin_textdomain( 'joinotify-otp-login', false, dirname( $this->basename ) . '/languages/' );
	}


	/**
	 * Setup plugin constants.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function setup_constants() {
		$base_file = $this->plugin_file;
		$base_dir = plugin_dir_path( $base_file );
		$base_url = plugin_dir_url( $base_file );

		$constants = array(
			'JOINOTIFY_OTP_LOGIN_BASENAME'           => plugin_basename( $base_file ),
			'JOINOTIFY_OTP_LOGIN_FILE'               => $base_file,
			'JOINOTIFY_OTP_LOGIN_DIR'                => $base_dir,
			'JOINOTIFY_OTP_LOGIN_INC'                => $base_dir . 'inc/',
			'JOINOTIFY_OTP_LOGIN_URL'                => $base_url,
			'JOINOTIFY_OTP_LOGIN_ASSETS'             => $base_url . 'assets/',
			'JOINOTIFY_OTP_LOGIN_ABSPATH'            => dirname( $base_file ) . '/',
			'JOINOTIFY_OTP_LOGIN_ADMIN_EMAIL'        => get_option('admin_email'),
			'JOINOTIFY_OTP_LOGIN_DOCS_URL'           => 'https://ajuda.meumouse.com/docs/joinotify-otp-login/overview',
			'JOINOTIFY_OTP_LOGIN_SLUG'               => 'joinotify-otp-login',
			'JOINOTIFY_OTP_LOGIN_VERSION'            => $this->plugin_version,
			'JOINOTIFY_OTP_LOGIN_DEV_MODE'           => true,
		);

		foreach ( $constants as $key => $value ) {
			if ( ! defined( $key ) ) {
				define( $key, $value );
			}
		}
	}


	/**
	 * PHP version notice.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function php_version_notice() {
		$class = 'notice notice-error is-dismissible';
		$message = __( '<strong>Joinotify OTP Login</strong> requer a versão do PHP 7.4 ou maior. Contate o suporte da sua hospedagem para realizar a atualização.', 'joinotify-otp-login' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
	}


	/**
	 * Register class instantiation handlers for each mapped hook.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function register_hooked_classes() {
        $hook_classes = array(
			'init' => array(
				'MeuMouse\\Joinotify\\Otp_Login\\Core\\Assets',
			),
			'wp_loaded' => array(
				'MeuMouse\\Joinotify\\Otp_Login\\Core\\Ajax',
			),
		);

		foreach ( $hook_classes as $hook => $classes ) {
			if ( ! is_string( $hook ) || empty( $hook ) || ! is_array( $classes ) || empty( $classes ) ) {
				continue;
			}

			add_action( $hook, function() use ( $classes ) {
				foreach ( $classes as $class ) {
					$this->safe_instance_class( $class );
				}
			}, 10 );
		}
	}


	/**
	 * Safely instance a single class with validation.
	 *
	 * @since 1.0.0
	 * @param string $class | Full class name with namespace.
	 * @return mixed|null Returns the class instance or null on failure.
	 */
	private function safe_instance_class( $class ) {
		if ( ! is_string( $class ) || empty( trim( $class ) ) ) {
			return null;
		}

		// Only allow Joinotify OTP Login namespace classes.
		if ( strpos( $class, 'MeuMouse\\Joinotify\\Otp_Login\\' ) !== 0 ) {
			return null;
		}

		if ( isset( $this->instantiated_classes[ $class ] ) ) {
			return $this->instantiated_classes[ $class ];
		}

		if ( ! class_exists( $class ) ) {
			error_log( 'Joinotify OTP Login: Class does not exist: ' . $class );
			return null;
		}

		try {
			$reflection = new ReflectionClass( $class );

			if ( ! $reflection->isInstantiable() ) {
				return null;
			}

			$constructor = $reflection->getConstructor();

			if ( $constructor && $constructor->getNumberOfRequiredParameters() > 0 ) {
				error_log( 'Joinotify OTP Login: Class requires constructor parameters: ' . $class );
				return null;
			}

			$instance = $reflection->newInstance();

			$this->instantiated_classes[ $class ] = $instance;

			if ( method_exists( $instance, 'init' ) ) {
				$init_method = $reflection->getMethod( 'init' );

				if ( $init_method->isPublic() && ! $init_method->isStatic() ) {
					$instance->init();
				}
			}

			return $instance;

		} catch ( ReflectionException $e ) {
			error_log( 'Joinotify OTP Login: Reflection error for class ' . $class . ': ' . $e->getMessage() );

			return null;
		} catch ( Exception $e ) {
			error_log( 'Joinotify OTP Login: Error instantiating class ' . $class . ': ' . $e->getMessage() );

			return null;
		}
	}


	/**
	 * Plugin action links.
	 *
	 * @since 1.0.0
	 * @version 1.3.0
	 * @param array $action_links Default plugin action links.
	 * @return array
	 */
	public function add_action_plugin_links( $action_links ) {
		$plugins_links = array(
            '<a href="' . admin_url( 'admin.php?page=joinotify' ) . '">' . __( 'Configurar', 'joinotify-otp-login' ) . '</a>',
        );

		return array_merge( $plugins_links, $action_links );
	}


	/**
	 * Add meta links on plugin.
	 *
	 * @since 1.0.0
	 * @version 1.3.0
	 * @param array  $plugin_meta An array of the plugin's metadata.
	 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
	 * @param array  $plugin_data An array of plugin data.
	 * @param string $status      Status filter currently applied to the plugin list.
	 * @return array
	 */
	public function add_row_meta_links( $plugin_meta, $plugin_file, $plugin_data, $status ) {
		if ( strpos( $plugin_file, $this->basename ) !== false ) {
			$new_links = array(
				'docs' => '<a href="' . esc_attr( JOINOTIFY_OTP_LOGIN_DOCS_URL ) . '" target="_blank">' . __( 'Documentação', 'joinotify-otp-login' ) . '</a>',
			);

			$plugin_meta = array_merge( $plugin_meta, $new_links );
		}

		return $plugin_meta;
	}
}