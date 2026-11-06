// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_withdrawals_request_body.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletWithdrawalsRequestBody _$WalletWithdrawalsRequestBodyFromJson(
  Map<String, dynamic> json,
) => WalletWithdrawalsRequestBody(
  amount: json['amount'] as num,
  fullName: json['full_name'] as String,
  bankName: json['bank_name'] as String,
  accountNumber: json['account_number'] as String,
);

Map<String, dynamic> _$WalletWithdrawalsRequestBodyToJson(
  WalletWithdrawalsRequestBody instance,
) => <String, dynamic>{
  'amount': instance.amount,
  'full_name': instance.fullName,
  'bank_name': instance.bankName,
  'account_number': instance.accountNumber,
};
