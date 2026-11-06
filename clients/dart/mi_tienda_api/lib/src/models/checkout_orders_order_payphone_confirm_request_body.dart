// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'checkout_orders_order_payphone_confirm_request_body.g.dart';

@JsonSerializable()
class CheckoutOrdersOrderPayphoneConfirmRequestBody {
  const CheckoutOrdersOrderPayphoneConfirmRequestBody({
    required this.id,
    required this.clientTransactionId,
  });
  
  factory CheckoutOrdersOrderPayphoneConfirmRequestBody.fromJson(Map<String, Object?> json) => _$CheckoutOrdersOrderPayphoneConfirmRequestBodyFromJson(json);
  
  /// `id` de la URL de vuelta de PayPhone.
  final int id;
  @JsonKey(name: 'client_transaction_id')
  final String clientTransactionId;

  Map<String, Object?> toJson() => _$CheckoutOrdersOrderPayphoneConfirmRequestBodyToJson(this);
}
