// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'cart_item.dart';
import 'totals.dart';

part 'cart_data.g.dart';

@JsonSerializable()
class CartData {
  const CartData({
    required this.items,
    required this.count,
    required this.totals,
    required this.currency,
  });
  
  factory CartData.fromJson(Map<String, Object?> json) => _$CartDataFromJson(json);
  
  final List<CartItem> items;
  final int count;
  final Totals totals;
  final String currency;

  Map<String, Object?> toJson() => _$CartDataToJson(this);
}
