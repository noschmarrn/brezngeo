<?php
namespace BreznGEO\Providers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OpenRouterProvider implements ProviderInterface {
	public const API_URL       = 'https://openrouter.ai/api/v1/chat/completions';
	public const MODELS_URL    = 'https://openrouter.ai/api/v1/models';
	public const MODELS_CACHE  = 'brezngeo_openrouter_models';
	public const FALLBACK_TEST = 'openai/gpt-4o-mini';

	public function getId(): string {
		return 'openrouter'; }

	public function getName(): string {
		return 'OpenRouter'; }

	/**
	 * Returns cached curated Marketing/SEO models plus any saved custom model.
	 * When the cache is empty, returns an empty array — the admin view shows a "Load models" hint.
	 */
	public function getModels(): array {
		$cached = get_transient( self::MODELS_CACHE );
		$models = array();

		if ( is_array( $cached ) ) {
			foreach ( $cached as $id => $meta ) {
				if ( is_array( $meta ) && isset( $meta['label'] ) ) {
					$models[ (string) $id ] = (string) $meta['label'];
				}
			}
		}

		if ( class_exists( '\BreznGEO\Admin\SettingsPage' ) ) {
			$settings = \BreznGEO\Admin\SettingsPage::getSettings();
			$custom   = $settings['models'][ $this->getId() ] ?? '';
			if ( is_string( $custom ) && $custom !== '' && ! isset( $models[ $custom ] ) ) {
				$models[ $custom ] = $custom . ' (' . __( 'custom', 'brezngeo' ) . ')';
			}
		}

		return $models;
	}

	public function testConnection( string $api_key ): array {
		try {
			$this->generateText( 'Say "ok"', $api_key, self::FALLBACK_TEST, 5 );
			return array(
				'success' => true,
				'message' => __( 'Connection successful', 'brezngeo' ),
			);
		} catch ( \RuntimeException $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}

	public function generateText( string $prompt, string $api_key, string $model, int $max_tokens = 300 ): string {
		$response = wp_remote_post(
			self::API_URL,
			array(
				'timeout' => 30,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
					'HTTP-Referer'  => home_url( '/' ),
					'X-Title'       => 'BreznGEO',
				),
				'body'    => wp_json_encode(
					array(
						'model'      => $model,
						'messages'   => array(
							array(
								'role'    => 'user',
								'content' => $prompt,
							),
						),
						'max_tokens' => $max_tokens,
					)
				),
			)
		);

		return $this->parseResponse( $response );
	}

	private function parseResponse( $response ): string {
		if ( is_wp_error( $response ) ) {
			throw new \RuntimeException( esc_html( $response->get_error_message() ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code !== 200 ) {
			$msg = $body['error']['message'] ?? "HTTP $code";
			throw new \RuntimeException( esc_html( $msg ) );
		}

		return trim( $body['choices'][0]['message']['content'] ?? '' );
	}
}
