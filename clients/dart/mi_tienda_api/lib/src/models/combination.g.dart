// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'combination.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

Combination _$CombinationFromJson(Map<String, dynamic> json) => Combination(
  variation: json['variation'] as String,
  size: json['size'] as String?,
  color: json['color'] as String?,
  price: json['price'] as num,
  originalPrice: json['original_price'] as num,
  qty: (json['qty'] as num).toInt(),
  available: json['available'] as bool,
);

Map<String, dynamic> _$CombinationToJson(Combination instance) =>
    <String, dynamic>{
      'variation': instance.variation,
      'size': instance.size,
      'color': instance.color,
      'price': instance.price,
      'original_price': instance.originalPrice,
      'qty': instance.qty,
      'available': instance.available,
    };
