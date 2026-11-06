// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'seller_product.dart';

part 'post_seller_products_product_toggle_published_response.g.dart';

@JsonSerializable()
class PostSellerProductsProductTogglePublishedResponse {
  const PostSellerProductsProductTogglePublishedResponse({
    required this.data,
  });
  
  factory PostSellerProductsProductTogglePublishedResponse.fromJson(Map<String, Object?> json) => _$PostSellerProductsProductTogglePublishedResponseFromJson(json);
  
  final SellerProduct data;

  Map<String, Object?> toJson() => _$PostSellerProductsProductTogglePublishedResponseToJson(this);
}
