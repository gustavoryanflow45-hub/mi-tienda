// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_withdrawal.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletWithdrawal _$WalletWithdrawalFromJson(Map<String, dynamic> json) =>
    WalletWithdrawal(
      id: (json['id'] as num).toInt(),
      amount: json['amount'] as num,
      fee: json['fee'] as num,
      netAmount: json['net_amount'] as num,
      fullName: json['full_name'] as String,
      bankName: json['bank_name'] as String,
      accountLast4: json['account_last4'] as String,
      status: WalletStatus.fromJson(json['status'] as String),
      fromSettlement: json['from_settlement'] as bool,
      createdAt: json['created_at'] == null
          ? null
          : DateTime.parse(json['created_at'] as String),
    );

Map<String, dynamic> _$WalletWithdrawalToJson(WalletWithdrawal instance) =>
    <String, dynamic>{
      'id': instance.id,
      'amount': instance.amount,
      'fee': instance.fee,
      'net_amount': instance.netAmount,
      'full_name': instance.fullName,
      'bank_name': instance.bankName,
      'account_last4': instance.accountLast4,
      'status': _$WalletStatusEnumMap[instance.status]!,
      'from_settlement': instance.fromSettlement,
      'created_at': instance.createdAt?.toIso8601String(),
    };

const _$WalletStatusEnumMap = {
  WalletStatus.pending: 'pending',
  WalletStatus.approved: 'approved',
  WalletStatus.rejected: 'rejected',
  WalletStatus.$unknown: r'$unknown',
};
