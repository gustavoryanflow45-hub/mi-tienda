// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'checkout_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CheckoutSummary _$CheckoutSummaryFromJson(
  Map<String, dynamic> json,
) => CheckoutSummary(
  items: (json['items'] as List<dynamic>)
      .map((e) => CartItem.fromJson(e as Map<String, dynamic>))
      .toList(),
  totals: Totals.fromJson(json['totals'] as Map<String, dynamic>),
  currency: json['currency'] as String,
  shipping: ShippingAddress.fromJson(json['shipping'] as Map<String, dynamic>),
  payment: CheckoutPayment.fromJson(json['payment'] as Map<String, dynamic>),
);

Map<String, dynamic> _$CheckoutSummaryToJson(CheckoutSummary instance) =>
    <String, dynamic>{
      'items': instance.items,
      'totals': instance.totals,
      'currency': instance.currency,
      'shipping': instance.shipping,
      'payment': instance.payment,
    };
