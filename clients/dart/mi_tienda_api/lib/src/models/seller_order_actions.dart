// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'seller_order_actions.g.dart';

@JsonSerializable()
class SellerOrderActions {
  const SellerOrderActions({
    required this.confirm,
    required this.sendToWarehouse,
  });
  
  factory SellerOrderActions.fromJson(Map<String, Object?> json) => _$SellerOrderActionsFromJson(json);
  
  final bool confirm;
  @JsonKey(name: 'send_to_warehouse')
  final bool sendToWarehouse;

  Map<String, Object?> toJson() => _$SellerOrderActionsToJson(this);
}
