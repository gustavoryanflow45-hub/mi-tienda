// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'order.dart';

part 'stripe_payment_intent.g.dart';

@JsonSerializable()
class StripePaymentIntent {
  const StripePaymentIntent({
    required this.clientSecret,
    required this.paymentIntentId,
    required this.publishableKey,
    required this.amount,
    required this.currency,
    required this.order,
  });
  
  factory StripePaymentIntent.fromJson(Map<String, Object?> json) => _$StripePaymentIntentFromJson(json);
  
  @JsonKey(name: 'client_secret')
  final String clientSecret;
  @JsonKey(name: 'payment_intent_id')
  final String paymentIntentId;
  @JsonKey(name: 'publishable_key')
  final String? publishableKey;

  /// En centavos.
  final int amount;
  final String currency;
  final Order order;

  Map<String, Object?> toJson() => _$StripePaymentIntentToJson(this);
}
