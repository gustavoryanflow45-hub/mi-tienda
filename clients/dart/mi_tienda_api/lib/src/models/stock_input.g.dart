// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'stock_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StockInput _$StockInputFromJson(Map<String, dynamic> json) => StockInput(
  price: json['price'] as num,
  qty: (json['qty'] as num).toInt(),
  size: json['size'] as String?,
  color: json['color'] as String?,
  sku: json['sku'] as String?,
);

Map<String, dynamic> _$StockInputToJson(StockInput instance) =>
    <String, dynamic>{
      'size': instance.size,
      'color': instance.color,
      'price': instance.price,
      'qty': instance.qty,
      'sku': instance.sku,
    };
