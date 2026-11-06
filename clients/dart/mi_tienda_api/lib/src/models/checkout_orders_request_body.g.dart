// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'checkout_orders_request_body.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CheckoutOrdersRequestBody _$CheckoutOrdersRequestBodyFromJson(
  Map<String, dynamic> json,
) => CheckoutOrdersRequestBody(
  fullName: json['full_name'] as String,
  phone: json['phone'] as String,
  email: json['email'] as String,
  address: json['address'] as String,
  city: json['city'] as String,
  state: json['state'] as String?,
  country: json['country'] as String?,
  postalCode: json['postal_code'] as String?,
  orderId: (json['order_id'] as num?)?.toInt(),
);

Map<String, dynamic> _$CheckoutOrdersRequestBodyToJson(
  CheckoutOrdersRequestBody instance,
) => <String, dynamic>{
  'full_name': instance.fullName,
  'phone': instance.phone,
  'email': instance.email,
  'address': instance.address,
  'city': instance.city,
  'state': instance.state,
  'country': instance.country,
  'postal_code': instance.postalCode,
  'order_id': instance.orderId,
};
