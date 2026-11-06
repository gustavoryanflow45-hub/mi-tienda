// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'settlement_cycle.g.dart';

/// Líneas pagadas, entregadas y sin liquidar.
@JsonSerializable()
class SettlementCycle {
  const SettlementCycle({
    required this.totalSales,
    required this.commissionRate,
    required this.commission,
    required this.netAmount,
    required this.lines,
  });
  
  factory SettlementCycle.fromJson(Map<String, Object?> json) => _$SettlementCycleFromJson(json);
  
  @JsonKey(name: 'total_sales')
  final num totalSales;
  @JsonKey(name: 'commission_rate')
  final num commissionRate;
  final num commission;
  @JsonKey(name: 'net_amount')
  final num netAmount;
  final int lines;

  Map<String, Object?> toJson() => _$SettlementCycleToJson(this);
}
