// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'product_detail.dart';

part 'get_products_product_response.g.dart';

@JsonSerializable()
class GetProductsProductResponse {
  const GetProductsProductResponse({
    required this.data,
  });
  
  factory GetProductsProductResponse.fromJson(Map<String, Object?> json) => _$GetProductsProductResponseFromJson(json);
  
  final ProductDetail data;

  Map<String, Object?> toJson() => _$GetProductsProductResponseToJson(this);
}
