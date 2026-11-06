// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'order.dart';

part 'post_checkout_orders_order_stripe_confirm_response.g.dart';

@JsonSerializable()
class PostCheckoutOrdersOrderStripeConfirmResponse {
  const PostCheckoutOrdersOrderStripeConfirmResponse({
    required this.data,
  });
  
  factory PostCheckoutOrdersOrderStripeConfirmResponse.fromJson(Map<String, Object?> json) => _$PostCheckoutOrdersOrderStripeConfirmResponseFromJson(json);
  
  final Order data;

  Map<String, Object?> toJson() => _$PostCheckoutOrdersOrderStripeConfirmResponseToJson(this);
}
