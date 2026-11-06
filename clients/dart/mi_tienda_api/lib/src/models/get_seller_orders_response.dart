// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'paginated.dart';
import 'pagination_links.dart';
import 'pagination_meta.dart';
import 'seller_order.dart';

part 'get_seller_orders_response.g.dart';

@JsonSerializable()
class GetSellerOrdersResponse {
  const GetSellerOrdersResponse({
    required this.links,
    required this.meta,
    this.data,
  });
  
  factory GetSellerOrdersResponse.fromJson(Map<String, Object?> json) => _$GetSellerOrdersResponseFromJson(json);
  
  final List<SellerOrder>? data;
  final PaginationLinks links;
  final PaginationMeta meta;

  Map<String, Object?> toJson() => _$GetSellerOrdersResponseToJson(this);
}
