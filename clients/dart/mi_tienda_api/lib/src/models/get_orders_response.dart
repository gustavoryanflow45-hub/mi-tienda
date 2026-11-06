// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'order.dart';
import 'paginated.dart';
import 'pagination_links.dart';
import 'pagination_meta.dart';

part 'get_orders_response.g.dart';

@JsonSerializable()
class GetOrdersResponse {
  const GetOrdersResponse({
    required this.links,
    required this.meta,
    this.data,
  });
  
  factory GetOrdersResponse.fromJson(Map<String, Object?> json) => _$GetOrdersResponseFromJson(json);
  
  final List<Order>? data;
  final PaginationLinks links;
  final PaginationMeta meta;

  Map<String, Object?> toJson() => _$GetOrdersResponseToJson(this);
}
