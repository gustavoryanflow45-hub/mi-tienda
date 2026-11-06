// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'paginated.dart';
import 'pagination_links.dart';
import 'pagination_meta.dart';
import 'product_card.dart';

part 'get_products_response.g.dart';

@JsonSerializable()
class GetProductsResponse {
  const GetProductsResponse({
    required this.links,
    required this.meta,
    this.data,
  });
  
  factory GetProductsResponse.fromJson(Map<String, Object?> json) => _$GetProductsResponseFromJson(json);
  
  final List<ProductCard>? data;
  final PaginationLinks links;
  final PaginationMeta meta;

  Map<String, Object?> toJson() => _$GetProductsResponseToJson(this);
}
