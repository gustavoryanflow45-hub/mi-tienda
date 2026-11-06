// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'seller_stock.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SellerStock _$SellerStockFromJson(Map<String, dynamic> json) => SellerStock(
  id: (json['id'] as num).toInt(),
  variation: json['variation'] as String,
  size: json['size'] as String?,
  color: json['color'] as String?,
  price: json['price'] as num,
  qty: (json['qty'] as num).toInt(),
  sku: json['sku'] as String?,
);

Map<String, dynamic> _$SellerStockToJson(SellerStock instance) =>
    <String, dynamic>{
      'id': instance.id,
      'variation': instance.variation,
      'size': instance.size,
      'color': instance.color,
      'price': instance.price,
      'qty': instance.qty,
      'sku': instance.sku,
    };
