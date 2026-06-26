<?php
/**
 * kcc-navi テーマのセットアップ。
 *
 * @package KccNavi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_theme_file_path( 'inc/schema.php' );

function kcc_navi_setup(): void {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'post-thumbnails' );
}
add_action( 'after_setup_theme', 'kcc_navi_setup' );

function kcc_navi_assets(): void {
	wp_enqueue_style(
		'kcc-navi',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);

	wp_enqueue_script(
		'kcc-comparison-table',
		get_theme_file_uri( 'assets/js/comparison-table.js' ),
		array(),
		wp_get_theme()->get( 'Version' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'kcc_navi_assets' );

/**
 * GA4 gtag を head に注入。測定IDは定数 KCC_GA4_MEASUREMENT_ID（wp-config / .wp-env.json）から。
 * 未設定なら何も出力しない（誤計測防止）。
 */
function kcc_navi_ga4(): void {
	$measurement_id = defined( 'KCC_GA4_MEASUREMENT_ID' ) ? KCC_GA4_MEASUREMENT_ID : '';

	if ( '' === $measurement_id ) {
		return;
	}

	$id = esc_js( $measurement_id );
	printf(
		'<script async src="https://www.googletagmanager.com/gtag/js?id=%s"></script>',
		esc_attr( $measurement_id )
	);
	echo '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("js",new Date());gtag("config","' . $id . '");</script>';
}
add_action( 'wp_head', 'kcc_navi_ga4' );
