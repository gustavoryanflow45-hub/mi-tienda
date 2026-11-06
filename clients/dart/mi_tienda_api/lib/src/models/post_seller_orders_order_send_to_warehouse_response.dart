// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'seller_order.dart';

part 'post_seller_orders_order_send_to_warehouse_response.g.dart';

@JsonSerializable()
class PostSellerOrdersOrderSendToWarehouseResponse {
  const PostSellerOrdersOrderSendToWarehouseResponse({
    required this.data,
  });
  
  factory PostSellerOrdersOrderSendToWarehouseResponse.fromJson(Map<String, Object?> json) => _$PostSellerOrdersOrderSendToWarehouseResponseFromJson(json);
  
  final SellerOrder data;

  Map<String, Object?> toJson() => _$PostSellerOrdersOrderSendToWarehouseResponseToJson(this);
}
