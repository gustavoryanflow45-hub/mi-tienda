// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'variant_info.dart';

part 'order_item.g.dart';

@JsonSerializable()
class OrderItem {
  const OrderItem({
    required this.id,
    required this.productId,
    required this.name,
    required this.thumbnailUrl,
    required this.variation,
    required this.variant,
    required this.price,
    required this.quantity,
    required this.lineTotal,
    required this.shippingCost,
  });
  
  factory OrderItem.fromJson(Map<String, Object?> json) => _$OrderItemFromJson(json);
  
  final int id;
  @JsonKey(name: 'product_id')
  final int? productId;
  final String name;
  @JsonKey(name: 'thumbnail_url')
  final String thumbnailUrl;
  final String variation;

  /// Talla y color legibles; null sin variación.
  final VariantInfo? variant;
  final num price;
  final int quantity;
  @JsonKey(name: 'line_total')
  final num lineTotal;
  @JsonKey(name: 'shipping_cost')
  final num shippingCost;

  Map<String, Object?> toJson() => _$OrderItemToJson(this);
}
