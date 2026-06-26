<?php
/**
 * service CPT とタクソノミーの登録。
 * すべて show_in_rest=true（Phase3 の AI更新が REST 経由で読み書きする前提）。
 *
 * @package KccCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function kcc_register_service_cpt(): void {
	$labels = array(
		'name'               => 'サービス',
		'singular_name'      => 'サービス',
		'menu_name'          => 'サービス',
		'add_new'            => '新規追加',
		'add_new_item'       => 'サービスを追加',
		'edit_item'          => 'サービスを編集',
		'new_item'           => '新規サービス',
		'view_item'          => 'サービスを表示',
		'search_items'       => 'サービスを検索',
		'not_found'          => 'サービスが見つかりません',
		'not_found_in_trash' => 'ゴミ箱にサービスはありません',
		'all_items'          => 'すべてのサービス',
	);

	register_post_type(
		'service',
		array(
			'labels'        => $labels,
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-id-alt',
			'menu_position' => 20,
			'rewrite'       => array( 'slug' => 'service' ),
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ),
			'show_in_rest'  => true,
			'rest_base'     => 'service',
		)
	);
}
add_action( 'init', 'kcc_register_service_cpt' );

function kcc_register_service_taxonomies(): void {
	register_taxonomy(
		'service_type',
		'service',
		array(
			'labels'            => array(
				'name'          => 'サービス種別',
				'singular_name' => 'サービス種別',
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'type' ),
		)
	);

	register_taxonomy(
		'supported_country',
		'service',
		array(
			'labels'            => array(
				'name'          => '対応国',
				'singular_name' => '対応国',
			),
			'public'            => true,
			'hierarchical'      => false,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'country' ),
		)
	);

	register_taxonomy(
		'card_brand',
		'service',
		array(
			'labels'            => array(
				'name'          => 'カードブランド',
				'singular_name' => 'カードブランド',
			),
			'public'            => true,
			'hierarchical'      => false,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'brand' ),
		)
	);
}
add_action( 'init', 'kcc_register_service_taxonomies' );

/**
 * service_type の初期ターム（card/exchange/wallet/tax/remittance）を冪等に用意。
 */
function kcc_seed_default_service_types(): void {
	$types = array(
		'card'        => 'カード',
		'exchange'    => '取引所',
		'wallet'      => 'ウォレット',
		'tax'         => '税務',
		'remittance'  => '送金',
	);

	foreach ( $types as $slug => $name ) {
		if ( ! term_exists( $slug, 'service_type' ) ) {
			wp_insert_term( $name, 'service_type', array( 'slug' => $slug ) );
		}
	}
}
add_action( 'init', 'kcc_seed_default_service_types', 11 );
