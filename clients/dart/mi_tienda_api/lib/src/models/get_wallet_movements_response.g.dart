// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_wallet_movements_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetWalletMovementsResponse _$GetWalletMovementsResponseFromJson(
  Map<String, dynamic> json,
) => GetWalletMovementsResponse(
  links: PaginationLinks.fromJson(json['links'] as Map<String, dynamic>),
  meta: PaginationMeta.fromJson(json['meta'] as Map<String, dynamic>),
  data: (json['data'] as List<dynamic>?)
      ?.map((e) => WalletMovement.fromJson(e as Map<String, dynamic>))
      .toList(),
);

Map<String, dynamic> _$GetWalletMovementsResponseToJson(
  GetWalletMovementsResponse instance,
) => <String, dynamic>{
  'data': instance.data,
  'links': instance.links,
  'meta': instance.meta,
};
