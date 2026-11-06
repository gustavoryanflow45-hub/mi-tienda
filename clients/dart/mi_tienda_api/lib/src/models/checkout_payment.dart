// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'gateway.dart';

part 'checkout_payment.g.dart';

@JsonSerializable()
class CheckoutPayment {
  const CheckoutPayment({
    required this.suggestedGateway,
    required this.country,
    required this.gateways,
    required this.stripePublishableKey,
  });
  
  factory CheckoutPayment.fromJson(Map<String, Object?> json) => _$CheckoutPaymentFromJson(json);
  
  @JsonKey(name: 'suggested_gateway')
  final Gateway suggestedGateway;

  /// ISO 3166-1 alfa-2 por IP.
  final String? country;
  final List<Gateway> gateways;
  @JsonKey(name: 'stripe_publishable_key')
  final String? stripePublishableKey;

  Map<String, Object?> toJson() => _$CheckoutPaymentToJson(this);
}
