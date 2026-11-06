// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletSummary _$WalletSummaryFromJson(Map<String, dynamic> json) =>
    WalletSummary(
      balance: json['balance'] as num,
      currency: json['currency'] as String,
      networkFee: json['network_fee'] as num,
      pendingRecharges: (json['pending_recharges'] as num).toInt(),
      pendingWithdrawals: (json['pending_withdrawals'] as num).toInt(),
      minWithdrawal: json['min_withdrawal'] as num,
      rechargeNetworks: (json['recharge_networks'] as List<dynamic>)
          .map((e) => e as String)
          .toList(),
      maxUploadKb: (json['max_upload_kb'] as num).toInt(),
    );

Map<String, dynamic> _$WalletSummaryToJson(WalletSummary instance) =>
    <String, dynamic>{
      'balance': instance.balance,
      'currency': instance.currency,
      'network_fee': instance.networkFee,
      'pending_recharges': instance.pendingRecharges,
      'pending_withdrawals': instance.pendingWithdrawals,
      'min_withdrawal': instance.minWithdrawal,
      'recharge_networks': instance.rechargeNetworks,
      'max_upload_kb': instance.maxUploadKb,
    };
