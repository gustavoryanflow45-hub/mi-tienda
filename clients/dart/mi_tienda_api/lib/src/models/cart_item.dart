// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'cart_product.dart';
import 'variant_info.dart';

part 'cart_item.g.dart';

@JsonSerializable()
class CartItem {
  const CartItem({
    required this.id,
    required this.product,
    required this.variation,
    required this.variant,
    required this.price,
    required this.quantity,
    required this.lineTotal,
    required this.shippingCost,
    required this.availableQty,
  });
  
  factory CartItem.fromJson(Map<String, Object?> json) => _$CartItemFromJson(json);
  
  final int id;
  final CartProduct? product;
  final String variation;

  /// Talla y color legibles; null sin variación.
  final VariantInfo? variant;

  /// Unitario fijado al añadir.
  final num price;
  final int quantity;
  @JsonKey(name: 'line_total')
  final num lineTotal;
  @JsonKey(name: 'shipping_cost')
  final num shippingCost;
  @JsonKey(name: 'available_qty')
  final int availableQty;

  Map<String, Object?> toJson() => _$CartItemToJson(this);
}
