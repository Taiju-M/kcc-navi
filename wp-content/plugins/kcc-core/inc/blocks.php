<?php
/**
 * 動的ブロックの登録（block.json + render.php ベース。ACF Pro 不要）。
 *
 * @package KccCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function kcc_register_blocks(): void {
	$blocks_dir = KCC_CORE_PATH . 'blocks';

	if ( ! is_dir( $blocks_dir ) ) {
		return;
	}

	$dirs = glob( $blocks_dir . '/*', GLOB_ONLYDIR );

	if ( empty( $dirs ) ) {
		return;
	}

	foreach ( $dirs as $dir ) {
		if ( file_exists( $dir . '/block.json' ) ) {
			register_block_type( $dir );
		}
	}
}
add_action( 'init', 'kcc_register_blocks' );

/**
 * 比較表ブロックで使う service データを取得して整形。
 * テンプレ（render.php）から呼ぶ純粋寄りのデータ取得関数。
 *
 * @param string $service_type service_type タームのスラッグ。
 * @return array<int, array<string, mixed>>
 */
function kcc_get_services_for_table( string $service_type = 'card' ): array {
	$query = new WP_Query(
		array(
			'post_type'      => 'service',
			'posts_per_page' => 100,
			'post_status'    => 'publish',
			'tax_query'      => array(
				array(
					'taxonomy' => 'service_type',
					'field'    => 'slug',
					'terms'    => $service_type,
				),
			),
			'meta_key'       => 'priority',
			'orderby'        => 'meta_value_num',
			'order'          => 'DESC',
		)
	);

	$rows = array();

	foreach ( $query->posts as $post ) {
		$id = $post->ID;

		$affiliate_url = get_field( 'affiliate_url', $id );
		$cta_url       = kcc_get_bridge_url( $id );

		$rows[] = array(
			'id'             => $id,
			'title'          => get_the_title( $id ),
			'permalink'      => get_permalink( $id ),
			'image'          => (string) get_the_post_thumbnail_url( $id, 'medium_large' ),
			'cta_url'        => $cta_url,
			'is_affiliate'   => (bool) $affiliate_url,
			'available_japan' => (bool) get_field( 'available_japan', $id ),
			'available_overseas_jp' => (bool) get_field( 'available_overseas_jp', $id ),
			'verify_status'  => (string) get_field( 'verify_status', $id ),
			'has_physical'   => (bool) get_field( 'card_has_physical', $id ),
			'cashback'       => (string) get_field( 'card_cashback', $id ),
			'issue_fee'      => (string) get_field( 'card_issue_fee', $id ),
			'annual_fee'     => (string) get_field( 'card_annual_fee', $id ),
			'priority'       => (int) get_field( 'priority', $id ),
			'last_verified'  => (string) get_field( 'last_verified', $id ),
		);
	}

	wp_reset_postdata();

	return $rows;
}

/**
 * 還元率文字列から数値（%）を抽出。ソート用キー。
 */
function kcc_parse_percent( string $value ): float {
	if ( preg_match( '/([0-9]+(?:\.[0-9]+)?)\s*%/u', $value, $m ) ) {
		return (float) $m[1];
	}
	return 0.0;
}

/**
 * 手数料文字列から数値を抽出（「無料」「Free」は 0）。ソート用キー。
 */
function kcc_parse_fee( string $value ): float {
	$normalized = mb_strtolower( trim( $value ) );
	if ( '' === $normalized || str_contains( $normalized, '無料' ) || str_contains( $normalized, 'free' ) ) {
		return 0.0;
	}
	if ( preg_match( '/([0-9]+(?:\.[0-9]+)?)/u', $normalized, $m ) ) {
		return (float) $m[1];
	}
	return PHP_INT_MAX;
}
