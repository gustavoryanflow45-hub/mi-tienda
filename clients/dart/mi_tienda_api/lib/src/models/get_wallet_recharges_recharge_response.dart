// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'wallet_recharge.dart';

part 'get_wallet_recharges_recharge_response.g.dart';

@JsonSerializable()
class GetWalletRechargesRechargeResponse {
  const GetWalletRechargesRechargeResponse({
    required this.data,
  });
  
  factory GetWalletRechargesRechargeResponse.fromJson(Map<String, Object?> json) => _$GetWalletRechargesRechargeResponseFromJson(json);
  
  final WalletRecharge data;

  Map<String, Object?> toJson() => _$GetWalletRechargesRechargeResponseToJson(this);
}
