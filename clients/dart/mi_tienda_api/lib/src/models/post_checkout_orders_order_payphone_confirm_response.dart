// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'order.dart';

part 'post_checkout_orders_order_payphone_confirm_response.g.dart';

@JsonSerializable()
class PostCheckoutOrdersOrderPayphoneConfirmResponse {
  const PostCheckoutOrdersOrderPayphoneConfirmResponse({
    required this.data,
  });
  
  factory PostCheckoutOrdersOrderPayphoneConfirmResponse.fromJson(Map<String, Object?> json) => _$PostCheckoutOrdersOrderPayphoneConfirmResponseFromJson(json);
  
  final Order data;

  Map<String, Object?> toJson() => _$PostCheckoutOrdersOrderPayphoneConfirmResponseToJson(this);
}
