<?php
/**
 * Plugin Name: Crosspost Composer
 * Description: Adds a shared, no-API composer for X, Bluesky, Threads, Mastodon, LinkedIn, and Facebook to the block editor.
 * Version: 0.3.3
 * Author: Stefan Pauwels
 * License: GPL-2.0-or-later
 * Text Domain: crosspost-composer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CROSSPOST_COMPOSER_VERSION                    = '0.3.3';
const CROSSPOST_COMPOSER_OPTION                     = 'crosspost_composer_settings';
const CROSSPOST_COMPOSER_DEFAULT_MASTODON_INSTANCE = 'https://mastodon.social';

/**
 * Return settings merged with their defaults.
 */
function crosspost_composer_get_settings() {
	$settings = wp_parse_args(
		get_option( CROSSPOST_COMPOSER_OPTION, array() ),
		array(
			'x'                 => true,
			'bluesky'           => true,
			'threads'           => true,
			'mastodon'          => true,
			'linkedin'          => true,
			'facebook'          => true,
			'mastodon_instance' => CROSSPOST_COMPOSER_DEFAULT_MASTODON_INSTANCE,
		)
	);

	if ( empty( $settings['mastodon_instance'] ) ) {
		$settings['mastodon_instance'] = CROSSPOST_COMPOSER_DEFAULT_MASTODON_INSTANCE;
	}

	return $settings;
}

/**
 * Validate settings before saving them.
 */
function crosspost_composer_sanitize_settings( $input ) {
	$input    = is_array( $input ) ? $input : array();
	$instance = isset( $input['mastodon_instance'] ) ? untrailingslashit( esc_url_raw( trim( $input['mastodon_instance'] ) ) ) : '';

	if ( ! $instance ) {
		$instance = CROSSPOST_COMPOSER_DEFAULT_MASTODON_INSTANCE;
	}

	if ( ! wp_http_validate_url( $instance ) ) {
		add_settings_error(
			CROSSPOST_COMPOSER_OPTION,
			'invalid_mastodon_instance',
			__( 'Enter a valid Mastodon instance URL, including https://.', 'crosspost-composer' )
		);
		$instance = CROSSPOST_COMPOSER_DEFAULT_MASTODON_INSTANCE;
	}

	return array(
		'x'                 => ! empty( $input['x'] ),
		'bluesky'           => ! empty( $input['bluesky'] ),
		'threads'           => ! empty( $input['threads'] ),
		'mastodon'          => ! empty( $input['mastodon'] ),
		'linkedin'          => ! empty( $input['linkedin'] ),
		'facebook'          => ! empty( $input['facebook'] ),
		'mastodon_instance' => $instance,
	);
}

/**
 * Register the settings page and fields.
 */
function crosspost_composer_admin_init() {
	register_setting(
		'crosspost_composer',
		CROSSPOST_COMPOSER_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'crosspost_composer_sanitize_settings',
			'default'           => array(),
		)
	);

	add_settings_section(
		'crosspost_composer_services',
		__( 'Services', 'crosspost-composer' ),
		function () {
			echo '<p>' . esc_html__( 'Enabled services appear as buttons in the post editor.', 'crosspost-composer' ) . '</p>';
		},
		'crosspost-composer'
	);

	$labels = array(
		'mastodon'  => __( 'Mastodon', 'crosspost-composer' ),
		'bluesky'   => __( 'Bluesky', 'crosspost-composer' ),
		'threads'   => __( 'Threads', 'crosspost-composer' ),
		'x'         => __( 'X', 'crosspost-composer' ),
		'linkedin'  => __( 'LinkedIn', 'crosspost-composer' ),
		'facebook'  => __( 'Facebook', 'crosspost-composer' ),
	);

	foreach ( $labels as $service => $label ) {
		add_settings_field(
			'crosspost_composer_' . $service,
			$label,
			function () use ( $service, $label ) {
				$settings = crosspost_composer_get_settings();
				printf(
					'<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s> %4$s</label>',
					esc_attr( CROSSPOST_COMPOSER_OPTION ),
					esc_attr( $service ),
					checked( ! empty( $settings[ $service ] ), true, false ),
					esc_html( sprintf( __( 'Show the %s button', 'crosspost-composer' ), $label ) )
				);
			},
			'crosspost-composer',
			'crosspost_composer_services'
		);
	}

	add_settings_field(
		'crosspost_composer_mastodon_instance',
		__( 'Mastodon instance', 'crosspost-composer' ),
		function () {
			$settings = crosspost_composer_get_settings();
			printf(
				'<input type="url" class="regular-text" name="%1$s[mastodon_instance]" value="%2$s" placeholder="https://mastodon.social"><p class="description">%3$s</p>',
				esc_attr( CROSSPOST_COMPOSER_OPTION ),
				esc_attr( $settings['mastodon_instance'] ),
				esc_html__( 'The server whose share page should open.', 'crosspost-composer' )
			);
		},
		'crosspost-composer',
		'crosspost_composer_services'
	);
}
add_action( 'admin_init', 'crosspost_composer_admin_init' );

/**
 * Add Settings > Crosspost.
 */
function crosspost_composer_admin_menu() {
	add_options_page(
		__( 'Crosspost Composer', 'crosspost-composer' ),
		__( 'Crosspost', 'crosspost-composer' ),
		'manage_options',
		'crosspost-composer',
		'crosspost_composer_settings_page'
	);
}
add_action( 'admin_menu', 'crosspost_composer_admin_menu' );

/**
 * URL of the settings page.
 */
function crosspost_composer_settings_url() {
	return admin_url( 'options-general.php?page=crosspost-composer' );
}

/**
 * Link to the settings from the Plugins list.
 */
function crosspost_composer_plugin_action_links( $links ) {
	if ( current_user_can( 'manage_options' ) ) {
		array_unshift(
			$links,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( crosspost_composer_settings_url() ),
				esc_html__( 'Settings', 'crosspost-composer' )
			)
		);
	}

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'crosspost_composer_plugin_action_links' );

/**
 * Render the settings page.
 */
function crosspost_composer_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Crosspost Composer', 'crosspost-composer' ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'crosspost_composer' );
			do_settings_sections( 'crosspost-composer' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}

/**
 * Load the composer in block editors for visible post types.
 */
function crosspost_composer_enqueue_editor_assets() {
	$screen = get_current_screen();
	if ( ! $screen || ! $screen->is_block_editor() || ! $screen->post_type ) {
		return;
	}

	$post_type = get_post_type_object( $screen->post_type );
	if ( ! $post_type || ! $post_type->show_ui ) {
		return;
	}

	$base = plugin_dir_url( __FILE__ );
	wp_enqueue_script(
		'crosspost-composer-editor',
		$base . 'editor.js',
		array( 'wp-components', 'wp-compose', 'wp-data', 'wp-edit-post', 'wp-element', 'wp-i18n', 'wp-plugins' ),
		CROSSPOST_COMPOSER_VERSION,
		true
	);
	wp_enqueue_style(
		'crosspost-composer-editor',
		$base . 'editor.css',
		array( 'wp-components' ),
		CROSSPOST_COMPOSER_VERSION
	);

	$settings = crosspost_composer_get_settings();
	wp_add_inline_script(
		'crosspost-composer-editor',
		'window.crosspostComposerSettings = ' . wp_json_encode(
			array(
				'limit'             => 280,
				'x'                 => (bool) $settings['x'],
				'bluesky'           => (bool) $settings['bluesky'],
				'threads'           => (bool) $settings['threads'],
				'mastodon'          => (bool) $settings['mastodon'],
				'linkedin'          => (bool) $settings['linkedin'],
				'facebook'          => (bool) $settings['facebook'],
				'mastodonInstance'  => $settings['mastodon_instance'],
			)
		) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'crosspost_composer_enqueue_editor_assets' );
