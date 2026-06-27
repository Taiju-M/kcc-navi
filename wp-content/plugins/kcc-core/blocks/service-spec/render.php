<?php
/**
 * service スペック表ブロックのレンダリング。表示中の service の ACF を出力。
 * CSS カードビジュアル + 海外在住可否ハイライト + 区分けしたスペック表。
 * 券面画像は使わず、商標を含まない CSS モックアップで視認性を確保する。
 *
 * @package KccCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$kcc_id = get_the_ID();

if ( ! $kcc_id || 'service' !== get_post_type( $kcc_id ) ) {
	return;
}

$cta_url       = kcc_get_bridge_url( $kcc_id );
$last_verified = (string) get_field( 'last_verified', $kcc_id );
$title         = get_the_title( $kcc_id );

$has_physical   = (bool) get_field( 'card_has_physical', $kcc_id );
$has_virtual    = (bool) get_field( 'card_has_virtual', $kcc_id );
$avail_japan    = (bool) get_field( 'available_japan', $kcc_id );
$avail_overseas = (bool) get_field( 'available_overseas_jp', $kcc_id );
$cashback       = (string) get_field( 'card_cashback', $kcc_id );

$kcc_hue       = abs( (int) crc32( (string) $title ) ) % 360;
$kcc_card_kind = $has_physical ? '物理 + バーチャル' : 'バーチャル';

$bool_label = static function ( bool $value, string $yes = '可', string $no = '不可' ): string {
	return $value
		? '<span class="kcc-spec__flag kcc-spec__flag--yes"><span aria-hidden="true">✓</span>' . esc_html( $yes ) . '</span>'
		: '<span class="kcc-spec__flag kcc-spec__flag--no"><span aria-hidden="true">—</span>' . esc_html( $no ) . '</span>';
};

$kcc_groups = array(
	'料金・コスト' => array(
		'還元率'       => array( 'text', (string) get_field( 'card_cashback', $kcc_id ) ),
		'カード作成費' => array( 'text', (string) get_field( 'card_issue_fee', $kcc_id ) ),
		'年会費'       => array( 'text', (string) get_field( 'card_annual_fee', $kcc_id ) ),
		'決済手数料'   => array( 'text', (string) get_field( 'card_payment_fee', $kcc_id ) ),
		'ATM手数料'    => array( 'text', (string) get_field( 'card_atm_fee', $kcc_id ) ),
	),
	'対応・機能'   => array(
		'対応通貨'   => array( 'text', (string) get_field( 'supported_currencies', $kcc_id ) ),
		'物理カード' => array( 'bool', $has_physical ),
		'バーチャル' => array( 'bool', $has_virtual ),
		'Apple Pay'  => array( 'bool', (bool) get_field( 'card_apple_pay', $kcc_id ) ),
		'Google Pay' => array( 'bool', (bool) get_field( 'card_google_pay', $kcc_id ) ),
	),
	'発行条件'     => array(
		'KYC条件'         => array( 'text', (string) get_field( 'kyc_requirement', $kcc_id ) ),
		'日本在住で発行'   => array( 'bool', $avail_japan ),
		'海外在住で発行'   => array( 'bool', $avail_overseas ),
	),
);
?>
<div class="kcc-spec">
	<div class="kcc-spec__hero">
		<div class="kcc-spec__viz" style="--kcc-h: <?php echo (int) $kcc_hue; ?>;">
			<span class="kcc-spec__viz-chip" aria-hidden="true"></span>
			<span class="kcc-spec__viz-net"><?php echo esc_html( $kcc_card_kind ); ?></span>
			<span class="kcc-spec__viz-name"><?php echo esc_html( $title ); ?></span>
			<span class="kcc-spec__viz-foot">CRYPTO CARD</span>
		</div>
		<div class="kcc-spec__hero-body">
			<?php if ( $cashback ) : ?>
				<div class="kcc-spec__headline">
					<span class="kcc-spec__headline-label">還元率</span>
					<span class="kcc-spec__headline-value"><?php echo esc_html( $cashback ); ?></span>
				</div>
			<?php endif; ?>
			<div class="kcc-spec__hero-flags">
				<div class="kcc-spec__hero-flag">
					<span class="kcc-spec__hero-flag-label">海外在住で発行</span>
					<?php echo $bool_label( $avail_overseas ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="kcc-spec__hero-flag">
					<span class="kcc-spec__hero-flag-label">日本在住で発行</span>
					<?php echo $bool_label( $avail_japan ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="kcc-spec__hero-flag">
					<span class="kcc-spec__hero-flag-label">物理カード</span>
					<?php echo $bool_label( $has_physical, '有', '無' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>
	</div>

	<?php foreach ( $kcc_groups as $kcc_group_label => $kcc_group_rows ) : ?>
		<div class="kcc-spec__group">
			<h3 class="kcc-spec__group-title"><?php echo esc_html( $kcc_group_label ); ?></h3>
			<table class="kcc-spec__table">
				<tbody>
					<?php foreach ( $kcc_group_rows as $label => $cell ) : ?>
						<tr>
							<th><?php echo esc_html( $label ); ?></th>
							<td>
								<?php
								if ( 'bool' === $cell[0] ) {
									echo $bool_label( (bool) $cell[1] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								} else {
									echo ( '' !== $cell[1] ) ? esc_html( $cell[1] ) : '—';
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endforeach; ?>

	<?php if ( $cta_url ) : ?>
		<p class="kcc-spec__cta-wrap">
			<a class="kcc-spec__cta" href="<?php echo esc_url( $cta_url ); ?>" target="_blank" rel="nofollow sponsored noopener">公式サイトでカードを発行<span aria-hidden="true">→</span></a>
		</p>
	<?php endif; ?>

	<?php if ( $last_verified ) : ?>
		<p class="kcc-spec__verified">最終確認日: <?php echo esc_html( $last_verified ); ?></p>
	<?php endif; ?>
</div>
