<?php
/**
 * services.seed.json を service CPT に冪等投入する wp-cli インポーター。
 * 実行: wp eval-file scripts/import-seed.php
 * update_field() を使い ACF のフィールドキー参照(_field)も正しく書き込む。
 *
 * @package KccCore
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "wp-cli 経由で実行してください。\n";
	exit( 1 );
}

$seed_path = dirname( __DIR__ ) . '/data/services.seed.json';

if ( ! file_exists( $seed_path ) ) {
	WP_CLI::error( "seed が見つかりません: {$seed_path}" );
}

$raw  = file_get_contents( $seed_path );
$data = json_decode( $raw, true );

if ( ! is_array( $data ) || empty( $data['services'] ) ) {
	WP_CLI::error( 'services.seed.json の形式が不正です。' );
}

$created = 0;
$updated = 0;

foreach ( $data['services'] as $service ) {
	$title = $service['title'] ?? '';
	if ( '' === $title ) {
		continue;
	}

	$existing = get_posts(
		array(
			'post_type'      => 'service',
			'title'          => $title,
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		)
	);

	if ( ! empty( $existing ) ) {
		$post_id = (int) $existing[0];
		++$updated;
	} else {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'service',
				'post_title'  => $title,
				'post_status' => 'publish',
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			WP_CLI::warning( "作成失敗: {$title} — " . $post_id->get_error_message() );
			continue;
		}
		++$created;
	}

	if ( ! empty( $service['service_type'] ) ) {
		wp_set_object_terms( $post_id, array( $service['service_type'] ), 'service_type', false );
	}
	if ( ! empty( $service['card_brand'] ) ) {
		wp_set_object_terms( $post_id, array( $service['card_brand'] ), 'card_brand', false );
	}
	if ( ! empty( $service['supported_country'] ) && is_array( $service['supported_country'] ) ) {
		wp_set_object_terms( $post_id, $service['supported_country'], 'supported_country', false );
	}

	if ( ! empty( $service['acf'] ) && is_array( $service['acf'] ) ) {
		if ( ! function_exists( 'update_field' ) ) {
			WP_CLI::warning( 'ACF 未導入のため ACF フィールドはスキップします。' );
		} else {
			foreach ( $service['acf'] as $key => $value ) {
				update_field( $key, $value, $post_id );
			}
		}
	}

	WP_CLI::log( "OK: {$title} (#{$post_id})" );
}

WP_CLI::success( "投入完了: 新規 {$created} 件 / 更新 {$updated} 件" );
