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

	<?php
	require_once dirname( __DIR__, 3 ) . '/includes/class-rf-site-functionality-standalone.php';
	$mc_mu_file = WP_CONTENT_DIR . '/mu-plugins/rf-site-functionality-loader.php';
	$mc_notice  = Rf_Site_Functionality_Standalone::notice( $mc_mu_file );
	if ( '' !== $mc_notice ) {
		echo '<div class="notice notice-warning mc-settings__notice"><p>' . esc_html( $mc_notice ) . '</p></div>';
	}
	?>
</div>
