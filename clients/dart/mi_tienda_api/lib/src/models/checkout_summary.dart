// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'cart_item.dart';
import 'checkout_payment.dart';
import 'shipping_address.dart';
import 'totals.dart';

part 'checkout_summary.g.dart';

@JsonSerializable()
class CheckoutSummary {
  const CheckoutSummary({
    required this.items,
    required this.totals,
    required this.currency,
    required this.shipping,
    required this.payment,
  });
  
  factory CheckoutSummary.fromJson(Map<String, Object?> json) => _$CheckoutSummaryFromJson(json);
  
  final List<CartItem> items;
  final Totals totals;
  final String currency;
  final ShippingAddress shipping;
  final CheckoutPayment payment;

  Map<String, Object?> toJson() => _$CheckoutSummaryToJson(this);
}
