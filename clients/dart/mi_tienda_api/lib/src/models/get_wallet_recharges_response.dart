// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'paginated.dart';
import 'pagination_links.dart';
import 'pagination_meta.dart';
import 'wallet_recharge.dart';

part 'get_wallet_recharges_response.g.dart';

@JsonSerializable()
class GetWalletRechargesResponse {
  const GetWalletRechargesResponse({
    required this.links,
    required this.meta,
    this.data,
  });
  
  factory GetWalletRechargesResponse.fromJson(Map<String, Object?> json) => _$GetWalletRechargesResponseFromJson(json);
  
  final List<WalletRecharge>? data;
  final PaginationLinks links;
  final PaginationMeta meta;

  Map<String, Object?> toJson() => _$GetWalletRechargesResponseToJson(this);
}
