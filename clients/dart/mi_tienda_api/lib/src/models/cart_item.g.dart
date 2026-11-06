// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cart_item.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CartItem _$CartItemFromJson(Map<String, dynamic> json) => CartItem(
  id: (json['id'] as num).toInt(),
  product: json['product'] == null
      ? null
      : CartProduct.fromJson(json['product'] as Map<String, dynamic>),
  variation: json['variation'] as String,
  variant: json['variant'] == null
      ? null
      : VariantInfo.fromJson(json['variant'] as Map<String, dynamic>),
  price: json['price'] as num,
  quantity: (json['quantity'] as num).toInt(),
  lineTotal: json['line_total'] as num,
  shippingCost: json['shipping_cost'] as num,
  availableQty: (json['available_qty'] as num).toInt(),
);

Map<String, dynamic> _$CartItemToJson(CartItem instance) => <String, dynamic>{
  'id': instance.id,
  'product': instance.product,
  'variation': instance.variation,
  'variant': instance.variant,
  'price': instance.price,
  'quantity': instance.quantity,
  'line_total': instance.lineTotal,
  'shipping_cost': instance.shippingCost,
  'available_qty': instance.availableQty,
};
