// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_wallet_recharges_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetWalletRechargesResponse _$GetWalletRechargesResponseFromJson(
  Map<String, dynamic> json,
) => GetWalletRechargesResponse(
  links: PaginationLinks.fromJson(json['links'] as Map<String, dynamic>),
  meta: PaginationMeta.fromJson(json['meta'] as Map<String, dynamic>),
  data: (json['data'] as List<dynamic>?)
      ?.map((e) => WalletRecharge.fromJson(e as Map<String, dynamic>))
      .toList(),
);

Map<String, dynamic> _$GetWalletRechargesResponseToJson(
  GetWalletRechargesResponse instance,
) => <String, dynamic>{
  'data': instance.data,
  'links': instance.links,
  'meta': instance.meta,
};
