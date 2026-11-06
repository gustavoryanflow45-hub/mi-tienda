// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'seller_order.dart';

part 'post_seller_orders_order_confirm_response.g.dart';

@JsonSerializable()
class PostSellerOrdersOrderConfirmResponse {
  const PostSellerOrdersOrderConfirmResponse({
    required this.data,
  });
  
  factory PostSellerOrdersOrderConfirmResponse.fromJson(Map<String, Object?> json) => _$PostSellerOrdersOrderConfirmResponseFromJson(json);
  
  final SellerOrder data;

  Map<String, Object?> toJson() => _$PostSellerOrdersOrderConfirmResponseToJson(this);
}
