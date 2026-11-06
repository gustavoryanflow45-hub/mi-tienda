// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'nullable_date_time.dart';

part 'last_settlement.g.dart';

@JsonSerializable()
class LastSettlement {
  const LastSettlement({
    required this.netAmount,
    required this.settledAt,
  });
  
  factory LastSettlement.fromJson(Map<String, Object?> json) => _$LastSettlementFromJson(json);
  
  @JsonKey(name: 'net_amount')
  final num netAmount;
  @JsonKey(name: 'settled_at')
  final NullableDateTime settledAt;

  Map<String, Object?> toJson() => _$LastSettlementToJson(this);
}
