<?php
/**
 * 自己完結 seed インポーター（サーバー投入用・一時設置）。
 * services.seed.json と content.seed.json を同ディレクトリから読み、冪等投入する。
 * 実行: wp eval-file wp-content/kcc-seed/import.php
 * 投入後はこのディレクトリごと削除すること。
 *
 * @package KccCore
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "wp-cli 経由で実行してください。\n";
	exit( 1 );
}

/* ---- サービス CPT ---- */

$services_path = __DIR__ . '/services.seed.json';

if ( ! file_exists( $services_path ) ) {
	WP_CLI::error( "seed が見つかりません: {$services_path}" );
}

$services_data = json_decode( (string) file_get_contents( $services_path ), true );

if ( ! is_array( $services_data ) || empty( $services_data['services'] ) ) {
	WP_CLI::error( 'services.seed.json の形式が不正です。' );
}

$s_created = 0;
$s_updated = 0;

foreach ( $services_data['services'] as $service ) {
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
		++$s_updated;
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
		++$s_created;
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

	WP_CLI::log( "service OK: {$title} (#{$post_id})" );
}

WP_CLI::success( "サービス投入: 新規 {$s_created} 件 / 更新 {$s_updated} 件" );

/* ---- コンテンツ（記事・法務ページ） ---- */

$content_path = __DIR__ . '/content.seed.json';

if ( ! file_exists( $content_path ) ) {
	WP_CLI::error( "seed が見つかりません: {$content_path}" );
}

$content_data = json_decode( (string) file_get_contents( $content_path ), true );

if ( ! is_array( $content_data ) || empty( $content_data['posts'] ) ) {
	WP_CLI::error( 'content.seed.json の形式が不正です。' );
}

$c_created = 0;
$c_updated = 0;

foreach ( $content_data['posts'] as $entry ) {
	$slug = $entry['slug'] ?? '';
	$type = $entry['type'] ?? 'post';
	if ( '' === $slug ) {
		continue;
	}

	$existing = get_posts(
		array(
			'post_type'      => $type,
			'name'           => $slug,
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		)
	);

	$postarr = array(
		'post_type'    => $type,
		'post_name'    => $slug,
		'post_title'   => $entry['title'] ?? $slug,
		'post_content' => $entry['content'] ?? '',
		'post_status'  => $entry['status'] ?? 'publish',
	);

	if ( ! empty( $existing ) ) {
		$postarr['ID'] = (int) $existing[0];
		$post_id       = wp_update_post( $postarr, true );
		++$c_updated;
	} else {
		$post_id = wp_insert_post( $postarr, true );
		++$c_created;
	}

	if ( is_wp_error( $post_id ) ) {
		WP_CLI::warning( "投入失敗: {$slug} — " . $post_id->get_error_message() );
		continue;
	}

	WP_CLI::log( "content OK: {$postarr['post_title']} (#{$post_id})" );
}

WP_CLI::success( "コンテンツ投入: 新規 {$c_created} 件 / 更新 {$c_updated} 件" );

WP_CLI::success( 'すべて完了。wp-content/kcc-seed/ を削除してください。' );
