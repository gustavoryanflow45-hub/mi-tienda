// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'settlement.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

Settlement _$SettlementFromJson(Map<String, dynamic> json) => Settlement(
  id: (json['id'] as num).toInt(),
  totalSales: json['total_sales'] as num,
  commissionRate: json['commission_rate'] as num,
  commission: json['commission'] as num,
  netAmount: json['net_amount'] as num,
  lines: (json['lines'] as num).toInt(),
  settledAt: json['settled_at'] == null
      ? null
      : DateTime.parse(json['settled_at'] as String),
);

Map<String, dynamic> _$SettlementToJson(Settlement instance) =>
    <String, dynamic>{
      'id': instance.id,
      'total_sales': instance.totalSales,
      'commission_rate': instance.commissionRate,
      'commission': instance.commission,
      'net_amount': instance.netAmount,
      'lines': instance.lines,
      'settled_at': instance.settledAt?.toIso8601String(),
    };
