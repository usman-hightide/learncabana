<?php
/**
 * Helpscout implementation.
 *
 * @package TrustedLogin\Vendor
 */

namespace TrustedLogin\Vendor\Webhooks;

use TrustedLogin\Vendor\AccessKeyLogin;
use TrustedLogin\Vendor\ApiHandler;
use TrustedLogin\Vendor\FailureLog;
use TrustedLogin\Vendor\Helpers;

/**
 * Helpscout webhook handler for the REST API.
 *
 * @since 2.0.0
 */
class Helpscout extends Webhook {


	/**
	 * Get slug for this webhook.
	 *
	 * @return string
	 */
	public static function getProviderName() {
		return 'helpscout';
	}

	/**
	 * Get name for this webhook with capitals.
	 *
	 * @return string
	 */
	public static function getProviderNameCapitalized() {
		return 'HelpScout';
	}

	/**
	 * Generates the output for the Help Scout widget.
	 *
	 * Checks the `$_SERVER` array for the signature and verifies the source before checking for licenses matching to users email.
	 *
	 * @param mixed|null $data The data sent to the webhook. If null, php://input is used.
	 *
	 * @return array The response array.
	 */
	public function webhookEndpoint( $data = null ): array {

		// Get the signature from headers.
		$signature = $this->get_signature_from_headers();

		// If no data was passed in, we grab it from the input.
		$data = is_null( $data ) ? file_get_contents( 'php://input' ) : $data;

		// If there's no data or if the request cannot be verified, we return an error.
		if ( ! $data || ! $this->verify_request( $data, $signature ) ) {
			$this->failure(
				'webhook_sig_failed',
				FailureLog::SEVERITY_WARNING,
				'Webhook request rejected: ' . ( ! $data ? 'empty request body' : 'signature verification failed' ) . '.',
				__METHOD__,
				array(
					'error_code'    => $data ? 'bad_signature' : 'empty_body',
					// File-log-only context, dropped by the allowlist.
					'has_signature' => ! empty( $signature ),
					'body_length'   => is_string( $data ) ? strlen( $data ) : 0,
				)
			);

			return self::build_error_message(
				403,
				__( 'Unauthorized.', 'trustedlogin-connector' ),
				__( "Verify your site's TrustedLogin Settings match the help desk widget settings.", 'trustedlogin-connector' )
			);
		}

		$account_id = Helpers::get_post_or_get( AccessKeyLogin::ACCOUNT_ID_INPUT_NAME, 'sanitize_text_field' );

		// Validate that account_id is a positive integer. `! $account_id`
		// alone would let "abc" through, which then casts to 0 below.
		$account_id_valid = filter_var( $account_id, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );

		if ( false === $account_id_valid ) {
			$this->log(
				'Webhook request rejected: missing or invalid account_id query argument.',
				__METHOD__,
				'warning',
				array(
					'expected_param' => AccessKeyLogin::ACCOUNT_ID_INPUT_NAME,
					'received_type'  => gettype( $account_id ),
					'received_value' => is_scalar( $account_id ) ? (string) $account_id : null,
				)
			);

			return self::build_error_message(
				401,
				__( 'Missing Account ID.', 'trustedlogin-connector' ),
				__( "Verify your site's TrustedLogin Settings match the help desk widget settings.", 'trustedlogin-connector' ),
				'missing_account_id'
			);
		}

		// Decode the data from JSON. Capture the JSON error state at the.
		// point of decoding — if we wait until later log calls, subsequent.
		// json_* calls may have reset json_last_error() to "No error".
		$data_obj       = json_decode( $data, false );
		$json_error     = json_last_error();
		$json_error_msg = json_last_error_msg();

		// Extract customer emails from data.
		$customer_emails = $this->extract_customer_emails( $data_obj );

		// If there's no customer email, we return an error. The cause is.
		// either a JSON parse failure OR a successful parse with no.
		// customer.emails — log enough to distinguish the two.
		if ( ! $customer_emails ) {
			$json_decode_failed = ( JSON_ERROR_NONE !== $json_error );
			$this->log(
				'Webhook request rejected: ' . ( $json_decode_failed ? 'JSON decode failed' : 'no customer emails in payload' ) . '.',
				__METHOD__,
				'warning',
				array(
					'json_decode_failed' => $json_decode_failed,
					'json_error_msg'     => $json_decode_failed ? $json_error_msg : null,
					'payload_keys'       => is_object( $data_obj ) ? array_keys( get_object_vars( $data_obj ) ) : null,
					'has_customer'       => is_object( $data_obj ) && isset( $data_obj->customer ),
				)
			);

			return self::build_error_message(
				400,
				__( 'Unable to Process.', 'trustedlogin-connector' ),
				__( 'The help desk sent corrupted customer data. Please try refreshing the page.', 'trustedlogin-connector' )
			);
		}

		// Get response for the widget and return it.
		$return_html = $this->get_widget_response( $customer_emails, (int) $account_id );

		return array(
			'html'   => $return_html,
			'status' => 200,
		);
	}

	/**
	 * Get HTML for the Help Scout widget.
	 *
	 * @param array $customer_emails List of customer emails.
	 * @param int   $account_id Account ID.
	 *
	 * @return string The HTML response.
	 */
	protected function get_widget_response( array $customer_emails, int $account_id ): string {
		// Get licenses by customer emails.
		$licenses = $this->getLicensesByEmails( $customer_emails );

		// Get API Handler.
		$saas_api = trustedlogin_connector()->getApiHandler( $account_id );

		$html_template = $this->apply_helpdesk_template_filter(
			'wrapper',
			'<ul class="c-sb-list c-sb-list--two-line">%1$s</ul>' .
			'<a href="' . esc_url( admin_url( 'admin.php?page=' . AccessKeyLogin::PAGE_SLUG ) ) . '"><i class="icon-gear"></i>' . esc_html__( 'Go to Access Key Log-In', 'trustedlogin-connector' ) . '</a>'
		);

		$item_template = $this->apply_helpdesk_template_filter(
			'item',
			'<li class="c-sb-list-item"><span class="c-sb-list-item__label">%4$s <span class="c-sb-list-item__text"><a href="%1$s" target="_blank" rel="noopener noreferrer" title="%3$s"><i class="icon-pointer"></i> %2$s</a></span></span></li>'
		);

		$no_items_template = $this->apply_helpdesk_template_filter(
			'no-items',
			'<li class="c-sb-list-item">%1$s</li>'
		);

		// Define the API endpoint via the shared builder so path-segment
		// encoding and the trailing-slash policy stay consistent with the
		// other SaaS callers.
		$endpoint = ApiHandler::buildEndpoint( array( 'accounts', (int) $account_id, 'sites' ), array(), true );

		// Prepare search keys for the API call.
		$data = $this->prepare_search_keys( $licenses );

		// If there are any search keys, make the API call.
		if ( ! empty( $data['searchKeys'] ) ) {

			/**
			 * Expected result
			 *
			 * @var array|\WP_Error $response [
			 *   "<license_key>" => [ <secrets> ]
			 * ]
			 */
			$response = $saas_api->call( $endpoint, $data, 'POST' );

			// Response shape: true = 204 No-Content (empty render), WP_Error
			// = upstream failure (surface message in the row), array = real
			// data (render through the item template).
			if ( true === $response ) {
				$item_html = '';
			} elseif ( is_wp_error( $response ) ) {
				$item_html = $response->get_error_message();
			} else {
				// Generate item HTML for each secret in the response.
				$item_html = $this->generate_item_html( (array) $response, $item_template, $data['statuses'], $account_id );
			}

			$this->log( 'item_html: ' . $item_html, __METHOD__ );
		} else {
			// array_walk(sanitize_email) is a no-op — sanitize_email.
			// returns by value, not by reference. Use array_map.
			$sanitized = array_map( 'sanitize_email', (array) $customer_emails );
			$this->log( 'No license keys found for email ' . implode( ',', $sanitized ), __METHOD__ );
		}

		// If no item HTML was generated, use the no items template.
		if ( empty( $item_html ) ) {
			$item_html = sprintf(
				$no_items_template,
				esc_html__( 'No TrustedLogin sessions authorized for this user.', 'trustedlogin-connector' )
			);
		}

		// Return the final HTML response.
		return sprintf( $html_template, $item_html );
	}

	/**
	 * Extracts the Help Scout signature from headers.
	 *
	 * @since 0.15.0
	 *
	 * @return string|null The signature or null if not found.
	 */
	private function get_signature_from_headers(): ?string {
		// Create the provider name in uppercase.
		$provider_name = strtoupper( $this->getProviderName() );

		// Get the provider name with capitals.
		$provider_name_capitalized = $this->getProviderNameCapitalized();

		// PHP populates $_SERVER with HTTP headers as HTTP_X_PROVIDER_SIGNATURE.
		// (uppercase, dashes → underscores, HTTP_ prefix). The canonical.
		// header form X-PROVIDER-SIGNATURE never appears as a $_SERVER key.
		// on standard SAPIs, so do not waste a check on it.
		if ( isset( $_SERVER[ "HTTP_X_{$provider_name}_SIGNATURE" ] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER[ "HTTP_X_{$provider_name}_SIGNATURE" ] ) );
		}

		if ( function_exists( 'apache_request_headers' ) ) {
			$headers = apache_request_headers();

			if ( isset( $headers[ "X-{$provider_name_capitalized}-Signature" ] ) ) {
				return sanitize_text_field( wp_unslash( $headers[ "X-{$provider_name_capitalized}-Signature" ] ) );
			}
		}

		// If we couldn't find the signature, we return null.
		return null;
	}

	/**
	 * Constructs an error response.
	 *
	 * @since 0.15.0
	 *
	 * @param int         $status HTTP status code.
	 * @param string      $errorMessage Error message text.
	 * @param string      $instruction Instruction text for user.
	 * @param string|null $extraMessage Optional extra message.
	 *
	 * @return array An associative array containing the error message.
	 */
	public static function build_error_message( int $status, string $errorMessage, string $instruction, ?string $extraMessage = null ): array {
		// Generate the HTML error message with richer context so users see something useful in the widget.
		//
		// Help Scout's dynamic app sidebar strips inline CSS, JavaScript, and any HTML/elements not.
		// listed in its style guide. Only documented utility classes (e.g. `.red`, `.muted`) and.
		// supported elements (paragraphs, links, lists) survive rendering. See:.
		// https://developer.helpscout.com/apps/legacy-custom-apps/style-guide/.
		$settings_url = admin_url( 'admin.php?page=' . AccessKeyLogin::PAGE_SLUG );
		$docs_url     = 'https://docs.trustedlogin.com/Connector/troubleshooting';

		$error_text  = '<p class="red">' . esc_html( $errorMessage ) . '</p>';
		$error_text .= '<p>' . esc_html( $instruction ) . '</p>';

		$meta = sprintf(
			// translators: %d is the HTTP status code that the request would have produced.
			esc_html__( 'Error code: %d', 'trustedlogin-connector' ),
			(int) $status
		);
		if ( $extraMessage ) {
			$meta .= ' &middot; ' . esc_html( $extraMessage );
		}
		$error_text .= '<p class="muted">' . $meta . '</p>';

		$error_text .= '<p>';
		$error_text .= '<a href="' . esc_url( $settings_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Open Settings', 'trustedlogin-connector' ) . '</a>';
		$error_text .= ' &middot; ';
		$error_text .= '<a href="' . esc_url( $docs_url ) . '" target="_blank" rel="noopener">' . esc_html__( 'Troubleshooting docs', 'trustedlogin-connector' ) . '</a>';
		$error_text .= '</p>';

		// Prepare the response array. We preserve the semantic error status in the array so callers.
		// (and tests) can still inspect it, but the endpoint itself will send HTTP 200 when there's.
		// HTML to render so Help Scout's widget displays the body instead of its generic "error" label.
		$response = array(
			'html'   => $error_text,
			'status' => $status,
		);

		// If there's an extra message, we add it to the response.
		if ( $extraMessage ) {
			$response['message'] = $extraMessage;
		}

		return $response;
	}

	/**
	 * Extracts customer emails from the data object.
	 *
	 * @since 0.15.0
	 *
	 * @param mixed $data_obj The webhook data object.
	 *
	 * @return array|false The emails if found, false otherwise.
	 */
	private function extract_customer_emails( $data_obj ) {
		// Try to extract emails from different parts of the data.
		if ( isset( $data_obj->customer->emails ) && is_array( $data_obj->customer->emails ) ) {
			return $data_obj->customer->emails;
		} elseif ( isset( $data_obj->customer->email ) ) {
			return array( $data_obj->customer->email );
		}

		// If no emails were found, return false.
		return false;
	}

	/**
	 * Prepare search keys for the API call.
	 *
	 * @since 0.15.0
	 *
	 * @param array $licenses {
	 *   List of licenses.
	 *      @type object $license {
	 *          @type string $key License key.
	 *          @type string $status License status.
	 *      }
	 * }
	 *
	 * @return array Array with searchKeys and statuses keys.
	 */
	private function prepare_search_keys( array $licenses ): array {
		// Initialize the data array and statuses array.
		$data     = array( 'searchKeys' => array() );
		$statuses = array();

		// Loop through licenses.
		foreach ( $licenses as $license ) {
			// Hash the license key.
			$license_hash = hash( 'sha256', $license->key );

			// Add the hashed license key to the searchKeys array if it's not already there.
			if ( ! in_array( $license_hash, $data['searchKeys'], true ) ) {
				$data['searchKeys'][] = $license_hash;
			}

			// Add the license status to the statuses array.
			$statuses[ $license_hash ] = $license->status;
		}

		// Add the statuses array to the data array.
		$data['statuses'] = $statuses;

		// Return the data array.
		return $data;
	}

	/**
	 * Generate item HTML for each secret in the response.
	 *
	 * @param array  $response API response.
	 * @param string $item_template Item template.
	 * @param array  $statuses Array of statuses.
	 * @param int    $account_id Account ID.
	 *
	 * @return string Item HTML.
	 */
	private function generate_item_html( array $response, string $item_template, array $statuses, int $account_id ): string {
		// Initialize the item HTML string.
		$item_html = '';

		// Loop through the response array.
		foreach ( $response as $key => $secrets ) {
			// Continue to the next iteration if the current value is not an array.
			if ( ! is_array( $secrets ) ) {
				continue;
			}

			// Reverse the order of the secrets array.
			$secrets_reversed = array_reverse( $secrets, true );

			// Loop through the reversed secrets array.
			foreach ( $secrets_reversed as $secret ) {
				// Generate a URL with the account ID and access key as query parameters.
				$url = add_query_arg(
					array(
						AccessKeyLogin::ACCOUNT_ID_INPUT_NAME => $account_id,
						AccessKeyLogin::ACCESS_KEY_INPUT_NAME => $key,
					),
					admin_url( 'admin.php?page=' . AccessKeyLogin::PAGE_SLUG )
				);

				// Generate the item HTML and append it to the item HTML string.
				// The third arg lands in a title="…" attribute, so it is.
				// escaped via esc_attr rather than esc_html.
				$item_html .= sprintf(
					$item_template,
					esc_url( $url ),
					esc_html__( 'Access Website', 'trustedlogin-connector' ),
					// translators: %s is replaced with the access key.
					esc_attr( sprintf( __( 'Access Key: %s', 'trustedlogin-connector' ), $key ) ),
					// translators: %s is replaced with the license status.
					sprintf( esc_html__( 'License is %s', 'trustedlogin-connector' ), ucwords( esc_html( $statuses[ $key ] ) ) )
				);
			}
		}

		// Return the item HTML string.
		return $item_html;
	}

	/**
	 * Verifies the source of the Widget request is from Help Scout
	 *
	 * @since 0.1.0
	 *
	 * @param string $data provided via `PHP://input`.
	 * @param string $signature provided via `$_SERVER` attribute.
	 *
	 * @return bool Whether the calculated hash matches the signature provided.
	 */
	public function verify_request( $data, $signature = null ) {

		if ( ! $signature ) {
			return false;
		}

		return hash_equals(
			$signature,
			$this->makeSignature(
				is_array( $data ) ? wp_json_encode( $data ) : $data
			)
		);
	}

	/**
	 * Applies the modern `trustedlogin/connector/helpdesk/<provider>/template/<slug>`
	 * filter to a default template, then the deprecated
	 * `trustedlogin/vendor/helpdesk/...` alias so 1.x integrators keep
	 * working until they migrate.
	 *
	 * Lifts the wrapper / item / no-items template-filter pattern that
	 * the response-rendering block ran three times in a row.
	 *
	 * @since 2.0.0
	 *
	 * @param string $slug         Template slug: 'wrapper', 'item', or 'no-items'.
	 * @param string $default_html Default HTML used when no filter intercepts.
	 *
	 * @return string The (possibly filtered) HTML template.
	 */
	private function apply_helpdesk_template_filter( string $slug, string $default_html ): string {
		$modern_filter = 'trustedlogin/connector/helpdesk/' . $this->getProviderName() . '/template/' . $slug;
		$legacy_filter = 'trustedlogin/vendor/helpdesk/' . $this->getProviderName() . '/template/' . $slug;

		/**
		 * Filter: Allows changing the rendered HTML output for a helpdesk
		 * widget template slug (`wrapper`, `item`, or `no-items`).
		 *
		 * @param string $html HTML template; defaults are provider-specific.
		 */
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Hook name is built from the `trustedlogin/connector/helpdesk/...` prefix above.
		$filtered = apply_filters( $modern_filter, $default_html );

		/**
		 * Deprecated alias for the helpdesk template filter.
		 *
		 * @deprecated 1.1 Use the `trustedlogin/connector/...` filter name.
		 */
		return (string) apply_filters_deprecated( $legacy_filter, array( $filtered ), '1.1', $modern_filter );
	}
}
