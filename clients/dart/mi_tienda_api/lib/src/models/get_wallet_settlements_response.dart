// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'paginated.dart';
import 'pagination_links.dart';
import 'pagination_meta.dart';
import 'settlement.dart';

part 'get_wallet_settlements_response.g.dart';

@JsonSerializable()
class GetWalletSettlementsResponse {
  const GetWalletSettlementsResponse({
    required this.links,
    required this.meta,
    this.data,
  });
  
  factory GetWalletSettlementsResponse.fromJson(Map<String, Object?> json) => _$GetWalletSettlementsResponseFromJson(json);
  
  final List<Settlement>? data;
  final PaginationLinks links;
  final PaginationMeta meta;

  Map<String, Object?> toJson() => _$GetWalletSettlementsResponseToJson(this);
}
