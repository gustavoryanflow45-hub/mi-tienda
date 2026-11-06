// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'nullable_date_time.dart';
import 'wallet_movement_type.dart';
import 'wallet_status.dart';

part 'wallet_movement.g.dart';

@JsonSerializable()
class WalletMovement {
  const WalletMovement({
    required this.type,
    required this.id,
    required this.amount,
    required this.status,
    required this.occurredAt,
  });
  
  factory WalletMovement.fromJson(Map<String, Object?> json) => _$WalletMovementFromJson(json);
  
  final WalletMovementType type;

  /// Id en su tipo: lleva a /wallet/recharges/{id} o /wallet/withdrawals/{id}.
  final int id;

  /// Con signo: + suma al saldo, − resta. En una recarga, ya sin la comisión.
  final num amount;
  final WalletStatus status;
  @JsonKey(name: 'occurred_at')
  final NullableDateTime occurredAt;

  Map<String, Object?> toJson() => _$WalletMovementToJson(this);
}
