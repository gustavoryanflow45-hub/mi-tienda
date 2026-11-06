// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'nullable_date_time.dart';
import 'wallet_status.dart';

part 'wallet_recharge.g.dart';

@JsonSerializable()
class WalletRecharge {
  const WalletRecharge({
    required this.id,
    required this.amount,
    required this.fee,
    required this.netAmount,
    required this.network,
    required this.transactionId,
    required this.hasProof,
    required this.status,
    required this.createdAt,
  });
  
  factory WalletRecharge.fromJson(Map<String, Object?> json) => _$WalletRechargeFromJson(json);
  
  final int id;

  /// Lo transferido por el usuario.
  final num amount;

  /// Comisión de red (0 si se pidió en la web).
  final num fee;

  /// Lo que se acredita al aprobarse: amount − fee.
  @JsonKey(name: 'net_amount')
  final num netAmount;
  final String? network;
  @JsonKey(name: 'transaction_id')
  final String? transactionId;
  @JsonKey(name: 'has_proof')
  final bool hasProof;
  final WalletStatus status;
  @JsonKey(name: 'created_at')
  final NullableDateTime createdAt;

  Map<String, Object?> toJson() => _$WalletRechargeToJson(this);
}
