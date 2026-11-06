// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'checkout_orders_order_payphone_confirm_request_body.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CheckoutOrdersOrderPayphoneConfirmRequestBody
_$CheckoutOrdersOrderPayphoneConfirmRequestBodyFromJson(
  Map<String, dynamic> json,
) => CheckoutOrdersOrderPayphoneConfirmRequestBody(
  id: (json['id'] as num).toInt(),
  clientTransactionId: json['client_transaction_id'] as String,
);

Map<String, dynamic> _$CheckoutOrdersOrderPayphoneConfirmRequestBodyToJson(
  CheckoutOrdersOrderPayphoneConfirmRequestBody instance,
) => <String, dynamic>{
  'id': instance.id,
  'client_transaction_id': instance.clientTransactionId,
};
