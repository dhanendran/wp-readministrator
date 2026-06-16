<?php
/**
 * @author            Dhanendran (https://dhanendranrajagopal.me/)
 * @link              https://dhanendranrajagopal.me/
 * @since             0.0.1
 * @package           wp-readministrator
 *
 * @wordpress-plugin
 * Plugin Name: Readministrator (Read Only Administrator)
 * Plugin URI:  https://github.com/dhanendran/wp-readministrator
 * Description: Adds a "Read Only Administrator" role. These users can view the entire wp-admin like an administrator, but cannot change anything: settings, content, users, plugins, themes, menus and comments are all read-only.
 * Version:     0.1.0
 * Author:      Dhanendran
 * Author URI:  https://dhanendranrajagopal.me/
 * License:     GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: wpreadmin
 * Requires at least: 4.7
 * Requires PHP: 7.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

define( 'WP_READMINISTRATOR_VERSION', '0.1.0' );
define( 'WP_READMINISTRATOR_ROLE', 'readministrator' );

/**
 * Core plugin class.
 *
 * The role itself only ever stores a safe Editor baseline. All of the
 * elevated "view the whole admin" capabilities are granted at runtime and the
 * write capabilities are denied at runtime, so if the plugin is deactivated the
 * role harmlessly falls back to a plain Editor instead of a stranded admin.
 *
 * @since 0.1.0
 */
class WP_Readministrator {

	/**
	 * Capabilities granted at runtime so the role can *see* the whole admin.
	 *
	 * @var string[]
	 */
	private $view_caps = array(
		'manage_options',
		'list_users',
		'activate_plugins',
		'switch_themes',
		'edit_theme_options',
		'manage_categories',
		'moderate_comments',
		'upload_files',
		'export',
		'edit_others_posts',
		'edit_published_posts',
		'edit_private_posts',
		'read_private_posts',
		'edit_pages',
		'edit_others_pages',
		'edit_published_pages',
		'edit_private_pages',
		'read_private_pages',
	);

	/**
	 * Primitive write capabilities denied at runtime.
	 *
	 * `activate_plugins` and `switch_themes` are intentionally NOT here (the
	 * screens must stay viewable); their write actions are blocked in
	 * guard_requests() instead.
	 *
	 * @var string[]
	 */
	private $denied_caps = array(
		// Users.
		'create_users',
		'delete_users',
		'remove_users',
		'promote_users',
		'add_users',
		'edit_users',
		// Plugins.
		'install_plugins',
		'update_plugins',
		'delete_plugins',
		'edit_plugins',
		// Themes.
		'install_themes',
		'update_themes',
		'delete_themes',
		'edit_themes',
		// Files & core.
		'edit_files',
		'update_core',
		'update_languages',
		// Import changes the site (export is allowed as a read-only extract).
		'import',
	);

	/**
	 * Meta (per-object) write capabilities denied at runtime.
	 *
	 * @var string[]
	 */
	private $denied_meta_caps = array(
		'edit_post',
		'delete_post',
		'publish_post',
		'edit_page',
		'delete_page',
		'edit_comment',
		'edit_term',
		'delete_term',
		'assign_term',
		'edit_user',
		'delete_user',
		'promote_user',
		'remove_user',
		'customize',
	);

	/**
	 * Options that WordPress writes in the background and must NOT be blocked,
	 * otherwise simply browsing as a read-only admin could break caching/cron.
	 *
	 * @var string[]
	 */
	private $internal_options = array(
		'cron',
		'rewrite_rules',
		'auto_updater.lock',
		'recently_activated',
		'recently_edited',
	);

	/**
	 * Register all hooks.
	 *
	 * @since 0.1.0
	 */
	public function init() {
		register_activation_hook( __FILE__, array( $this, 'ensure_role' ) );
		register_uninstall_hook( __FILE__, array( __CLASS__, 'remove_role' ) );

		add_action( 'init', array( $this, 'maybe_sync_role' ) );
		add_action( 'init', array( $this, 'define_marker' ), 0 );

		// Capability enforcement.
		add_filter( 'user_has_cap', array( $this, 'filter_caps' ), 10, 4 );
		add_filter( 'map_meta_cap', array( $this, 'filter_meta_caps' ), 10, 4 );
		add_filter( 'pre_update_option', array( $this, 'block_option_save' ), 999, 3 );
		add_filter( 'pre_update_site_option', array( $this, 'block_site_option_save' ), 999, 3 );
		add_filter( 'rest_pre_dispatch', array( $this, 'block_rest_writes' ), 10, 3 );

		// Request-level guards for the screens that stay viewable.
		add_action( 'admin_init', array( $this, 'guard_requests' ), 0 );

		// Presentation.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_style' ) );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
	}

	/* ---------------------------------------------------------------------
	 * Role management
	 * ------------------------------------------------------------------- */

	/**
	 * Create / refresh the role with a safe Editor baseline.
	 *
	 * @since 0.1.0
	 */
	public function ensure_role() {
		$editor = get_role( 'editor' );
		$caps   = $editor ? $editor->capabilities : array( 'read' => true );

		// Refresh cleanly so upgrades from older versions drop stored
		// `manage_options` (now granted only at runtime).
		remove_role( WP_READMINISTRATOR_ROLE );
		add_role( WP_READMINISTRATOR_ROLE, __( 'Read Only Administrator', 'wpreadmin' ), $caps );

		update_option( 'wpreadmin_role_version', WP_READMINISTRATOR_VERSION );
	}

	/**
	 * Re-sync the role after a plugin update without requiring reactivation.
	 *
	 * @since 0.1.0
	 */
	public function maybe_sync_role() {
		if ( get_option( 'wpreadmin_role_version' ) !== WP_READMINISTRATOR_VERSION ) {
			$this->ensure_role();
		}
	}

	/**
	 * Remove the role on uninstall.
	 *
	 * @since 0.0.2
	 */
	public static function remove_role() {
		remove_role( WP_READMINISTRATOR_ROLE );
		delete_option( 'wpreadmin_role_version' );
	}

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------- */

	/**
	 * Whether the given user is a Read Only Administrator.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_User|null $user User object.
	 * @return bool
	 */
	private function is_readministrator( $user ) {
		return ( $user instanceof WP_User ) && in_array( WP_READMINISTRATOR_ROLE, (array) $user->roles, true );
	}

	/**
	 * Whether the current user is a Read Only Administrator.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	private function current_is_readministrator() {
		return is_user_logged_in() && $this->is_readministrator( wp_get_current_user() );
	}

	/**
	 * Block the current request with a friendly read-only message.
	 *
	 * @since 0.1.0
	 */
	private function deny() {
		wp_die(
			esc_html__( 'Read Only Administrator: you are not allowed to make changes.', 'wpreadmin' ),
			esc_html__( 'Read Only', 'wpreadmin' ),
			array(
				'response'  => 403,
				'back_link' => true,
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Capability filters
	 * ------------------------------------------------------------------- */

	/**
	 * Grant view capabilities and deny write capabilities at runtime.
	 *
	 * @since 0.1.0
	 *
	 * @param array        $allcaps All capabilities of the user.
	 * @param array        $caps    Required primitive capabilities.
	 * @param array        $args    Arguments.
	 * @param WP_User|null $user    The user object.
	 * @return array
	 */
	public function filter_caps( $allcaps, $caps, $args, $user ) {
		if ( ! $this->is_readministrator( $user ) ) {
			return $allcaps;
		}

		foreach ( $this->view_caps as $cap ) {
			$allcaps[ $cap ] = true;
		}

		foreach ( $this->denied_caps as $cap ) {
			$allcaps[ $cap ] = false;
		}

		return $allcaps;
	}

	/**
	 * Deny per-object write meta capabilities.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $caps    Required primitive capabilities.
	 * @param string $cap     Meta capability being checked.
	 * @param int    $user_id User ID.
	 * @param array  $args    Arguments.
	 * @return array
	 */
	public function filter_meta_caps( $caps, $cap, $user_id, $args ) {
		if ( ! in_array( $cap, $this->denied_meta_caps, true ) ) {
			return $caps;
		}

		$user = $user_id ? get_userdata( $user_id ) : wp_get_current_user();
		if ( $this->is_readministrator( $user ) ) {
			return array( 'do_not_allow' );
		}

		return $caps;
	}

	/* ---------------------------------------------------------------------
	 * Option write guards
	 * ------------------------------------------------------------------- */

	/**
	 * Whether an option is an internal/background option we must not block.
	 *
	 * @since 0.1.0
	 *
	 * @param string $option Option name.
	 * @return bool
	 */
	private function is_internal_option( $option ) {
		if ( 0 === strpos( $option, '_transient_' ) || 0 === strpos( $option, '_site_transient_' ) ) {
			return true;
		}

		return in_array( $option, $this->internal_options, true );
	}

	/**
	 * Block option writes (covers Settings API saves on any page).
	 *
	 * @since 0.1.0
	 *
	 * @param mixed  $value     New value.
	 * @param string $option    Option name.
	 * @param mixed  $old_value Existing value.
	 * @return mixed
	 */
	public function block_option_save( $value, $option, $old_value ) {
		if ( $this->current_is_readministrator() && ! $this->is_internal_option( $option ) ) {
			return $old_value;
		}

		return $value;
	}

	/**
	 * Block network option writes.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed  $value     New value.
	 * @param string $option    Option name.
	 * @param mixed  $old_value Existing value.
	 * @return mixed
	 */
	public function block_site_option_save( $value, $option, $old_value ) {
		if ( $this->current_is_readministrator() && ! $this->is_internal_option( $option ) ) {
			return $old_value;
		}

		return $value;
	}

	/* ---------------------------------------------------------------------
	 * REST + request guards
	 * ------------------------------------------------------------------- */

	/**
	 * Block all non-read REST requests (covers the block editor, modern
	 * widgets/menus, and REST-based settings).
	 *
	 * @since 0.1.0
	 *
	 * @param mixed           $result  Response to replace the dispatch with.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 * @return mixed
	 */
	public function block_rest_writes( $result, $server, $request ) {
		if ( is_wp_error( $result ) || ! $this->current_is_readministrator() ) {
			return $result;
		}

		if ( ! in_array( $request->get_method(), array( 'GET', 'HEAD', 'OPTIONS' ), true ) ) {
			return new WP_Error(
				'wpreadmin_forbidden',
				__( 'Read Only Administrator: you are not allowed to make changes.', 'wpreadmin' ),
				array( 'status' => 403 )
			);
		}

		return $result;
	}

	/**
	 * Block classic write actions on screens whose menu we keep visible.
	 *
	 * @since 0.1.0
	 */
	public function guard_requests() {
		if ( ! $this->current_is_readministrator() ) {
			return;
		}

		global $pagenow;

		$is_post = isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) );

		// Settings API, permalinks, classic menus and classic widgets all POST
		// to these screens.
		$post_screens = array( 'options.php', 'options-permalink.php', 'nav-menus.php', 'widgets.php' );
		if ( $is_post && in_array( $pagenow, $post_screens, true ) ) {
			$this->deny();
		}

		$action  = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
		$action2 = isset( $_REQUEST['action2'] ) ? sanitize_key( wp_unslash( $_REQUEST['action2'] ) ) : '';

		$blocked = array(
			'plugins.php'  => array( 'activate', 'deactivate', 'activate-selected', 'deactivate-selected', 'delete-selected', 'update-selected' ),
			'themes.php'   => array( 'activate', 'resume', 'delete', 'enable', 'disable' ),
			'comment.php'  => array( 'editedcomment', 'deletecomment', 'trashcomment', 'untrashcomment', 'spamcomment', 'unspamcomment', 'approvecomment', 'unapprovecomment' ),
		);

		if ( isset( $blocked[ $pagenow ] ) ) {
			if ( in_array( $action, $blocked[ $pagenow ], true ) || in_array( $action2, $blocked[ $pagenow ], true ) ) {
				$this->deny();
			}
		}
	}

	/* ---------------------------------------------------------------------
	 * Presentation
	 * ------------------------------------------------------------------- */

	/**
	 * Back-compat constant some integrations / styles may rely on.
	 *
	 * @since 0.0.1
	 */
	public function define_marker() {
		if ( $this->current_is_readministrator() && ! defined( 'WP_READMIN' ) ) {
			define( 'WP_READMIN', true );
		}
	}

	/**
	 * Enqueue the admin stylesheet for read-only admins only.
	 *
	 * @since 0.0.2
	 */
	public function enqueue_style() {
		if ( $this->current_is_readministrator() ) {
			wp_enqueue_style( 'wpreadmin-style', plugin_dir_url( __FILE__ ) . 'style.css', array(), WP_READMINISTRATOR_VERSION );
		}
	}

	/**
	 * Add a body class for read-only admins.
	 *
	 * @since 0.0.1
	 *
	 * @param string $classes Space-separated body classes.
	 * @return string
	 */
	public function admin_body_class( $classes ) {
		if ( $this->current_is_readministrator() ) {
			$classes .= ' readministrator ';
		}

		return $classes;
	}

	/**
	 * Show a persistent read-only notice in the admin.
	 *
	 * @since 0.0.1
	 */
	public function admin_notice() {
		if ( ! $this->current_is_readministrator() ) {
			return;
		}

		printf(
			'<div class="notice notice-info"><p>%s</p></div>',
			esc_html__( 'You are signed in as a Read Only Administrator. You can view everything but cannot make changes.', 'wpreadmin' )
		);
	}
}

$wp_readministrator = new WP_Readministrator();
$wp_readministrator->init();
