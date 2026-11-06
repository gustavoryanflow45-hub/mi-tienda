// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'post_seller_products_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PostSellerProductsResponse _$PostSellerProductsResponseFromJson(
  Map<String, dynamic> json,
) => PostSellerProductsResponse(
  data: SellerProduct.fromJson(json['data'] as Map<String, dynamic>),
);

Map<String, dynamic> _$PostSellerProductsResponseToJson(
  PostSellerProductsResponse instance,
) => <String, dynamic>{'data': instance.data};
