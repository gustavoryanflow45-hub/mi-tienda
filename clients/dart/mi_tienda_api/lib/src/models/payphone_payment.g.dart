// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'payphone_payment.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PayphonePayment _$PayphonePaymentFromJson(Map<String, dynamic> json) =>
    PayphonePayment(
      redirectUrl: json['redirect_url'] as String,
      clientTransactionId: json['client_transaction_id'] as String,
      returnUrl: json['return_url'] as String,
      order: Order.fromJson(json['order'] as Map<String, dynamic>),
    );

Map<String, dynamic> _$PayphonePaymentToJson(PayphonePayment instance) =>
    <String, dynamic>{
      'redirect_url': instance.redirectUrl,
      'client_transaction_id': instance.clientTransactionId,
      'return_url': instance.returnUrl,
      'order': instance.order,
    };
