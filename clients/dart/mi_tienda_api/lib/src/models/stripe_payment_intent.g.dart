// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'stripe_payment_intent.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StripePaymentIntent _$StripePaymentIntentFromJson(Map<String, dynamic> json) =>
    StripePaymentIntent(
      clientSecret: json['client_secret'] as String,
      paymentIntentId: json['payment_intent_id'] as String,
      publishableKey: json['publishable_key'] as String?,
      amount: (json['amount'] as num).toInt(),
      currency: json['currency'] as String,
      order: Order.fromJson(json['order'] as Map<String, dynamic>),
    );

Map<String, dynamic> _$StripePaymentIntentToJson(
  StripePaymentIntent instance,
) => <String, dynamic>{
  'client_secret': instance.clientSecret,
  'payment_intent_id': instance.paymentIntentId,
  'publishable_key': instance.publishableKey,
  'amount': instance.amount,
  'currency': instance.currency,
  'order': instance.order,
};
