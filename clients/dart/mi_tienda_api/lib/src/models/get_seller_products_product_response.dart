// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'seller_product.dart';

part 'get_seller_products_product_response.g.dart';

@JsonSerializable()
class GetSellerProductsProductResponse {
  const GetSellerProductsProductResponse({
    required this.data,
  });
  
  factory GetSellerProductsProductResponse.fromJson(Map<String, Object?> json) => _$GetSellerProductsProductResponseFromJson(json);
  
  final SellerProduct data;

  Map<String, Object?> toJson() => _$GetSellerProductsProductResponseToJson(this);
}
