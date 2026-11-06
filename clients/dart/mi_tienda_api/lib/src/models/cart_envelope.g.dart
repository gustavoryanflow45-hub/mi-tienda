// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cart_envelope.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CartEnvelope _$CartEnvelopeFromJson(Map<String, dynamic> json) => CartEnvelope(
  data: CartData.fromJson(json['data'] as Map<String, dynamic>),
  message: json['message'] as String?,
);

Map<String, dynamic> _$CartEnvelopeToJson(CartEnvelope instance) =>
    <String, dynamic>{'data': instance.data, 'message': instance.message};
