// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'wallet_withdrawal.dart';

part 'post_wallet_withdrawals_response.g.dart';

@JsonSerializable()
class PostWalletWithdrawalsResponse {
  const PostWalletWithdrawalsResponse({
    required this.data,
    required this.message,
  });
  
  factory PostWalletWithdrawalsResponse.fromJson(Map<String, Object?> json) => _$PostWalletWithdrawalsResponseFromJson(json);
  
  final WalletWithdrawal data;
  final String message;

  Map<String, Object?> toJson() => _$PostWalletWithdrawalsResponseToJson(this);
}
