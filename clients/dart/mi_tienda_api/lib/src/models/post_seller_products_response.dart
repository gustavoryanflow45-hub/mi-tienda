// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'seller_product.dart';

part 'post_seller_products_response.g.dart';

@JsonSerializable()
class PostSellerProductsResponse {
  const PostSellerProductsResponse({
    required this.data,
  });
  
  factory PostSellerProductsResponse.fromJson(Map<String, Object?> json) => _$PostSellerProductsResponseFromJson(json);
  
  final SellerProduct data;

  Map<String, Object?> toJson() => _$PostSellerProductsResponseToJson(this);
}
