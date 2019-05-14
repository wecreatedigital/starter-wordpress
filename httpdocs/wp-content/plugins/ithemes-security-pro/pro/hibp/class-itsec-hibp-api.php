<?php

/**
 * Class ITSEC_HIBP_API
 */
class ITSEC_HIBP_API {

	const URL = 'https://api.pwnedpasswords.com/range/';

	/**
	 * Check if the password has been pwned according to HaveIBeenPwned.com
	 *
	 * @param string $plaintext The Plaintext password to check.
	 *
	 * @return int|WP_Error Number of breaches the password is in, WP_Error if error occurred.
	 */
	public static function check_breach_count( $plaintext ) {

		$hash = sha1( $plaintext );

		if ( ! $hash ) {
			return new WP_Error(
				'itsec-strong-passwords-hibp-hash-failed',
				__( 'Could not generate a sha1 hash of the password.', 'it-l10n-ithemes-security-pro' )
			);
		}

		$hash = strtoupper( $hash );

		$range  = substr( $hash, 0, 5 );
		$suffix = substr( $hash, 5 );

		$response = wp_remote_get( self::URL . $range );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( ! $body ) {
			return 0;
		}

		$maybe_suffixes = preg_split( '/\r\n|\n|\r/', $body );

		foreach ( $maybe_suffixes as $maybe_suffix ) {
			list( $maybe_suffix, $count ) = explode( ':', $maybe_suffix );

			if ( $maybe_suffix === $suffix ) {
				return (int) $count;
			}
		}

		return 0;
	}
}