<?php
/**
 * content.seed.json（ランキング記事・法務ページ）を冪等投入する wp-cli インポーター。
 * 実行: wp eval-file scripts/import-content.php
 *
 * @package KccCore
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	echo "wp-cli 経由で実行してください。\n";
	exit( 1 );
}

$seed_path = dirname( __DIR__ ) . '/data/content.seed.json';

if ( ! file_exists( $seed_path ) ) {
	WP_CLI::error( "seed が見つかりません: {$seed_path}" );
}

$data = json_decode( (string) file_get_contents( $seed_path ), true );

if ( ! is_array( $data ) || empty( $data['posts'] ) ) {
	WP_CLI::error( 'content.seed.json の形式が不正です。' );
}

$created = 0;
$updated = 0;

foreach ( $data['posts'] as $entry ) {
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
		++$updated;
	} else {
		$post_id = wp_insert_post( $postarr, true );
		++$created;
	}

	if ( is_wp_error( $post_id ) ) {
		WP_CLI::warning( "投入失敗: {$slug} — " . $post_id->get_error_message() );
		continue;
	}

	WP_CLI::log( "OK: {$entry['title']} (#{$post_id})" );
}

WP_CLI::success( "コンテンツ投入完了: 新規 {$created} 件 / 更新 {$updated} 件" );
