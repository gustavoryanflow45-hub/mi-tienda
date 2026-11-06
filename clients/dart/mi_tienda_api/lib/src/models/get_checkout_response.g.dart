// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_checkout_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetCheckoutResponse _$GetCheckoutResponseFromJson(Map<String, dynamic> json) =>
    GetCheckoutResponse(
      data: CheckoutSummary.fromJson(json['data'] as Map<String, dynamic>),
    );

Map<String, dynamic> _$GetCheckoutResponseToJson(
  GetCheckoutResponse instance,
) => <String, dynamic>{'data': instance.data};
