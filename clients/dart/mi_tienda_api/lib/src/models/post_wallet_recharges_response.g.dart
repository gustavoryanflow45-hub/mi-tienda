// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'post_wallet_recharges_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PostWalletRechargesResponse _$PostWalletRechargesResponseFromJson(
  Map<String, dynamic> json,
) => PostWalletRechargesResponse(
  data: WalletRecharge.fromJson(json['data'] as Map<String, dynamic>),
  message: json['message'] as String,
);

Map<String, dynamic> _$PostWalletRechargesResponseToJson(
  PostWalletRechargesResponse instance,
) => <String, dynamic>{'data': instance.data, 'message': instance.message};
