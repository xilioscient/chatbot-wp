<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* JWT generation for WP -> LLM service */
function xilio_generate_jwt( $secret ) {
    if ( empty( $secret ) ) {
        return '';
    }
    $header = base64_encode( json_encode( array( 'alg' => 'HS256', 'typ' => 'JWT' ) ) );
    $payload = base64_encode( json_encode( array(
        'iss' => get_bloginfo( 'url' ),
        'iat' => time(),
        'exp' => time() + 60,
    ) ) );
    $sig = hash_hmac( 'sha256', "$header.$payload", $secret, true );
    $sig_enc = rtrim( strtr( base64_encode( $sig ), '+/', '-_' ), '=' );
    return "$header.$payload.$sig_enc";
}

/* Verify JWT from LLM service (if needed) */
function xilio_verify_jwt( $jwt, $secret ) {
    if ( empty( $jwt ) || empty( $secret ) ) {
        return false;
    }
    $parts = explode( '.', $jwt );
    if ( count( $parts ) !== 3 ) {
        return false;
    }
    list( $header, $payload, $sig ) = $parts;
    $expected_sig = rtrim( strtr( base64_encode( hash_hmac( 'sha256', "$header.$payload", $secret, true ) ), '+/', '-_' ), '=' );
    return hash_equals( $expected_sig, $sig );
}
