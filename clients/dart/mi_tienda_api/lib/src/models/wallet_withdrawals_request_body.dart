// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'wallet_withdrawals_request_body.g.dart';

@JsonSerializable()
class WalletWithdrawalsRequestBody {
  const WalletWithdrawalsRequestBody({
    required this.amount,
    required this.fullName,
    required this.bankName,
    required this.accountNumber,
  });
  
  factory WalletWithdrawalsRequestBody.fromJson(Map<String, Object?> json) => _$WalletWithdrawalsRequestBodyFromJson(json);
  
  /// Desde `min_withdrawal` de GET /wallet (y mayor que `network_fee`), hasta el saldo.
  final num amount;
  @JsonKey(name: 'full_name')
  final String fullName;
  @JsonKey(name: 'bank_name')
  final String bankName;

  /// Cuenta local o IBAN.
  @JsonKey(name: 'account_number')
  final String accountNumber;

  Map<String, Object?> toJson() => _$WalletWithdrawalsRequestBodyToJson(this);
}
