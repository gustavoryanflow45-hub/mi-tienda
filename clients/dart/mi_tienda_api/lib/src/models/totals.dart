// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'totals.g.dart';

@JsonSerializable()
class Totals {
  const Totals({
    required this.subtotal,
    required this.shipping,
    required this.tax,
    required this.grandTotal,
  });
  
  factory Totals.fromJson(Map<String, Object?> json) => _$TotalsFromJson(json);
  
  final num subtotal;
  final num shipping;
  final num tax;
  @JsonKey(name: 'grand_total')
  final num grandTotal;

  Map<String, Object?> toJson() => _$TotalsToJson(this);
}
