// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'stock_input.g.dart';

@JsonSerializable()
class StockInput {
  const StockInput({
    required this.price,
    required this.qty,
    this.size,
    this.color,
    this.sku,
  });
  
  factory StockInput.fromJson(Map<String, Object?> json) => _$StockInputFromJson(json);
  
  final String? size;
  final String? color;
  final num price;
  final int qty;
  final String? sku;

  Map<String, Object?> toJson() => _$StockInputToJson(this);
}
