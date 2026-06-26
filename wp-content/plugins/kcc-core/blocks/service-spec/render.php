<?php
/**
 * service スペック表ブロックのレンダリング。表示中の service の ACF を出力。
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

$bool_label = static function ( $value ): string {
	return $value ? '○' : '×';
};

$rows = array(
	'還元率'         => (string) get_field( 'card_cashback', $kcc_id ),
	'カード作成費'   => (string) get_field( 'card_issue_fee', $kcc_id ),
	'年会費'         => (string) get_field( 'card_annual_fee', $kcc_id ),
	'決済手数料'     => (string) get_field( 'card_payment_fee', $kcc_id ),
	'ATM手数料'      => (string) get_field( 'card_atm_fee', $kcc_id ),
	'対応通貨'       => (string) get_field( 'supported_currencies', $kcc_id ),
	'KYC条件'        => (string) get_field( 'kyc_requirement', $kcc_id ),
	'物理カード'     => $bool_label( get_field( 'card_has_physical', $kcc_id ) ),
	'バーチャル'     => $bool_label( get_field( 'card_has_virtual', $kcc_id ) ),
	'Apple Pay'      => $bool_label( get_field( 'card_apple_pay', $kcc_id ) ),
	'Google Pay'     => $bool_label( get_field( 'card_google_pay', $kcc_id ) ),
	'日本在住可'     => $bool_label( get_field( 'available_japan', $kcc_id ) ),
	'海外在住日本人可' => $bool_label( get_field( 'available_overseas_jp', $kcc_id ) ),
);
?>
<div class="kcc-spec">
	<table class="kcc-spec__table">
		<tbody>
			<?php foreach ( $rows as $label => $value ) : ?>
				<tr>
					<th><?php echo esc_html( $label ); ?></th>
					<td><?php echo ( '' !== $value ) ? esc_html( $value ) : '—'; ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( $cta_url ) : ?>
		<p class="kcc-spec__cta-wrap">
			<a class="kcc-spec__cta" href="<?php echo esc_url( $cta_url ); ?>" target="_blank" rel="nofollow noopener">公式サイトでカードを発行</a>
		</p>
	<?php endif; ?>

	<?php if ( $last_verified ) : ?>
		<p class="kcc-spec__verified">最終確認日: <?php echo esc_html( $last_verified ); ?></p>
	<?php endif; ?>
</div>
