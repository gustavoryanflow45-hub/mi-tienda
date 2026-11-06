// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'last_settlement.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

LastSettlement _$LastSettlementFromJson(Map<String, dynamic> json) =>
    LastSettlement(
      netAmount: json['net_amount'] as num,
      settledAt: json['settled_at'] == null
          ? null
          : DateTime.parse(json['settled_at'] as String),
    );

Map<String, dynamic> _$LastSettlementToJson(LastSettlement instance) =>
    <String, dynamic>{
      'net_amount': instance.netAmount,
      'settled_at': instance.settledAt?.toIso8601String(),
    };
