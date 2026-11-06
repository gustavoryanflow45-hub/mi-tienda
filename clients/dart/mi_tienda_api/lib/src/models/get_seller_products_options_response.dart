// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'seller_product_options.dart';

part 'get_seller_products_options_response.g.dart';

@JsonSerializable()
class GetSellerProductsOptionsResponse {
  const GetSellerProductsOptionsResponse({
    required this.data,
  });
  
  factory GetSellerProductsOptionsResponse.fromJson(Map<String, Object?> json) => _$GetSellerProductsOptionsResponseFromJson(json);
  
  final SellerProductOptions data;

  Map<String, Object?> toJson() => _$GetSellerProductsOptionsResponseToJson(this);
}
