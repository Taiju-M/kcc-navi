<?php
/**
 * 構造化データ（JSON-LD）の出力。
 *
 * - 全ページ: Organization（運営主体）
 * - 投稿/service 個別: Article(BlogPosting) + BreadcrumbList
 * 比較表の ItemList、FAQ の FAQPage はそれぞれのブロック側で出力する。
 *
 * @package KccNavi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 運営主体ロゴURL（定数 KCC_ORG_LOGO_URL 優先、無ければサイトアイコン）。
 */
function kcc_navi_org_logo_url(): string {
	if ( defined( 'KCC_ORG_LOGO_URL' ) && '' !== (string) KCC_ORG_LOGO_URL ) {
		return (string) KCC_ORG_LOGO_URL;
	}
	$icon = get_site_icon_url();
	return $icon ? $icon : '';
}

/**
 * Organization ノード（@id 付きで他ノードから参照させる）。
 *
 * @return array<string, mixed>
 */
function kcc_navi_org_node(): array {
	$node = array(
		'@type' => 'Organization',
		'@id'   => home_url( '/#organization' ),
		'name'  => get_bloginfo( 'name' ),
		'url'   => home_url( '/' ),
	);

	$logo = kcc_navi_org_logo_url();
	if ( '' !== $logo ) {
		$node['logo'] = array(
			'@type' => 'ImageObject',
			'url'   => $logo,
		);
	}

	/**
	 * SNS など外部プロフィールURL（X等）。開設後に追加。
	 *
	 * @param array<int, string> $same_as
	 */
	$same_as = array_values( array_filter( (array) apply_filters( 'kcc_navi_org_same_as', array() ) ) );
	if ( ! empty( $same_as ) ) {
		$node['sameAs'] = $same_as;
	}

	return $node;
}

/**
 * JSON-LD を head に出力。
 */
function kcc_navi_schema(): void {
	$graph = array( kcc_navi_org_node() );

	if ( is_singular( array( 'post', 'service' ) ) ) {
		$id = get_the_ID();

		$article = array(
			'@type'            => 'BlogPosting',
			'@id'              => get_permalink( $id ) . '#article',
			'headline'         => get_the_title( $id ),
			'mainEntityOfPage' => get_permalink( $id ),
			'datePublished'    => get_the_date( 'c', $id ),
			'dateModified'     => get_the_modified_date( 'c', $id ),
			'author'           => array(
				'@type' => 'Person',
				'name'  => get_bloginfo( 'name' ) . '編集部',
			),
			'publisher'        => array( '@id' => home_url( '/#organization' ) ),
		);

		$thumb = get_the_post_thumbnail_url( $id, 'full' );
		if ( $thumb ) {
			$article['image'] = $thumb;
		}

		$graph[] = $article;

		$graph[] = array(
			'@type'           => 'BreadcrumbList',
			'itemListElement' => array(
				array(
					'@type'    => 'ListItem',
					'position' => 1,
					'name'     => 'ホーム',
					'item'     => home_url( '/' ),
				),
				array(
					'@type'    => 'ListItem',
					'position' => 2,
					'name'     => get_the_title( $id ),
					'item'     => get_permalink( $id ),
				),
			),
		);
	}

	$data = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	echo '<script type="application/ld+json">'
		. wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		. '</script>' . "\n";
}
add_action( 'wp_head', 'kcc_navi_schema' );
