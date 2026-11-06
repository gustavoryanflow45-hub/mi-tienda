// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_wallet_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetWalletResponse _$GetWalletResponseFromJson(Map<String, dynamic> json) =>
    GetWalletResponse(
      data: WalletSummary.fromJson(json['data'] as Map<String, dynamic>),
    );

Map<String, dynamic> _$GetWalletResponseToJson(GetWalletResponse instance) =>
    <String, dynamic>{'data': instance.data};
