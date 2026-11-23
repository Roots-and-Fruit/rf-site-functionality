<?php
/**
 * Settings Tab Component
 *
 * @package    Rf_Site_Functionality
 * @subpackage Rf_Site_Functionality/admin/partials/tabs
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

$mc_codemirror_theme = get_option( 'mc_codemirror_theme', 'default' );
$mc_theme_preview    = "<?php\nadd_shortcode( 'hello', function () {\n\treturn '<p class=\"hi\">Hello</p>';\n} );\n";
$mc_store            = new Rf_Site_Functionality_Snippet_Store();
$mc_safe_mode        = $mc_store->is_disabled();
$mc_safe_on          = _x( 'On', 'toggle state', 'rf-site-functionality' );
$mc_safe_off         = _x( 'Off', 'toggle state', 'rf-site-functionality' );
?>

<div class="rf-site-functionality-settings">
	<div class="mc-settings__head">
		<h2 class="mc-settings__title"><?php esc_html_e( 'Plugin Settings', 'rf-site-functionality' ); ?></h2>
		<button type="submit" class="button button-primary" form="mc-codemirror-settings"><?php esc_html_e( 'Save Settings', 'rf-site-functionality' ); ?></button>
	</div>

	<section class="mc-setting">
		<h3><?php esc_html_e( 'CodeMirror Theme', 'rf-site-functionality' ); ?></h3>
		<p class="mc-setting__description">
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %s: URL of the Site Health screen. */
					__( 'Choose the theme for the code editor. Changes will apply to new editing sessions. The editor warns when a snippet looks like it would exceed PHP\'s limits. See <a href="%s">Site Health</a> when those limits get tight.', 'rf-site-functionality' ),
					esc_url( admin_url( 'site-health.php' ) )
				),
				array(
					'a' => array(
						'href' => true,
					),
				)
			);
			?>
		</p>
		<form id="mc-codemirror-settings" method="post" action="options.php">
			<?php settings_fields( 'rf_site_functionality_settings' ); ?>
			<?php do_settings_sections( 'rf_site_functionality_settings' ); ?>
			<div class="mc-setting__row">
				<div class="mc-setting__field">
					<label class="mc-setting__label" for="mc_codemirror_theme"><?php esc_html_e( 'Editor theme', 'rf-site-functionality' ); ?></label>
					<select name="mc_codemirror_theme" id="mc_codemirror_theme">
						<option value="default" <?php selected( $mc_codemirror_theme, 'default' ); ?>><?php esc_html_e( 'Default', 'rf-site-functionality' ); ?></option>
						<option value="darcula" <?php selected( $mc_codemirror_theme, 'darcula' ); ?>><?php esc_html_e( 'Darcula', 'rf-site-functionality' ); ?></option>
						<option value="eclipse" <?php selected( $mc_codemirror_theme, 'eclipse' ); ?>><?php esc_html_e( 'Eclipse', 'rf-site-functionality' ); ?></option>
						<option value="material" <?php selected( $mc_codemirror_theme, 'material' ); ?>><?php esc_html_e( 'Material', 'rf-site-functionality' ); ?></option>
						<option value="mdn-like" <?php selected( $mc_codemirror_theme, 'mdn-like' ); ?>><?php esc_html_e( 'MDN-like', 'rf-site-functionality' ); ?></option>
						<option value="monokai" <?php selected( $mc_codemirror_theme, 'monokai' ); ?>><?php esc_html_e( 'Monokai', 'rf-site-functionality' ); ?></option>
					</select>
				</div>
				<div class="mc-settings-theme__preview">
					<label class="screen-reader-text" for="mc-theme-preview"><?php esc_html_e( 'Theme preview', 'rf-site-functionality' ); ?></label>
					<textarea id="mc-theme-preview" readonly><?php echo esc_textarea( $mc_theme_preview ); ?></textarea>
				</div>
			</div>
		</form>
	</section>

	<section class="mc-setting">
		<h3><?php esc_html_e( 'Safe Mode', 'rf-site-functionality' ); ?></h3>
		<p class="mc-setting__description" id="mc-safe-mode-desc"><?php esc_html_e( 'Safe mode stops every snippet from running. Turn it off here when you want them to load again.', 'rf-site-functionality' ); ?></p>
		<form id="mc-safe-mode-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="rf_site_functionality_safe_mode" />
			<?php wp_nonce_field( 'rf_site_functionality_safe_mode' ); ?>
			<input type="hidden" name="mc_safe_mode" value="0" />
			<div class="mc-setting__control">
				<label class="mc-setting__label" id="mc-safe-mode-label" for="mc_safe_mode"><?php esc_html_e( 'Keep snippets off', 'rf-site-functionality' ); ?></label>
				<label class="mc-rf-toggle">
					<input class="mc-rf-toggle__input mc-safe-mode-toggle" type="checkbox" name="mc_safe_mode" id="mc_safe_mode" value="1" aria-labelledby="mc-safe-mode-label mc-safe-mode-state" aria-describedby="mc-safe-mode-desc" <?php checked( $mc_safe_mode ); ?> />
					<span class="mc-rf-toggle__track" aria-hidden="true"></span>
					<span class="mc-rf-toggle__state" id="mc-safe-mode-state" data-enabled="<?php echo esc_attr( $mc_safe_on ); ?>" data-disabled="<?php echo esc_attr( $mc_safe_off ); ?>"><?php echo esc_html( $mc_safe_mode ? $mc_safe_on : $mc_safe_off ); ?></span>
				</label>
			</div>
		</form>
	</section>

	<section class="mc-setting">
		<h3><?php esc_html_e( 'Snippet Storage', 'rf-site-functionality' ); ?></h3>
		<p class="mc-setting__description"><?php esc_html_e( 'Site Functionality picks the safest writable folder automatically. These details are read-only for support and verification.', 'rf-site-functionality' ); ?></p>
		<?php
		$mc_storage      = function_exists( 'rf_site_functionality_storage_info' ) ? rf_site_functionality_storage_info() : array(
			'dir'             => $mc_store->get_dir(),
			'tier'            => 'custom',
			'fallback_reason' => '',
			'inside_web_root' => false,
			'writable'        => '' !== $mc_store->get_dir() && is_dir( $mc_store->get_dir() ) && is_writable( $mc_store->get_dir() ),
		);
		$mc_force_probe  = isset( $_GET['rf_recheck_storage'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['rf_recheck_storage'] ) );
		$mc_probe_status = $mc_store->probe_web_access( $mc_storage, $mc_force_probe );
		$mc_writable     = '' !== $mc_store->get_dir() && is_dir( $mc_store->get_dir() ) && is_writable( $mc_store->get_dir() );

		$mc_tier_labels = array(
			'outside_root' => __( 'Private directory outside the web root', 'rf-site-functionality' ),
			'wp_content'   => __( 'Locked-down directory in wp-content/rf-snippets', 'rf-site-functionality' ),
			'custom'       => __( 'Custom directory (RF_SITE_FUNCTIONALITY_SNIPPETS_DIR)', 'rf-site-functionality' ),
			'unwritable'   => __( 'Not writable', 'rf-site-functionality' ),
		);
		$mc_tier_label = isset( $mc_tier_labels[ $mc_storage['tier'] ] ) ? $mc_tier_labels[ $mc_storage['tier'] ] : $mc_storage['tier'];

		$mc_reason_labels = array(
			'open_basedir'        => __( 'PHP open_basedir restricts access outside the web root.', 'rf-site-functionality' ),
			'parent_not_writable' => __( 'The parent directory of the web root is not writable by PHP.', 'rf-site-functionality' ),
			'dir_not_writable'    => __( 'The sibling directory outside the web root is not writable by PHP.', 'rf-site-functionality' ),
			'mkdir_failed'        => __( 'PHP could not create a sibling directory next to the web root.', 'rf-site-functionality' ),
		);
		$mc_reason_text = ( ! empty( $mc_storage['fallback_reason'] ) && isset( $mc_reason_labels[ $mc_storage['fallback_reason'] ] ) )
			? $mc_reason_labels[ $mc_storage['fallback_reason'] ]
			: '';

		$mc_probe_labels = array(
			'outside_root' => __( 'Protected — directory is outside the web root.', 'rf-site-functionality' ),
			'blocked'      => __( 'Protected — direct HTTP requests are blocked or return empty.', 'rf-site-functionality' ),
			'unverified'   => __( 'Hardened (PHP-guarded files, .htaccess, web.config, index.php) — loopback check unavailable on this host.', 'rf-site-functionality' ),
			'exposed'      => __( 'Action recommended — your web server served raw PHP source from this folder.', 'rf-site-functionality' ),
			'unwritable'   => __( 'Unavailable — directory is not writable.', 'rf-site-functionality' ),
		);
		$mc_probe_label = isset( $mc_probe_labels[ $mc_probe_status ] ) ? $mc_probe_labels[ $mc_probe_status ] : $mc_probe_status;
		?>
		<dl class="mc-storage-meta">
			<div class="mc-storage-meta__row">
				<dt><?php esc_html_e( 'Location', 'rf-site-functionality' ); ?></dt>
				<dd><code><?php echo esc_html( $mc_store->get_dir() ); ?></code></dd>
			</div>
			<div class="mc-storage-meta__row">
				<dt><?php esc_html_e( 'Storage mode', 'rf-site-functionality' ); ?></dt>
				<dd><?php echo esc_html( $mc_tier_label ); ?></dd>
			</div>
			<?php if ( '' !== $mc_reason_text ) : ?>
			<div class="mc-storage-meta__row">
				<dt><?php esc_html_e( 'Fallback reason', 'rf-site-functionality' ); ?></dt>
				<dd><?php echo esc_html( $mc_reason_text ); ?></dd>
			</div>
			<?php endif; ?>
			<div class="mc-storage-meta__row">
				<dt><?php esc_html_e( 'Status', 'rf-site-functionality' ); ?></dt>
				<dd><?php echo esc_html( $mc_writable ? __( 'Writable', 'rf-site-functionality' ) : __( 'Not writable', 'rf-site-functionality' ) ); ?></dd>
			</div>
			<div class="mc-storage-meta__row">
				<dt><?php esc_html_e( 'Web access', 'rf-site-functionality' ); ?></dt>
				<dd>
					<?php echo esc_html( $mc_probe_label ); ?>
					<?php if ( ! empty( $mc_storage['inside_web_root'] ) && $mc_writable ) : ?>
						<a class="mc-storage-meta__recheck" href="<?php echo esc_url( add_query_arg( array( 'page' => 'rf-site-functionality', 'tab' => 'settings', 'rf_recheck_storage' => '1' ), admin_url( 'plugins.php' ) ) ); ?>"><?php esc_html_e( 'Re-check', 'rf-site-functionality' ); ?></a>
					<?php endif; ?>
				</dd>
			</div>
		</dl>

		<?php if ( ! $mc_writable ) : ?>
			<div class="mc-storage-callout mc-storage-callout--error" role="alert">
				<p><?php esc_html_e( 'Site Functionality cannot write to its snippet directory. Check filesystem permissions for wp-content or set RF_SITE_FUNCTIONALITY_SNIPPETS_DIR in wp-config.php.', 'rf-site-functionality' ); ?></p>
			</div>
		<?php elseif ( 'exposed' === $mc_probe_status ) : ?>
			<div class="mc-storage-callout mc-storage-callout--warning" role="status">
				<p><?php esc_html_e( 'Your web server returned raw PHP source code from the snippet folder instead of executing or blocking it. Add a web server rule to deny direct access to /wp-content/rf-snippets/:', 'rf-site-functionality' ); ?></p>
				<pre class="mc-storage-nginx"><code>location ^~ /wp-content/rf-snippets/ {
    deny all;
    return 403;
}</code></pre>
			</div>
		<?php endif; ?>
	</section>

	<?php
	require_once dirname( __DIR__, 3 ) . '/includes/class-rf-site-functionality-standalone.php';
	$mc_mu_file = WP_CONTENT_DIR . '/mu-plugins/rf-site-functionality-loader.php';
	$mc_notice  = Rf_Site_Functionality_Standalone::notice( $mc_mu_file );
	if ( '' !== $mc_notice ) {
		echo '<div class="notice notice-warning mc-settings__notice"><p>' . esc_html( $mc_notice ) . '</p></div>';
	}
	?>
</div>
