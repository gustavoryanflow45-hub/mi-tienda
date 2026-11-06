// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'post_wallet_withdrawals_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PostWalletWithdrawalsResponse _$PostWalletWithdrawalsResponseFromJson(
  Map<String, dynamic> json,
) => PostWalletWithdrawalsResponse(
  data: WalletWithdrawal.fromJson(json['data'] as Map<String, dynamic>),
  message: json['message'] as String,
);

Map<String, dynamic> _$PostWalletWithdrawalsResponseToJson(
  PostWalletWithdrawalsResponse instance,
) => <String, dynamic>{'data': instance.data, 'message': instance.message};
