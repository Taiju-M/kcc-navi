<?php
/**
 * カード比較ブロックのレンダリング。
 * service_type=card を priority 降順で取得し、sort/filter 用の data 属性を付けた
 * カードグリッドとして出力。ソート・フィルタの実体は kcc-navi テーマの
 * assets/js/comparison-table.js。
 *
 * @package KccCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$kcc_rows = kcc_get_services_for_table( 'card' );

if ( empty( $kcc_rows ) ) {
	echo '<p>表示できるカードがまだありません。</p>';
	return;
}

wp_enqueue_script( 'kcc-comparison-table' );

$kcc_yesno = static function ( bool $value, string $yes = '可', string $no = '不可' ): string {
	return $value
		? '<span class="kcc-flag kcc-flag--yes"><span aria-hidden="true">✓</span>' . esc_html( $yes ) . '</span>'
		: '<span class="kcc-flag kcc-flag--no"><span aria-hidden="true">—</span>' . esc_html( $no ) . '</span>';
};

$kcc_verify_labels = array(
	'verified' => '確認済み',
	'pending'  => '要確認',
	'outdated' => '古い可能性',
);
?>
<div class="kcc-comparison" id="kcc-compare" data-kcc-comparison>
	<div class="kcc-comparison__controls">
		<div class="kcc-comparison__sort">
			<label for="kcc-sort">並び替え</label>
			<select id="kcc-sort" data-kcc-sort>
				<option value="priority">おすすめ順</option>
				<option value="cashback">還元率が高い</option>
				<option value="issue_fee">作成費が安い</option>
				<option value="annual_fee">年会費が安い</option>
			</select>
		</div>
		<div class="kcc-comparison__filters" role="group" aria-label="絞り込み">
			<label class="kcc-chip"><input type="checkbox" data-kcc-filter="available_overseas_jp"> 海外在住で発行可</label>
			<label class="kcc-chip"><input type="checkbox" data-kcc-filter="available_japan"> 日本在住で発行可</label>
			<label class="kcc-chip"><input type="checkbox" data-kcc-filter="has_physical"> 物理カード有</label>
			<label class="kcc-chip"><input type="checkbox" data-kcc-filter="verified"> 確認済みのみ</label>
		</div>
	</div>

	<div class="kcc-cards" data-kcc-grid>
		<?php $kcc_rank = 0; ?>
		<?php foreach ( $kcc_rows as $row ) : ?>
			<?php
			++$kcc_rank;
			$kcc_hue       = abs( (int) crc32( (string) $row['title'] ) ) % 360;
			$kcc_vs        = $row['verify_status'];
			$kcc_vs_label  = isset( $kcc_verify_labels[ $kcc_vs ] ) ? $kcc_verify_labels[ $kcc_vs ] : '要確認';
			$kcc_vs_class  = in_array( $kcc_vs, array( 'verified', 'pending', 'outdated' ), true ) ? $kcc_vs : 'pending';
			$kcc_card_kind = $row['has_physical'] ? '物理 + バーチャル' : 'バーチャル';
			?>
			<article
				class="kcc-card<?php echo $kcc_rank <= 3 ? ' kcc-card--top' : ''; ?>"
				data-kcc-card
				data-priority="<?php echo esc_attr( (string) $row['priority'] ); ?>"
				data-cashback="<?php echo esc_attr( (string) kcc_parse_percent( $row['cashback'] ) ); ?>"
				data-issue_fee="<?php echo esc_attr( (string) kcc_parse_fee( $row['issue_fee'] ) ); ?>"
				data-annual_fee="<?php echo esc_attr( (string) kcc_parse_fee( $row['annual_fee'] ) ); ?>"
				data-available_japan="<?php echo $row['available_japan'] ? '1' : '0'; ?>"
				data-available_overseas_jp="<?php echo $row['available_overseas_jp'] ? '1' : '0'; ?>"
				data-has_physical="<?php echo $row['has_physical'] ? '1' : '0'; ?>"
				data-verified="<?php echo ( 'verified' === $row['verify_status'] ) ? '1' : '0'; ?>"
			>
				<div class="kcc-card__topline">
					<span class="kcc-card__rank<?php echo $kcc_rank <= 3 ? ' kcc-card__rank--' . (int) $kcc_rank : ''; ?>" data-kcc-rank><?php echo (int) $kcc_rank; ?></span>
					<div class="kcc-card__tags">
						<?php if ( $row['available_overseas_jp'] ) : ?>
							<span class="kcc-tag kcc-tag--hero">海外在住OK</span>
						<?php endif; ?>
						<?php if ( $row['available_japan'] ) : ?>
							<span class="kcc-tag">日本在住OK</span>
						<?php endif; ?>
						<?php if ( $row['has_physical'] ) : ?>
							<span class="kcc-tag">物理カード</span>
						<?php endif; ?>
					</div>
				</div>

				<div class="kcc-card__viz" style="--kcc-h: <?php echo (int) $kcc_hue; ?>;">
					<span class="kcc-card__viz-chip" aria-hidden="true"></span>
					<span class="kcc-card__viz-net"><?php echo esc_html( $kcc_card_kind ); ?></span>
					<span class="kcc-card__viz-name"><?php echo esc_html( $row['title'] ); ?></span>
					<span class="kcc-card__viz-foot">CRYPTO CARD</span>
				</div>

				<h3 class="kcc-card__name">
					<a href="<?php echo esc_url( $row['permalink'] ); ?>"><?php echo esc_html( $row['title'] ); ?></a>
				</h3>

				<dl class="kcc-card__specs">
					<div class="kcc-card__spec kcc-card__spec--hero">
						<dt>還元率</dt>
						<dd><?php echo $row['cashback'] ? esc_html( $row['cashback'] ) : '—'; ?></dd>
					</div>
					<div class="kcc-card__spec">
						<dt>カード作成費</dt>
						<dd><?php echo $row['issue_fee'] ? esc_html( $row['issue_fee'] ) : '—'; ?></dd>
					</div>
					<div class="kcc-card__spec">
						<dt>年会費</dt>
						<dd><?php echo $row['annual_fee'] ? esc_html( $row['annual_fee'] ) : '—'; ?></dd>
					</div>
					<div class="kcc-card__spec">
						<dt>海外在住で発行</dt>
						<dd><?php echo $kcc_yesno( (bool) $row['available_overseas_jp'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></dd>
					</div>
				</dl>

				<div class="kcc-card__meta">
					<span class="kcc-card__verify kcc-card__verify--<?php echo esc_attr( $kcc_vs_class ); ?>"><?php echo esc_html( $kcc_vs_label ); ?></span>
					<?php if ( $row['last_verified'] ) : ?>
						<span class="kcc-card__date">最終確認: <?php echo esc_html( $row['last_verified'] ); ?></span>
					<?php endif; ?>
				</div>

				<div class="kcc-card__actions">
					<?php if ( $row['cta_url'] ) : ?>
						<a class="kcc-card__cta" href="<?php echo esc_url( $row['cta_url'] ); ?>" target="_blank" rel="nofollow sponsored noopener">
							公式サイト<span aria-hidden="true">→</span>
						</a>
					<?php endif; ?>
					<a class="kcc-card__detail" href="<?php echo esc_url( $row['permalink'] ); ?>">詳細を見る</a>
				</div>
			</article>
		<?php endforeach; ?>
	</div>

	<p class="kcc-comparison__empty" data-kcc-empty hidden>条件に合うカードが見つかりませんでした。フィルタを外してお試しください。</p>

	<p class="kcc-comparison__disclaimer">
		当サイトは広告・アフィリエイトリンクを含みます。掲載情報は各公式サイトを基に作成していますが、最新の条件は必ず公式でご確認ください。投資・税務の助言ではありません。
	</p>
</div>
<?php
$kcc_item_list = array();
foreach ( $kcc_rows as $kcc_i => $kcc_row ) {
	$kcc_item_list[] = array(
		'@type'    => 'ListItem',
		'position' => $kcc_i + 1,
		'name'     => $kcc_row['title'],
		'url'      => $kcc_row['permalink'],
	);
}
$kcc_item_list_ld = array(
	'@context'        => 'https://schema.org',
	'@type'           => 'ItemList',
	'itemListElement' => $kcc_item_list,
);
echo '<script type="application/ld+json">'
	. wp_json_encode( $kcc_item_list_ld, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
	. '</script>';
?>
