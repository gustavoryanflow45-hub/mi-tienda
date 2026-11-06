// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_wallet_withdrawals_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetWalletWithdrawalsResponse _$GetWalletWithdrawalsResponseFromJson(
  Map<String, dynamic> json,
) => GetWalletWithdrawalsResponse(
  links: PaginationLinks.fromJson(json['links'] as Map<String, dynamic>),
  meta: PaginationMeta.fromJson(json['meta'] as Map<String, dynamic>),
  data: (json['data'] as List<dynamic>?)
      ?.map((e) => WalletWithdrawal.fromJson(e as Map<String, dynamic>))
      .toList(),
);

Map<String, dynamic> _$GetWalletWithdrawalsResponseToJson(
  GetWalletWithdrawalsResponse instance,
) => <String, dynamic>{
  'data': instance.data,
  'links': instance.links,
  'meta': instance.meta,
};
