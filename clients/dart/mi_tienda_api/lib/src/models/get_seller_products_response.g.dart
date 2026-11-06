// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_seller_products_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetSellerProductsResponse _$GetSellerProductsResponseFromJson(
  Map<String, dynamic> json,
) => GetSellerProductsResponse(
  links: PaginationLinks.fromJson(json['links'] as Map<String, dynamic>),
  meta: PaginationMeta.fromJson(json['meta'] as Map<String, dynamic>),
  data: (json['data'] as List<dynamic>?)
      ?.map((e) => SellerProduct.fromJson(e as Map<String, dynamic>))
      .toList(),
);

Map<String, dynamic> _$GetSellerProductsResponseToJson(
  GetSellerProductsResponse instance,
) => <String, dynamic>{
  'data': instance.data,
  'links': instance.links,
  'meta': instance.meta,
};
