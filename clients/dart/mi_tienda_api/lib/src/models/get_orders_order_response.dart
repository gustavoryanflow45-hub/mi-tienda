// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'order.dart';

part 'get_orders_order_response.g.dart';

@JsonSerializable()
class GetOrdersOrderResponse {
  const GetOrdersOrderResponse({
    required this.data,
  });
  
  factory GetOrdersOrderResponse.fromJson(Map<String, Object?> json) => _$GetOrdersOrderResponseFromJson(json);
  
  final Order data;

  Map<String, Object?> toJson() => _$GetOrdersOrderResponseToJson(this);
}
