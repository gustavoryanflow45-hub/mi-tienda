// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_wallet_settlements_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetWalletSettlementsResponse _$GetWalletSettlementsResponseFromJson(
  Map<String, dynamic> json,
) => GetWalletSettlementsResponse(
  links: PaginationLinks.fromJson(json['links'] as Map<String, dynamic>),
  meta: PaginationMeta.fromJson(json['meta'] as Map<String, dynamic>),
  data: (json['data'] as List<dynamic>?)
      ?.map((e) => Settlement.fromJson(e as Map<String, dynamic>))
      .toList(),
);

Map<String, dynamic> _$GetWalletSettlementsResponseToJson(
  GetWalletSettlementsResponse instance,
) => <String, dynamic>{
  'data': instance.data,
  'links': instance.links,
  'meta': instance.meta,
};
