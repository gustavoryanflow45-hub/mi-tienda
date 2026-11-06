// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cart_items_request_body.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CartItemsRequestBody _$CartItemsRequestBodyFromJson(
  Map<String, dynamic> json,
) => CartItemsRequestBody(
  productId: (json['product_id'] as num).toInt(),
  quantity: (json['quantity'] as num?)?.toInt() ?? 1,
  variation: json['variation'] as String?,
);

Map<String, dynamic> _$CartItemsRequestBodyToJson(
  CartItemsRequestBody instance,
) => <String, dynamic>{
  'product_id': instance.productId,
  'variation': instance.variation,
  'quantity': instance.quantity,
};
