// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'settlement_cycle.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SettlementCycle _$SettlementCycleFromJson(Map<String, dynamic> json) =>
    SettlementCycle(
      totalSales: json['total_sales'] as num,
      commissionRate: json['commission_rate'] as num,
      commission: json['commission'] as num,
      netAmount: json['net_amount'] as num,
      lines: (json['lines'] as num).toInt(),
    );

Map<String, dynamic> _$SettlementCycleToJson(SettlementCycle instance) =>
    <String, dynamic>{
      'total_sales': instance.totalSales,
      'commission_rate': instance.commissionRate,
      'commission': instance.commission,
      'net_amount': instance.netAmount,
      'lines': instance.lines,
    };
