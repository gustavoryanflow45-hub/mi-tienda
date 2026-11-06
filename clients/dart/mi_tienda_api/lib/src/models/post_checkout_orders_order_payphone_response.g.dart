// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'post_checkout_orders_order_payphone_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PostCheckoutOrdersOrderPayphoneResponse
_$PostCheckoutOrdersOrderPayphoneResponseFromJson(Map<String, dynamic> json) =>
    PostCheckoutOrdersOrderPayphoneResponse(
      data: PayphonePayment.fromJson(json['data'] as Map<String, dynamic>),
    );

Map<String, dynamic> _$PostCheckoutOrdersOrderPayphoneResponseToJson(
  PostCheckoutOrdersOrderPayphoneResponse instance,
) => <String, dynamic>{'data': instance.data};
