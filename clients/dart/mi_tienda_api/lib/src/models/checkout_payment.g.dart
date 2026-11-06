// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'checkout_payment.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CheckoutPayment _$CheckoutPaymentFromJson(Map<String, dynamic> json) =>
    CheckoutPayment(
      suggestedGateway: Gateway.fromJson(json['suggested_gateway'] as String),
      country: json['country'] as String?,
      gateways: (json['gateways'] as List<dynamic>)
          .map((e) => Gateway.fromJson(e as String))
          .toList(),
      stripePublishableKey: json['stripe_publishable_key'] as String?,
    );

Map<String, dynamic> _$CheckoutPaymentToJson(CheckoutPayment instance) =>
    <String, dynamic>{
      'suggested_gateway': _$GatewayEnumMap[instance.suggestedGateway]!,
      'country': instance.country,
      'gateways': instance.gateways.map((e) => _$GatewayEnumMap[e]!).toList(),
      'stripe_publishable_key': instance.stripePublishableKey,
    };

const _$GatewayEnumMap = {
  Gateway.stripe: 'stripe',
  Gateway.payphone: 'payphone',
  Gateway.$unknown: r'$unknown',
};
