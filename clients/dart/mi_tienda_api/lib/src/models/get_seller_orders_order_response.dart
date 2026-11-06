// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'seller_order.dart';

part 'get_seller_orders_order_response.g.dart';

@JsonSerializable()
class GetSellerOrdersOrderResponse {
  const GetSellerOrdersOrderResponse({
    required this.data,
  });
  
  factory GetSellerOrdersOrderResponse.fromJson(Map<String, Object?> json) => _$GetSellerOrdersOrderResponseFromJson(json);
  
  final SellerOrder data;

  Map<String, Object?> toJson() => _$GetSellerOrdersOrderResponseToJson(this);
}
