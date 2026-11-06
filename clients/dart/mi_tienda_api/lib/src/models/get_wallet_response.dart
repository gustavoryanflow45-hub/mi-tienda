// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'wallet_summary.dart';

part 'get_wallet_response.g.dart';

@JsonSerializable()
class GetWalletResponse {
  const GetWalletResponse({
    required this.data,
  });
  
  factory GetWalletResponse.fromJson(Map<String, Object?> json) => _$GetWalletResponseFromJson(json);
  
  final WalletSummary data;

  Map<String, Object?> toJson() => _$GetWalletResponseToJson(this);
}
