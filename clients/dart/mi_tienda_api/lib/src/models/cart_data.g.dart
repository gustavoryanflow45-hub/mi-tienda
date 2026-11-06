// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cart_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CartData _$CartDataFromJson(Map<String, dynamic> json) => CartData(
  items: (json['items'] as List<dynamic>)
      .map((e) => CartItem.fromJson(e as Map<String, dynamic>))
      .toList(),
  count: (json['count'] as num).toInt(),
  totals: Totals.fromJson(json['totals'] as Map<String, dynamic>),
  currency: json['currency'] as String,
);

Map<String, dynamic> _$CartDataToJson(CartData instance) => <String, dynamic>{
  'items': instance.items,
  'count': instance.count,
  'totals': instance.totals,
  'currency': instance.currency,
};
