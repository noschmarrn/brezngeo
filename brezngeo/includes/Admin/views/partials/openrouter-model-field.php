<?php
/**
 * OpenRouter-specific model field. Rendered from provider.php.
 *
 * Expected in scope:
 *   $provider (OpenRouterProvider), $settings (array), $pricing_urls (array)
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$or_models           = $provider->getModels();
$or_saved_model      = $settings['models']['openrouter'] ?? '';
$or_is_custom        = $or_saved_model !== '' && ! array_key_exists( $or_saved_model, $or_models );
$or_cached_pricing   = get_transient( \BreznGEO\Providers\OpenRouterProvider::MODELS_CACHE );
$or_cache_is_array   = is_array( $or_cached_pricing );
$or_selected_pricing = ( $or_cache_is_array && isset( $or_cached_pricing[ $or_saved_model ] ) )
	? $or_cached_pricing[ $or_saved_model ]
	: null;
?>
<br><br>
<label><?php esc_html_e( 'Model:', 'brezngeo' ); ?></label>
<select name="brezngeo_settings[models][openrouter]" class="brezngeo-openrouter-model-select" id="brezngeo-openrouter-model">
	<?php if ( empty( $or_models ) ) : ?>
		<option value=""><?php esc_html_e( 'No models loaded yet — click "Load models"', 'brezngeo' ); ?></option>
	<?php else : ?>
		<?php foreach ( $or_models as $or_mid => $or_label ) : ?>
			<option value="<?php echo esc_attr( $or_mid ); ?>"
				<?php selected( $or_saved_model, $or_mid ); ?>
				data-input="<?php echo esc_attr( isset( $or_cached_pricing[ $or_mid ]['input_cost'] ) ? $or_cached_pricing[ $or_mid ]['input_cost'] : '' ); ?>"
				data-output="<?php echo esc_attr( isset( $or_cached_pricing[ $or_mid ]['output_cost'] ) ? $or_cached_pricing[ $or_mid ]['output_cost'] : '' ); ?>">
				<?php echo esc_html( $or_label ); ?>
			</option>
		<?php endforeach; ?>
	<?php endif; ?>
	<option value="__custom__" <?php selected( $or_is_custom ); ?>>
		<?php esc_html_e( 'Custom model ID…', 'brezngeo' ); ?>
	</option>
</select>
<button type="button" class="button brezngeo-openrouter-load-btn">
	<?php esc_html_e( 'Load models', 'brezngeo' ); ?>
</button>
<span class="brezngeo-openrouter-load-status" aria-live="polite"></span>

<div class="brezngeo-openrouter-custom-wrap" style="<?php echo $or_is_custom ? '' : 'display:none;'; ?>margin-top:10px;">
	<label for="brezngeo-openrouter-custom">
		<?php esc_html_e( 'Custom model ID:', 'brezngeo' ); ?>
	</label>
	<input type="text"
			id="brezngeo-openrouter-custom"
			name="brezngeo_settings[openrouter_custom_model]"
			value="<?php echo esc_attr( $or_is_custom ? $or_saved_model : '' ); ?>"
			placeholder="<?php esc_attr_e( 'e.g. anthropic/claude-opus-4.7', 'brezngeo' ); ?>"
			class="regular-text">
	<p class="description">
		<a href="https://brezngeo.com/faq.html#openrouter" target="_blank" rel="noopener">
			<?php esc_html_e( 'Learn how to find OpenRouter model IDs →', 'brezngeo' ); ?>
		</a>
	</p>
</div>

<?php if ( ! empty( $pricing_urls['openrouter'] ) ) : ?>
<p style="margin-top:8px;">
	<a href="<?php echo esc_url( $pricing_urls['openrouter'] ); ?>" target="_blank" rel="noopener noreferrer">
		<?php esc_html_e( 'Browse all OpenRouter models →', 'brezngeo' ); ?>
	</a>
</p>
<?php endif; ?>

<p style="margin-top:12px;"><strong><?php esc_html_e( 'Pricing (automatically from OpenRouter, per 1M tokens):', 'brezngeo' ); ?></strong></p>
<div class="brezngeo-openrouter-pricing-display" id="brezngeo-openrouter-pricing" style="font-size:12px;color:#555;">
	<?php if ( $or_selected_pricing ) : ?>
		Input $<span class="or-price-input"><?php echo esc_html( number_format( (float) $or_selected_pricing['input_cost'], 4 ) ); ?></span>
		/ 1M · Output $<span class="or-price-output"><?php echo esc_html( number_format( (float) $or_selected_pricing['output_cost'], 4 ) ); ?></span>
		/ 1M
	<?php elseif ( $or_is_custom ) : ?>
		<em><?php esc_html_e( 'Pricing unknown for custom models — will be populated after you click "Load models".', 'brezngeo' ); ?></em>
	<?php else : ?>
		<em><?php esc_html_e( 'Click "Load models" to fetch pricing from OpenRouter.', 'brezngeo' ); ?></em>
	<?php endif; ?>
</div>
