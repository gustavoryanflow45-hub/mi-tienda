// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'order.dart';

part 'payphone_payment.g.dart';

@JsonSerializable()
class PayphonePayment {
  const PayphonePayment({
    required this.redirectUrl,
    required this.clientTransactionId,
    required this.returnUrl,
    required this.order,
  });
  
  factory PayphonePayment.fromJson(Map<String, Object?> json) => _$PayphonePaymentFromJson(json);
  
  @JsonKey(name: 'redirect_url')
  final String redirectUrl;
  @JsonKey(name: 'client_transaction_id')
  final String clientTransactionId;

  /// Esquema de la app al que vuelve el navegador (`?status=…&order=…`).
  @JsonKey(name: 'return_url')
  final String returnUrl;
  final Order order;

  Map<String, Object?> toJson() => _$PayphonePaymentToJson(this);
}
