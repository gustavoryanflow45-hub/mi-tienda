// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'nullable_date_time.dart';

part 'settlement.g.dart';

@JsonSerializable()
class Settlement {
  const Settlement({
    required this.id,
    required this.totalSales,
    required this.commissionRate,
    required this.commission,
    required this.netAmount,
    required this.lines,
    required this.settledAt,
  });
  
  factory Settlement.fromJson(Map<String, Object?> json) => _$SettlementFromJson(json);
  
  final int id;
  @JsonKey(name: 'total_sales')
  final num totalSales;
  @JsonKey(name: 'commission_rate')
  final num commissionRate;
  final num commission;

  /// Acreditado al saldo.
  @JsonKey(name: 'net_amount')
  final num netAmount;
  final int lines;
  @JsonKey(name: 'settled_at')
  final NullableDateTime settledAt;

  Map<String, Object?> toJson() => _$SettlementToJson(this);
}
