// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'wallet_recharge.dart';

part 'post_wallet_recharges_response.g.dart';

@JsonSerializable()
class PostWalletRechargesResponse {
  const PostWalletRechargesResponse({
    required this.data,
    required this.message,
  });
  
  factory PostWalletRechargesResponse.fromJson(Map<String, Object?> json) => _$PostWalletRechargesResponseFromJson(json);
  
  final WalletRecharge data;
  final String message;

  Map<String, Object?> toJson() => _$PostWalletRechargesResponseToJson(this);
}
