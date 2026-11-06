// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'method.dart';

part 'checkout_orders_order_payphone_request_body.g.dart';

@JsonSerializable()
class CheckoutOrdersOrderPayphoneRequestBody {
  const CheckoutOrdersOrderPayphoneRequestBody({
    this.method = Method.card,
  });
  
  factory CheckoutOrdersOrderPayphoneRequestBody.fromJson(Map<String, Object?> json) => _$CheckoutOrdersOrderPayphoneRequestBodyFromJson(json);
  
  final Method method;

  Map<String, Object?> toJson() => _$CheckoutOrdersOrderPayphoneRequestBodyToJson(this);
}
