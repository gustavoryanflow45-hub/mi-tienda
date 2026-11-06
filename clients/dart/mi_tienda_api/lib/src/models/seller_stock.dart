// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'seller_stock.g.dart';

@JsonSerializable()
class SellerStock {
  const SellerStock({
    required this.id,
    required this.variation,
    required this.size,
    required this.color,
    required this.price,
    required this.qty,
    required this.sku,
  });
  
  factory SellerStock.fromJson(Map<String, Object?> json) => _$SellerStockFromJson(json);
  
  final int id;
  final String variation;
  final String? size;
  final String? color;
  final num price;
  final int qty;
  final String? sku;

  Map<String, Object?> toJson() => _$SellerStockToJson(this);
}
