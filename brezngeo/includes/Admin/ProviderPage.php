<?php
namespace BreznGEO\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BreznGEO\ProviderRegistry;
use BreznGEO\Helpers\KeyVault;
use BreznGEO\Providers\OpenRouterProvider;

class ProviderPage {
	private const PRICING_URLS = array(
		'openai'     => 'https://openai.com/de-DE/api/pricing',
		'anthropic'  => 'https://platform.claude.com/docs/en/about-claude/pricing',
		'gemini'     => 'https://ai.google.dev/gemini-api/docs/pricing?hl=de',
		'grok'       => 'https://docs.x.ai/developers/models',
		'openrouter' => 'https://openrouter.ai/models',
	);

	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_brezngeo_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_brezngeo_get_default_prompt', array( $this, 'ajax_get_default_prompt' ) );
		add_action( 'wp_ajax_brezngeo_openrouter_load_models', array( $this, 'ajax_openrouter_load_models' ) );
	}

	public function register_settings(): void {
		register_setting(
			'brezngeo_provider',
			SettingsPage::OPTION_KEY_PROVIDER,
			array(
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);
	}

	public function enqueue_assets( string $hook ): void {
		if ( $hook !== 'brezngeo_page_brezngeo-provider' ) {
			return;
		}
		wp_enqueue_style( 'brezngeo-admin', BREZNGEO_URL . 'assets/admin.css', array(), BREZNGEO_VERSION );
		wp_enqueue_script( 'brezngeo-admin', BREZNGEO_URL . 'assets/admin.js', array( 'jquery' ), BREZNGEO_VERSION, true );
		wp_localize_script(
			'brezngeo-admin',
			'brezngeoAdmin',
			array(
				'nonce'        => wp_create_nonce( 'brezngeo_admin' ),
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'testing'      => __( 'Testing…', 'brezngeo' ),
				'networkError' => __( 'Network error', 'brezngeo' ),
				'resetConfirm' => __( 'Really reset the prompt?', 'brezngeo' ),
			)
		);
	}

	public function sanitize( mixed $input ): array {
		$input    = is_array( $input ) ? $input : array();
		$raw_ex   = get_option( SettingsPage::OPTION_KEY_PROVIDER, array() );
		$existing = is_array( $raw_ex ) ? $raw_ex : array();
		$clean    = array();

		$clean['provider']   = sanitize_key( $input['provider'] ?? 'openai' );
		$clean['ai_enabled'] = ! empty( $input['ai_enabled'] );

		$clean['api_keys'] = array();
		foreach ( ( $input['api_keys'] ?? array() ) as $provider_id => $raw ) {
			$provider_id = sanitize_key( $provider_id );
			$raw         = sanitize_text_field( $raw );
			if ( $raw !== '' ) {
				$clean['api_keys'][ $provider_id ] = KeyVault::encrypt( $raw );
			} elseif ( isset( $existing['api_keys'][ $provider_id ] ) ) {
				$clean['api_keys'][ $provider_id ] = $existing['api_keys'][ $provider_id ];
			}
		}
		// Preserve DB-stored keys for providers whose UI field was disabled
		// (wp-config.php constant override) and therefore never submitted.
		foreach ( ( $existing['api_keys'] ?? array() ) as $provider_id => $stored ) {
			if ( ! isset( $clean['api_keys'][ $provider_id ] ) ) {
				$clean['api_keys'][ $provider_id ] = $stored;
			}
		}

		$clean['models'] = array();
		foreach ( ( $input['models'] ?? array() ) as $provider_id => $model ) {
			$pid   = sanitize_key( $provider_id );
			$value = sanitize_text_field( $model );
			if ( $pid === 'openrouter' && $value === '__custom__' ) {
				$custom_raw = (string) ( $input['openrouter_custom_model'] ?? '' );
				$value      = sanitize_text_field( $custom_raw );
			}
			$clean['models'][ $pid ] = $value;
		}

		$clean['costs'] = array();
		foreach ( ( $input['costs'] ?? array() ) as $provider_id => $models ) {
			$provider_id = sanitize_key( $provider_id );
			foreach ( (array) $models as $model_id => $prices ) {
				$in  = (float) str_replace( ',', '.', $prices['input'] ?? '0' );
				$out = (float) str_replace( ',', '.', $prices['output'] ?? '0' );
				if ( $in > 0 || $out > 0 ) {
					$clean['costs'][ $provider_id ][ sanitize_text_field( $model_id ) ] = array(
						'input'  => max( 0.0, $in ),
						'output' => max( 0.0, $out ),
					);
				}
			}
		}

		$selected_openrouter = $clean['models']['openrouter'] ?? '';
		if ( $selected_openrouter !== '' ) {
			$cached = get_transient( \BreznGEO\Providers\OpenRouterProvider::MODELS_CACHE );
			if ( is_array( $cached ) && isset( $cached[ $selected_openrouter ] ) ) {
				$meta = $cached[ $selected_openrouter ];
				$clean['costs']['openrouter'][ $selected_openrouter ] = array(
					'input'  => (float) ( $meta['input_cost'] ?? 0 ),
					'output' => (float) ( $meta['output_cost'] ?? 0 ),
				);
			}
		}

		return $clean;
	}

	public function ajax_test_connection(): void {
		check_ajax_referer( 'brezngeo_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'brezngeo' ) );
		}
		$provider_id = sanitize_key( $_POST['provider'] ?? '' );
		$settings    = SettingsPage::getSettings();
		$api_key     = $settings['api_keys'][ $provider_id ] ?? '';
		if ( empty( $api_key ) ) {
			wp_send_json_error( __( 'No API key saved. Please save first.', 'brezngeo' ) );
		}
		$provider = ProviderRegistry::instance()->get( $provider_id );
		if ( ! $provider ) {
			wp_send_json_error( __( 'Unknown provider.', 'brezngeo' ) );
		}
		$result = $provider->testConnection( $api_key );
		if ( $result['success'] ) {
			wp_send_json_success( $result['message'] );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	public function ajax_get_default_prompt(): void {
		check_ajax_referer( 'brezngeo_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}
		wp_send_json_success( SettingsPage::getDefaultPrompt() );
	}

	public function ajax_openrouter_load_models(): void {
		check_ajax_referer( 'brezngeo_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'brezngeo' ) );
		}

		$response = wp_remote_get(
			OpenRouterProvider::MODELS_URL . '?category=marketing',
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept'       => 'application/json',
					'HTTP-Referer' => home_url( '/' ),
					'X-Title'      => 'BreznGEO',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $code !== 200 || ! isset( $body['data'] ) || ! is_array( $body['data'] ) ) {
			$msg = $body['error']['message'] ?? "HTTP $code";
			wp_send_json_error( $msg );
		}

		$normalized = array();
		foreach ( $body['data'] as $model ) {
			if ( ! is_array( $model ) || empty( $model['id'] ) ) {
				continue;
			}
			$id                = (string) $model['id'];
			$label             = isset( $model['name'] ) && is_string( $model['name'] ) && $model['name'] !== '' ? (string) $model['name'] : $id;
			$input_per_token   = isset( $model['pricing']['prompt'] ) ? (float) $model['pricing']['prompt'] : 0.0;
			$output_per_token  = isset( $model['pricing']['completion'] ) ? (float) $model['pricing']['completion'] : 0.0;
			$normalized[ $id ] = array(
				'label'       => $label,
				'input_cost'  => round( $input_per_token * 1_000_000, 4 ),
				'output_cost' => round( $output_per_token * 1_000_000, 4 ),
			);
		}

		if ( empty( $normalized ) ) {
			wp_send_json_error( __( 'No models returned by OpenRouter.', 'brezngeo' ) );
		}

		set_transient( OpenRouterProvider::MODELS_CACHE, $normalized, 12 * HOUR_IN_SECONDS );
		wp_send_json_success( $normalized );
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings     = SettingsPage::getSettings();
		$providers    = ProviderRegistry::instance()->all();
		$masked_keys  = array();
		$raw_settings = get_option( SettingsPage::OPTION_KEY_PROVIDER, array() );
		foreach ( ( $raw_settings['api_keys'] ?? array() ) as $id => $stored ) {
			$plain              = KeyVault::decrypt( $stored );
			$masked_keys[ $id ] = KeyVault::mask( $plain );
		}
		$pricing_urls = self::PRICING_URLS;
		include BREZNGEO_DIR . 'includes/Admin/views/provider.php';
	}
}
