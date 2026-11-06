// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'stripe_payment_intent.dart';

part 'post_checkout_orders_order_stripe_response.g.dart';

@JsonSerializable()
class PostCheckoutOrdersOrderStripeResponse {
  const PostCheckoutOrdersOrderStripeResponse({
    required this.data,
  });
  
  factory PostCheckoutOrdersOrderStripeResponse.fromJson(Map<String, Object?> json) => _$PostCheckoutOrdersOrderStripeResponseFromJson(json);
  
  final StripePaymentIntent data;

  Map<String, Object?> toJson() => _$PostCheckoutOrdersOrderStripeResponseToJson(this);
}
