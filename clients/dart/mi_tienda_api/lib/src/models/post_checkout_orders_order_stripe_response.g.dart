// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'post_checkout_orders_order_stripe_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PostCheckoutOrdersOrderStripeResponse
_$PostCheckoutOrdersOrderStripeResponseFromJson(Map<String, dynamic> json) =>
    PostCheckoutOrdersOrderStripeResponse(
      data: StripePaymentIntent.fromJson(json['data'] as Map<String, dynamic>),
    );

Map<String, dynamic> _$PostCheckoutOrdersOrderStripeResponseToJson(
  PostCheckoutOrdersOrderStripeResponse instance,
) => <String, dynamic>{'data': instance.data};
