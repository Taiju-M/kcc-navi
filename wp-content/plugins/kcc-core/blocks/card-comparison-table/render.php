<?php
/**
 * カード比較表ブロックのレンダリング。
 * service_type=card を priority 降順で取得し、sort/filter 用の data 属性を付けて出力。
 * ソート・フィルタの実体は kcc-navi テーマの assets/js/comparison-table.js。
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

$kcc_yesno = static function ( bool $value ): string {
	return $value
		? '<span class="kcc-flag kcc-flag--yes" aria-label="可">✓</span>'
		: '<span class="kcc-flag kcc-flag--no" aria-label="不可">—</span>';
};
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
			<label class="kcc-chip"><input type="checkbox" data-kcc-filter="available_japan"> 日本在住可</label>
			<label class="kcc-chip"><input type="checkbox" data-kcc-filter="available_overseas_jp"> 海外在住可</label>
			<label class="kcc-chip"><input type="checkbox" data-kcc-filter="has_physical"> 物理カード有</label>
			<label class="kcc-chip"><input type="checkbox" data-kcc-filter="verified"> 確認済みのみ</label>
		</div>
	</div>

	<div class="kcc-comparison__table-wrap">
	<table class="kcc-comparison__table">
		<thead>
			<tr>
				<th>カード</th>
				<th>還元率</th>
				<th>作成費</th>
				<th>年会費</th>
				<th>日本可</th>
				<th>海外在住可</th>
				<th>物理</th>
				<th>確認</th>
				<th></th>
			</tr>
		</thead>
		<tbody data-kcc-rows>
			<?php
			$kcc_verify_labels = array(
				'verified' => '確認済み',
				'pending'  => '要確認',
				'outdated' => '古い可能性',
			);
			?>
			<?php $kcc_rank = 0; ?>
			<?php foreach ( $kcc_rows as $row ) : ?>
				<?php ++$kcc_rank; ?>
				<tr
					class="<?php echo $kcc_rank <= 3 ? 'kcc-comparison__row--top' : ''; ?>"
					data-priority="<?php echo esc_attr( (string) $row['priority'] ); ?>"
					data-cashback="<?php echo esc_attr( (string) kcc_parse_percent( $row['cashback'] ) ); ?>"
					data-issue_fee="<?php echo esc_attr( (string) kcc_parse_fee( $row['issue_fee'] ) ); ?>"
					data-annual_fee="<?php echo esc_attr( (string) kcc_parse_fee( $row['annual_fee'] ) ); ?>"
					data-available_japan="<?php echo $row['available_japan'] ? '1' : '0'; ?>"
					data-available_overseas_jp="<?php echo $row['available_overseas_jp'] ? '1' : '0'; ?>"
					data-has_physical="<?php echo $row['has_physical'] ? '1' : '0'; ?>"
					data-verified="<?php echo ( 'verified' === $row['verify_status'] ) ? '1' : '0'; ?>"
				>
					<td class="kcc-comparison__name">
						<span class="kcc-comparison__rank kcc-comparison__rank--<?php echo (int) $kcc_rank; ?>" data-kcc-rank><?php echo (int) $kcc_rank; ?></span>
						<span class="kcc-comparison__name-inner">
							<a href="<?php echo esc_url( $row['permalink'] ); ?>"><?php echo esc_html( $row['title'] ); ?></a>
							<?php if ( $row['last_verified'] ) : ?>
								<span class="kcc-comparison__verified">最終確認: <?php echo esc_html( $row['last_verified'] ); ?></span>
							<?php endif; ?>
						</span>
					</td>
					<td data-label="還元率" class="kcc-comparison__num"><?php echo $row['cashback'] ? esc_html( $row['cashback'] ) : '—'; ?></td>
					<td data-label="作成費" class="kcc-comparison__num"><?php echo $row['issue_fee'] ? esc_html( $row['issue_fee'] ) : '—'; ?></td>
					<td data-label="年会費" class="kcc-comparison__num"><?php echo $row['annual_fee'] ? esc_html( $row['annual_fee'] ) : '—'; ?></td>
					<td data-label="日本可"><?php echo $kcc_yesno( (bool) $row['available_japan'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					<td data-label="海外在住可"><?php echo $kcc_yesno( (bool) $row['available_overseas_jp'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					<td data-label="物理"><?php echo $kcc_yesno( (bool) $row['has_physical'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					<td data-label="確認">
						<?php
						$kcc_vs = $row['verify_status'];
						$kcc_vs_label = isset( $kcc_verify_labels[ $kcc_vs ] ) ? $kcc_verify_labels[ $kcc_vs ] : '要確認';
						$kcc_vs_class = in_array( $kcc_vs, array( 'verified', 'pending', 'outdated' ), true ) ? $kcc_vs : 'pending';
						?>
						<span class="kcc-comparison__verify kcc-comparison__verify--<?php echo esc_attr( $kcc_vs_class ); ?>"><?php echo esc_html( $kcc_vs_label ); ?></span>
					</td>
					<td>
						<?php if ( $row['cta_url'] ) : ?>
							<a class="kcc-comparison__cta" href="<?php echo esc_url( $row['cta_url'] ); ?>" target="_blank" rel="nofollow sponsored noopener">
								公式サイト<span aria-hidden="true">→</span>
							</a>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	</div>
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
	. wp_json_encode( $kcc_item_list_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
	. '</script>';
?>
