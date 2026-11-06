// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_movement.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletMovement _$WalletMovementFromJson(Map<String, dynamic> json) =>
    WalletMovement(
      type: WalletMovementType.fromJson(json['type'] as String),
      id: (json['id'] as num).toInt(),
      amount: json['amount'] as num,
      status: WalletStatus.fromJson(json['status'] as String),
      occurredAt: json['occurred_at'] == null
          ? null
          : DateTime.parse(json['occurred_at'] as String),
    );

Map<String, dynamic> _$WalletMovementToJson(WalletMovement instance) =>
    <String, dynamic>{
      'type': _$WalletMovementTypeEnumMap[instance.type]!,
      'id': instance.id,
      'amount': instance.amount,
      'status': _$WalletStatusEnumMap[instance.status]!,
      'occurred_at': instance.occurredAt?.toIso8601String(),
    };

const _$WalletMovementTypeEnumMap = {
  WalletMovementType.recharge: 'recharge',
  WalletMovementType.withdrawal: 'withdrawal',
  WalletMovementType.settlement: 'settlement',
  WalletMovementType.$unknown: r'$unknown',
};

const _$WalletStatusEnumMap = {
  WalletStatus.pending: 'pending',
  WalletStatus.approved: 'approved',
  WalletStatus.rejected: 'rejected',
  WalletStatus.$unknown: r'$unknown',
};
