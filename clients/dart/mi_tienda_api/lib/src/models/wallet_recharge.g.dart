// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_recharge.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletRecharge _$WalletRechargeFromJson(Map<String, dynamic> json) =>
    WalletRecharge(
      id: (json['id'] as num).toInt(),
      amount: json['amount'] as num,
      fee: json['fee'] as num,
      netAmount: json['net_amount'] as num,
      network: json['network'] as String?,
      transactionId: json['transaction_id'] as String?,
      hasProof: json['has_proof'] as bool,
      status: WalletStatus.fromJson(json['status'] as String),
      createdAt: json['created_at'] == null
          ? null
          : DateTime.parse(json['created_at'] as String),
    );

Map<String, dynamic> _$WalletRechargeToJson(WalletRecharge instance) =>
    <String, dynamic>{
      'id': instance.id,
      'amount': instance.amount,
      'fee': instance.fee,
      'net_amount': instance.netAmount,
      'network': instance.network,
      'transaction_id': instance.transactionId,
      'has_proof': instance.hasProof,
      'status': _$WalletStatusEnumMap[instance.status]!,
      'created_at': instance.createdAt?.toIso8601String(),
    };

const _$WalletStatusEnumMap = {
  WalletStatus.pending: 'pending',
  WalletStatus.approved: 'approved',
  WalletStatus.rejected: 'rejected',
  WalletStatus.$unknown: r'$unknown',
};
