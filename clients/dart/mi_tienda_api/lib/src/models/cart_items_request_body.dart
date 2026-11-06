// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'cart_items_request_body.g.dart';

@JsonSerializable()
class CartItemsRequestBody {
  const CartItemsRequestBody({
    required this.productId,
    this.quantity = 1,
    this.variation,
  });
  
  factory CartItemsRequestBody.fromJson(Map<String, Object?> json) => _$CartItemsRequestBodyFromJson(json);
  
  @JsonKey(name: 'product_id')
  final int productId;

  /// `variants.combinations[].variation` de la ficha; "" sin variantes.
  final String? variation;
  final int quantity;

  Map<String, Object?> toJson() => _$CartItemsRequestBodyToJson(this);
}
