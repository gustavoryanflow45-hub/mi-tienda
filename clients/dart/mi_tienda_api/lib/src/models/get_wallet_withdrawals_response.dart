// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'paginated.dart';
import 'pagination_links.dart';
import 'pagination_meta.dart';
import 'wallet_withdrawal.dart';

part 'get_wallet_withdrawals_response.g.dart';

@JsonSerializable()
class GetWalletWithdrawalsResponse {
  const GetWalletWithdrawalsResponse({
    required this.links,
    required this.meta,
    this.data,
  });
  
  factory GetWalletWithdrawalsResponse.fromJson(Map<String, Object?> json) => _$GetWalletWithdrawalsResponseFromJson(json);
  
  final List<WalletWithdrawal>? data;
  final PaginationLinks links;
  final PaginationMeta meta;

  Map<String, Object?> toJson() => _$GetWalletWithdrawalsResponseToJson(this);
}
