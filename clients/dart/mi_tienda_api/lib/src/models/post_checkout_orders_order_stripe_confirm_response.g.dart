// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'post_checkout_orders_order_stripe_confirm_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PostCheckoutOrdersOrderStripeConfirmResponse
_$PostCheckoutOrdersOrderStripeConfirmResponseFromJson(
  Map<String, dynamic> json,
) => PostCheckoutOrdersOrderStripeConfirmResponse(
  data: Order.fromJson(json['data'] as Map<String, dynamic>),
);

Map<String, dynamic> _$PostCheckoutOrdersOrderStripeConfirmResponseToJson(
  PostCheckoutOrdersOrderStripeConfirmResponse instance,
) => <String, dynamic>{'data': instance.data};
