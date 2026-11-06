// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'checkout_orders_order_payphone_request_body.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CheckoutOrdersOrderPayphoneRequestBody
_$CheckoutOrdersOrderPayphoneRequestBodyFromJson(Map<String, dynamic> json) =>
    CheckoutOrdersOrderPayphoneRequestBody(
      method: json['method'] == null
          ? Method.card
          : Method.fromJson(json['method'] as String),
    );

Map<String, dynamic> _$CheckoutOrdersOrderPayphoneRequestBodyToJson(
  CheckoutOrdersOrderPayphoneRequestBody instance,
) => <String, dynamic>{'method': _$MethodEnumMap[instance.method]!};

const _$MethodEnumMap = {
  Method.card: 'card',
  Method.app: 'app',
  Method.$unknown: r'$unknown',
};
