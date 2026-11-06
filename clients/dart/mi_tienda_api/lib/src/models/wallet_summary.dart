// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'wallet_summary.g.dart';

@JsonSerializable()
class WalletSummary {
  const WalletSummary({
    required this.balance,
    required this.currency,
    required this.networkFee,
    required this.pendingRecharges,
    required this.pendingWithdrawals,
    required this.minWithdrawal,
    required this.rechargeNetworks,
    required this.maxUploadKb,
  });
  
  factory WalletSummary.fromJson(Map<String, Object?> json) => _$WalletSummaryFromJson(json);
  
  final num balance;
  final String currency;

  /// Comisión de red de cada recarga y retiro de la app.
  @JsonKey(name: 'network_fee')
  final num networkFee;
  @JsonKey(name: 'pending_recharges')
  final int pendingRecharges;
  @JsonKey(name: 'pending_withdrawals')
  final int pendingWithdrawals;
  @JsonKey(name: 'min_withdrawal')
  final num minWithdrawal;
  @JsonKey(name: 'recharge_networks')
  final List<String> rechargeNetworks;
  @JsonKey(name: 'max_upload_kb')
  final int maxUploadKb;

  Map<String, Object?> toJson() => _$WalletSummaryToJson(this);
}
