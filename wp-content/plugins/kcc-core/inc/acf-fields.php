<?php
/**
 * service CPT の ACF フィールドグループを PHP で登録（Git管理＝コードが唯一の真実）。
 * show_in_rest=true で REST に公開（Phase3 の AI更新の前提）。
 * ACF Pro 未導入環境でも致命エラーにしないため acf_add_local_field_group の存在を確認する。
 *
 * @package KccCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function kcc_register_service_fields(): void {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'                   => 'group_kcc_service',
			'title'                 => 'サービス情報',
			'show_in_rest'          => true,
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'service',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'active'                => true,
			'fields'                => array_merge(
				kcc_acf_common_fields(),
				kcc_acf_affiliate_fields(),
				kcc_acf_card_fields()
			),
		)
	);
}
add_action( 'acf/init', 'kcc_register_service_fields' );

/**
 * 共通フィールド（全 service 種別）。
 *
 * @return array<int, array<string, mixed>>
 */
function kcc_acf_common_fields(): array {
	return array(
		array(
			'key'     => 'field_kcc_tab_common',
			'label'   => '共通',
			'name'    => '',
			'type'    => 'tab',
		),
		array(
			'key'           => 'field_kcc_available_japan',
			'label'         => '日本在住可否',
			'name'          => 'available_japan',
			'type'          => 'true_false',
			'ui'            => 1,
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_available_overseas_jp',
			'label'         => '海外在住日本人 可否',
			'name'          => 'available_overseas_jp',
			'type'          => 'true_false',
			'ui'            => 1,
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_supported_currencies',
			'label'         => '対応通貨',
			'name'          => 'supported_currencies',
			'type'          => 'text',
			'instructions'  => 'カンマ区切り（例: USDT, USDC, BTC, ETH）',
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_kyc_requirement',
			'label'         => 'KYC条件',
			'name'          => 'kyc_requirement',
			'type'          => 'textarea',
			'rows'          => 2,
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_prohibited_notes',
			'label'         => '禁止表現メモ（社内用）',
			'name'          => 'prohibited_notes',
			'type'          => 'textarea',
			'rows'          => 2,
			'instructions'  => '「KYCなし」「絶対安全」等の断定を避けるための注意。表示はしない。',
			'show_in_rest'  => false,
		),
		array(
			'key'           => 'field_kcc_official_url',
			'label'         => '公式URL',
			'name'          => 'official_url',
			'type'          => 'url',
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_last_verified',
			'label'         => '最終確認日',
			'name'          => 'last_verified',
			'type'          => 'date_picker',
			'display_format' => 'Y-m-d',
			'return_format' => 'Y-m-d',
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_verify_status',
			'label'         => '確認ステータス',
			'name'          => 'verify_status',
			'type'          => 'select',
			'choices'       => array(
				'verified'  => '確認済み',
				'pending'   => '要確認',
				'outdated'  => '古い可能性',
			),
			'default_value' => 'pending',
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_priority',
			'label'         => '優先度',
			'name'          => 'priority',
			'type'          => 'number',
			'instructions'  => 'おすすめ順の基準。大きいほど上位。',
			'default_value' => 0,
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_show_in_diagnosis',
			'label'         => '診断表示可否',
			'name'          => 'show_in_diagnosis',
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => 1,
			'show_in_rest'  => true,
		),
	);
}

/**
 * アフィリエイトフィールド。
 *
 * @return array<int, array<string, mixed>>
 */
function kcc_acf_affiliate_fields(): array {
	return array(
		array(
			'key'   => 'field_kcc_tab_affiliate',
			'label' => 'アフィリエイト',
			'name'  => '',
			'type'  => 'tab',
		),
		array(
			'key'           => 'field_kcc_affiliate_type',
			'label'         => 'ASP / 直 区分',
			'name'          => 'affiliate_type',
			'type'          => 'select',
			'choices'       => array(
				'asp'    => 'ASP経由',
				'direct' => '直アフィリエイト',
				'none'   => 'なし',
			),
			'default_value' => 'none',
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_reward_condition',
			'label'         => '報酬条件',
			'name'          => 'reward_condition',
			'type'          => 'textarea',
			'rows'          => 2,
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_reward_amount',
			'label'         => '報酬単価',
			'name'          => 'reward_amount',
			'type'          => 'text',
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_cookie_days',
			'label'         => 'Cookie期間（日）',
			'name'          => 'cookie_days',
			'type'          => 'number',
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_approval_condition',
			'label'         => '承認条件',
			'name'          => 'approval_condition',
			'type'          => 'textarea',
			'rows'          => 2,
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_affiliate_url',
			'label'         => 'アフィリンク',
			'name'          => 'affiliate_url',
			'type'          => 'url',
			'instructions'  => 'Phase1は空でOK（空なら公式URLにフォールバック）。',
			'show_in_rest'  => true,
		),
	);
}

/**
 * カード種別フィールド。
 *
 * @return array<int, array<string, mixed>>
 */
function kcc_acf_card_fields(): array {
	return array(
		array(
			'key'   => 'field_kcc_tab_card',
			'label' => 'カード',
			'name'  => '',
			'type'  => 'tab',
		),
		array(
			'key'           => 'field_kcc_card_has_physical',
			'label'         => '物理カード有',
			'name'          => 'card_has_physical',
			'type'          => 'true_false',
			'ui'            => 1,
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_card_has_virtual',
			'label'         => 'バーチャルカード有',
			'name'          => 'card_has_virtual',
			'type'          => 'true_false',
			'ui'            => 1,
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_card_issue_fee',
			'label'         => '発行手数料',
			'name'          => 'card_issue_fee',
			'type'          => 'text',
			'instructions'  => '例: 無料 / $5 / 10 USDT',
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_card_annual_fee',
			'label'         => '年会費',
			'name'          => 'card_annual_fee',
			'type'          => 'text',
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_card_payment_fee',
			'label'         => '決済手数料',
			'name'          => 'card_payment_fee',
			'type'          => 'text',
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_card_atm_fee',
			'label'         => 'ATM出金手数料',
			'name'          => 'card_atm_fee',
			'type'          => 'text',
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_card_cashback',
			'label'         => '還元率',
			'name'          => 'card_cashback',
			'type'          => 'text',
			'instructions'  => '例: 1% / 最大8%',
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_card_apple_pay',
			'label'         => 'Apple Pay 対応',
			'name'          => 'card_apple_pay',
			'type'          => 'true_false',
			'ui'            => 1,
			'show_in_rest'  => true,
		),
		array(
			'key'           => 'field_kcc_card_google_pay',
			'label'         => 'Google Pay 対応',
			'name'          => 'card_google_pay',
			'type'          => 'true_false',
			'ui'            => 1,
			'show_in_rest'  => true,
		),
	);
}
