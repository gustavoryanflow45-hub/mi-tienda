// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_seller_orders_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetSellerOrdersResponse _$GetSellerOrdersResponseFromJson(
  Map<String, dynamic> json,
) => GetSellerOrdersResponse(
  links: PaginationLinks.fromJson(json['links'] as Map<String, dynamic>),
  meta: PaginationMeta.fromJson(json['meta'] as Map<String, dynamic>),
  data: (json['data'] as List<dynamic>?)
      ?.map((e) => SellerOrder.fromJson(e as Map<String, dynamic>))
      .toList(),
);

Map<String, dynamic> _$GetSellerOrdersResponseToJson(
  GetSellerOrdersResponse instance,
) => <String, dynamic>{
  'data': instance.data,
  'links': instance.links,
  'meta': instance.meta,
};
