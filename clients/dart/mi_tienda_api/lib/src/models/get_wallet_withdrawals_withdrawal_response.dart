// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'wallet_withdrawal.dart';

part 'get_wallet_withdrawals_withdrawal_response.g.dart';

@JsonSerializable()
class GetWalletWithdrawalsWithdrawalResponse {
  const GetWalletWithdrawalsWithdrawalResponse({
    required this.data,
  });
  
  factory GetWalletWithdrawalsWithdrawalResponse.fromJson(Map<String, Object?> json) => _$GetWalletWithdrawalsWithdrawalResponseFromJson(json);
  
  final WalletWithdrawal data;

  Map<String, Object?> toJson() => _$GetWalletWithdrawalsWithdrawalResponseToJson(this);
}
