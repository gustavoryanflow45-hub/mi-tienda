// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'nullable_date_time.dart';
import 'wallet_status.dart';

part 'wallet_withdrawal.g.dart';

@JsonSerializable()
class WalletWithdrawal {
  const WalletWithdrawal({
    required this.id,
    required this.amount,
    required this.fee,
    required this.netAmount,
    required this.fullName,
    required this.bankName,
    required this.accountLast4,
    required this.status,
    required this.fromSettlement,
    required this.createdAt,
  });
  
  factory WalletWithdrawal.fromJson(Map<String, Object?> json) => _$WalletWithdrawalFromJson(json);
  
  final int id;

  /// Lo descontado del saldo.
  final num amount;

  /// Comisión de red (0 si se pidió en la web).
  final num fee;

  /// Lo que el admin transfiere al aprobarlo: amount − fee.
  @JsonKey(name: 'net_amount')
  final num netAmount;
  @JsonKey(name: 'full_name')
  final String fullName;
  @JsonKey(name: 'bank_name')
  final String bankName;

  /// La cuenta nunca se devuelve entera.
  @JsonKey(name: 'account_last4')
  final String accountLast4;
  final WalletStatus status;

  /// true = lo cubren ventas ya liquidadas (dato para el admin; igual espera su aprobación).
  @JsonKey(name: 'from_settlement')
  final bool fromSettlement;
  @JsonKey(name: 'created_at')
  final NullableDateTime createdAt;

  Map<String, Object?> toJson() => _$WalletWithdrawalToJson(this);
}
