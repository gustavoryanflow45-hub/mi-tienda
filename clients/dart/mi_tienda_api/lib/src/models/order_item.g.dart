// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'order_item.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

OrderItem _$OrderItemFromJson(Map<String, dynamic> json) => OrderItem(
  id: (json['id'] as num).toInt(),
  productId: (json['product_id'] as num?)?.toInt(),
  name: json['name'] as String,
  thumbnailUrl: json['thumbnail_url'] as String,
  variation: json['variation'] as String,
  variant: json['variant'] == null
      ? null
      : VariantInfo.fromJson(json['variant'] as Map<String, dynamic>),
  price: json['price'] as num,
  quantity: (json['quantity'] as num).toInt(),
  lineTotal: json['line_total'] as num,
  shippingCost: json['shipping_cost'] as num,
);

Map<String, dynamic> _$OrderItemToJson(OrderItem instance) => <String, dynamic>{
  'id': instance.id,
  'product_id': instance.productId,
  'name': instance.name,
  'thumbnail_url': instance.thumbnailUrl,
  'variation': instance.variation,
  'variant': instance.variant,
  'price': instance.price,
  'quantity': instance.quantity,
  'line_total': instance.lineTotal,
  'shipping_cost': instance.shippingCost,
};
