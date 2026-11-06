// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'post_checkout_orders_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PostCheckoutOrdersResponse _$PostCheckoutOrdersResponseFromJson(
  Map<String, dynamic> json,
) => PostCheckoutOrdersResponse(
  data: Order.fromJson(json['data'] as Map<String, dynamic>),
);

Map<String, dynamic> _$PostCheckoutOrdersResponseToJson(
  PostCheckoutOrdersResponse instance,
) => <String, dynamic>{'data': instance.data};
