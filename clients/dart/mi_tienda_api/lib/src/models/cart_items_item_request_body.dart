// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'cart_items_item_request_body.g.dart';

@JsonSerializable()
class CartItemsItemRequestBody {
  const CartItemsItemRequestBody({
    required this.quantity,
  });
  
  factory CartItemsItemRequestBody.fromJson(Map<String, Object?> json) => _$CartItemsItemRequestBodyFromJson(json);
  
  final int quantity;

  Map<String, Object?> toJson() => _$CartItemsItemRequestBodyToJson(this);
}
