<?php
/**
 * アフィリンクのブリッジ（/go/{slug}）。
 *
 * 記事/比較表の CTA は外部アフィURLを直貼りせず、内部 /go/{slug} を1枚挟む。
 * - リンク先は service の ACF affiliate_url（無ければ official_url）を単一の真実として参照
 * - ブリッジページは noindex,nofollow。クリックを GA4 へ送ってから公式へリダイレクト
 * - これにより「アフィURLの一括差し替え（ACF1か所）」「クリック計測」「リンクエクイティ流出回避」を実現
 *
 * @package KccCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * リライトルールとクエリ変数を登録。
 */
function kcc_register_bridge_rewrite(): void {
	add_rewrite_rule( '^go/([^/]+)/?$', 'index.php?kcc_go=$matches[1]', 'top' );
}
add_action( 'init', 'kcc_register_bridge_rewrite' );

/**
 * @param array<int, string> $vars
 * @return array<int, string>
 */
function kcc_register_bridge_query_var( array $vars ): array {
	$vars[] = 'kcc_go';
	return $vars;
}
add_filter( 'query_vars', 'kcc_register_bridge_query_var' );

/**
 * service の遷移先URL（affiliate 優先、無ければ official）を返す。無ければ空文字。
 */
function kcc_get_bridge_destination( int $service_id ): string {
	$affiliate_url = (string) get_field( 'affiliate_url', $service_id );
	$official_url  = (string) get_field( 'official_url', $service_id );
	return '' !== $affiliate_url ? $affiliate_url : $official_url;
}

/**
 * service の内部ブリッジURL（/go/{slug}）。遷移先が無ければ空文字。
 */
function kcc_get_bridge_url( int $service_id ): string {
	if ( '' === kcc_get_bridge_destination( $service_id ) ) {
		return '';
	}
	$post = get_post( $service_id );
	if ( ! $post ) {
		return '';
	}
	return home_url( '/go/' . $post->post_name );
}

/**
 * /go/{slug} アクセス時にブリッジページを出力してリダイレクト。
 */
function kcc_handle_bridge(): void {
	$slug = get_query_var( 'kcc_go' );

	if ( '' === $slug || null === $slug ) {
		return;
	}

	$slug    = sanitize_title( (string) $slug );
	$service = get_page_by_path( $slug, OBJECT, 'service' );

	if ( ! $service ) {
		status_header( 404 );
		nocache_headers();
		wp_die( esc_html__( '対象のカードが見つかりませんでした。', 'kcc-core' ), '404', array( 'response' => 404 ) );
	}

	$dest = kcc_get_bridge_destination( $service->ID );

	if ( '' === $dest ) {
		status_header( 404 );
		nocache_headers();
		wp_die( esc_html__( 'リンク先が設定されていません。', 'kcc-core' ), '404', array( 'response' => 404 ) );
	}

	$title  = get_the_title( $service->ID );
	$source = isset( $_GET['src'] ) ? sanitize_key( wp_unslash( $_GET['src'] ) ) : 'direct';

	$ga4_id     = defined( 'KCC_GA4_MEASUREMENT_ID' ) ? (string) KCC_GA4_MEASUREMENT_ID : '';
	$js_flags   = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
	$dest_js    = wp_json_encode( $dest, $js_flags );
	$card_js    = wp_json_encode( $slug, $js_flags );
	$source_js  = wp_json_encode( $source, $js_flags );

	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
	?>
<!doctype html>
<html lang="ja">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<title><?php echo esc_html( $title ); ?>の公式サイトへ移動します</title>
	<?php if ( '' !== $ga4_id ) : ?>
		<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $ga4_id ); ?>"></script>
		<script>
			window.dataLayer = window.dataLayer || [];
			function gtag(){dataLayer.push(arguments);}
			gtag('js', new Date());
			gtag('config', <?php echo wp_json_encode( $ga4_id, $js_flags ); ?>);
		</script>
	<?php endif; ?>
	<script>
		(function () {
			var dest = <?php echo $dest_js; ?>;
			var redirected = false;
			function go() {
				if (redirected) { return; }
				redirected = true;
				window.location.replace(dest);
			}
			<?php if ( '' !== $ga4_id ) : ?>
			try {
				gtag('event', 'affiliate_click', {
					card: <?php echo $card_js; ?>,
					source: <?php echo $source_js; ?>,
					event_callback: go
				});
			} catch (e) {}
			<?php endif; ?>
			setTimeout(go, 800);
		})();
	</script>
</head>
<body style="font-family:sans-serif;max-width:560px;margin:64px auto;padding:0 16px;text-align:center;line-height:1.7;">
	<p style="font-size:13px;color:#666;">PR・広告を含みます</p>
	<h1 style="font-size:20px;"><?php echo esc_html( $title ); ?> の公式サイトへ移動します</h1>
	<p>自動的に移動します。5秒経っても移動しない場合は下のボタンから進んでください。</p>
	<p style="margin-top:24px;">
		<a href="<?php echo esc_url( $dest ); ?>" rel="nofollow sponsored noopener" style="display:inline-block;padding:12px 24px;background:#1a56db;color:#fff;text-decoration:none;border-radius:6px;">公式サイトへ進む</a>
	</p>
</body>
</html>
	<?php
	exit;
}
add_action( 'template_redirect', 'kcc_handle_bridge' );
