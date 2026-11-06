// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'paginated.dart';
import 'pagination_links.dart';
import 'pagination_meta.dart';
import 'wallet_movement.dart';

part 'get_wallet_movements_response.g.dart';

@JsonSerializable()
class GetWalletMovementsResponse {
  const GetWalletMovementsResponse({
    required this.links,
    required this.meta,
    this.data,
  });
  
  factory GetWalletMovementsResponse.fromJson(Map<String, Object?> json) => _$GetWalletMovementsResponseFromJson(json);
  
  final List<WalletMovement>? data;
  final PaginationLinks links;
  final PaginationMeta meta;

  Map<String, Object?> toJson() => _$GetWalletMovementsResponseToJson(this);
}
