<?php
/**
 * カード診断ブロックのレンダリング。
 * 5問の回答（居住地・カード形態・重視点・初期費用・確認状況）から、
 * service（card）データに対してルールベースでスコアリングし上位を提案する。
 * 採点ロジックの実体は同梱の view.js。LLM は使わない（YMYL/再現性）。
 *
 * @package KccCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$kcc_rows = kcc_get_services_for_table( 'card' );

if ( empty( $kcc_rows ) ) {
	echo '<p>診断に使えるカードがまだありません。</p>';
	return;
}

$kcc_cards = array();
foreach ( $kcc_rows as $row ) {
	$kcc_cards[] = array(
		'id'         => (int) $row['id'],
		'title'      => $row['title'],
		'permalink'  => $row['permalink'],
		'cta'        => $row['cta_url'],
		'image'      => $row['image'],
		'hue'        => abs( (int) crc32( (string) $row['title'] ) ) % 360,
		'overseas'   => $row['available_overseas_jp'] ? 1 : 0,
		'japan'      => $row['available_japan'] ? 1 : 0,
		'physical'   => $row['has_physical'] ? 1 : 0,
		'cashback'   => kcc_parse_percent( $row['cashback'] ),
		'cashbackLabel' => $row['cashback'] ? $row['cashback'] : '—',
		'issueFee'   => kcc_parse_fee( $row['issue_fee'] ),
		'annualFee'  => kcc_parse_fee( $row['annual_fee'] ),
		'verified'   => ( 'verified' === $row['verify_status'] ) ? 1 : 0,
		'priority'   => (int) $row['priority'],
	);
}

$kcc_steps = array(
	array(
		'key'      => 'residence',
		'question' => 'お住まいはどちらですか？',
		'help'     => '居住地で発行できるカードが変わります。海外在住者が使えるかを最優先で絞り込みます。',
		'options'  => array(
			array( 'value' => 'overseas', 'label' => '海外在住' ),
			array( 'value' => 'japan', 'label' => '日本在住' ),
		),
	),
	array(
		'key'      => 'form',
		'question' => 'カードの形態はどちらを希望しますか？',
		'help'     => '実店舗やATMで使うなら物理カード、オンライン決済中心ならバーチャルで十分なことが多いです。',
		'options'  => array(
			array( 'value' => 'physical', 'label' => '物理カードが欲しい' ),
			array( 'value' => 'virtual', 'label' => 'バーチャルで十分' ),
			array( 'value' => 'any', 'label' => 'どちらでもよい' ),
		),
	),
	array(
		'key'      => 'priority',
		'question' => '一番重視するのは？',
		'help'     => '重視点に合わせて還元率と維持コストの重みづけを変えて採点します。',
		'options'  => array(
			array( 'value' => 'cashback', 'label' => '還元率の高さ' ),
			array( 'value' => 'cost', 'label' => '維持コストの安さ' ),
			array( 'value' => 'balance', 'label' => 'バランス重視' ),
		),
	),
	array(
		'key'      => 'initial',
		'question' => '初期費用（カード作成費）は？',
		'help'     => '作成費が無料のカードに絞るかどうかです。',
		'options'  => array(
			array( 'value' => 'free', 'label' => '無料がいい' ),
			array( 'value' => 'ok', 'label' => '多少なら払える' ),
		),
	),
	array(
		'key'      => 'trust',
		'question' => '掲載情報の確かさは重視しますか？',
		'help'     => '編集部が公式で最終確認できているカードを優先するかどうかです。',
		'options'  => array(
			array( 'value' => 'verified', 'label' => '確認済みを優先' ),
			array( 'value' => 'any', 'label' => 'こだわらない' ),
		),
	),
);
?>
<div class="kcc-diag" data-kcc-diag>
	<script type="application/json" data-kcc-diag-data><?php echo wp_json_encode( $kcc_cards, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>

	<div class="kcc-diag__intro">
		<p class="kcc-diag__eyebrow">かんたん5問診断</p>
		<h2 class="kcc-diag__title">あなたに合う仮想通貨カードを見つける</h2>
		<p class="kcc-diag__lead">居住地・使い方・重視点を選ぶだけ。当サイトの掲載データだけを使ってルールベースで提案します（AIの当てずっぽうではありません）。</p>
	</div>

	<div class="kcc-diag__progress" data-kcc-diag-progress aria-hidden="true">
		<span class="kcc-diag__progress-bar" data-kcc-diag-bar></span>
	</div>

	<form class="kcc-diag__form" data-kcc-diag-form>
		<?php foreach ( $kcc_steps as $i => $step ) : ?>
			<fieldset class="kcc-diag__step<?php echo 0 === $i ? ' is-active' : ''; ?>" data-kcc-diag-step="<?php echo (int) $i; ?>" data-kcc-key="<?php echo esc_attr( $step['key'] ); ?>"<?php echo 0 === $i ? '' : ' hidden'; ?>>
				<legend class="kcc-diag__q">
					<span class="kcc-diag__q-num">Q<?php echo (int) $i + 1; ?><span class="kcc-diag__q-total">/<?php echo count( $kcc_steps ); ?></span></span>
					<?php echo esc_html( $step['question'] ); ?>
				</legend>
				<p class="kcc-diag__q-help"><?php echo esc_html( $step['help'] ); ?></p>
				<div class="kcc-diag__options">
					<?php foreach ( $step['options'] as $opt ) : ?>
						<label class="kcc-diag__option">
							<input type="radio" name="<?php echo esc_attr( $step['key'] ); ?>" value="<?php echo esc_attr( $opt['value'] ); ?>" data-kcc-diag-input>
							<span class="kcc-diag__option-label"><?php echo esc_html( $opt['label'] ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
				<div class="kcc-diag__nav">
					<?php if ( $i > 0 ) : ?>
						<button type="button" class="kcc-diag__back" data-kcc-diag-back>戻る</button>
					<?php endif; ?>
				</div>
			</fieldset>
		<?php endforeach; ?>
	</form>

	<div class="kcc-diag__result" data-kcc-diag-result hidden aria-live="polite">
		<div class="kcc-diag__result-head">
			<p class="kcc-diag__eyebrow">診断結果</p>
			<h3 class="kcc-diag__result-title" data-kcc-diag-result-title>あなたにおすすめのカード</h3>
		</div>
		<div class="kcc-diag__cards" data-kcc-diag-cards></div>
		<p class="kcc-diag__empty" data-kcc-diag-noresult hidden>条件に完全一致するカードが見つかりませんでした。条件をゆるめて再診断するか、<a href="#kcc-compare">比較表</a>もご覧ください。</p>
		<button type="button" class="kcc-diag__restart" data-kcc-diag-restart>最初からやり直す</button>
	</div>

	<p class="kcc-diag__disclaimer">診断は当サイト掲載の比較データに基づく目安です。最新の発行条件・手数料は必ず各公式サイトでご確認ください。投資・税務の助言ではありません。</p>
</div>
