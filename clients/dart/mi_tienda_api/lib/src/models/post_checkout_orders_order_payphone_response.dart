// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'payphone_payment.dart';

part 'post_checkout_orders_order_payphone_response.g.dart';

@JsonSerializable()
class PostCheckoutOrdersOrderPayphoneResponse {
  const PostCheckoutOrdersOrderPayphoneResponse({
    required this.data,
  });
  
  factory PostCheckoutOrdersOrderPayphoneResponse.fromJson(Map<String, Object?> json) => _$PostCheckoutOrdersOrderPayphoneResponseFromJson(json);
  
  final PayphonePayment data;

  Map<String, Object?> toJson() => _$PostCheckoutOrdersOrderPayphoneResponseToJson(this);
}
