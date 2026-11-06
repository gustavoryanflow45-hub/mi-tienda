// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'shipping_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ShippingInput _$ShippingInputFromJson(Map<String, dynamic> json) =>
    ShippingInput(
      fullName: json['full_name'] as String,
      phone: json['phone'] as String,
      email: json['email'] as String,
      address: json['address'] as String,
      city: json['city'] as String,
      state: json['state'] as String?,
      country: json['country'] as String?,
      postalCode: json['postal_code'] as String?,
    );

Map<String, dynamic> _$ShippingInputToJson(ShippingInput instance) =>
    <String, dynamic>{
      'full_name': instance.fullName,
      'phone': instance.phone,
      'email': instance.email,
      'address': instance.address,
      'city': instance.city,
      'state': instance.state,
      'country': instance.country,
      'postal_code': instance.postalCode,
    };
