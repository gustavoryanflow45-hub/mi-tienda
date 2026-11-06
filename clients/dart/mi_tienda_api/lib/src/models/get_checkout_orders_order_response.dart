// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'order.dart';

part 'get_checkout_orders_order_response.g.dart';

@JsonSerializable()
class GetCheckoutOrdersOrderResponse {
  const GetCheckoutOrdersOrderResponse({
    required this.data,
  });
  
  factory GetCheckoutOrdersOrderResponse.fromJson(Map<String, Object?> json) => _$GetCheckoutOrdersOrderResponseFromJson(json);
  
  final Order data;

  Map<String, Object?> toJson() => _$GetCheckoutOrdersOrderResponseToJson(this);
}
