// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'totals.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

Totals _$TotalsFromJson(Map<String, dynamic> json) => Totals(
  subtotal: json['subtotal'] as num,
  shipping: json['shipping'] as num,
  tax: json['tax'] as num,
  grandTotal: json['grand_total'] as num,
);

Map<String, dynamic> _$TotalsToJson(Totals instance) => <String, dynamic>{
  'subtotal': instance.subtotal,
  'shipping': instance.shipping,
  'tax': instance.tax,
  'grand_total': instance.grandTotal,
};
