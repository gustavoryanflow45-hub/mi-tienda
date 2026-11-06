// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'user_shop_status.dart';

part 'user_shop.g.dart';

@JsonSerializable()
class UserShop {
  const UserShop({
    required this.id,
    required this.name,
    required this.status,
  });
  
  factory UserShop.fromJson(Map<String, Object?> json) => _$UserShopFromJson(json);
  
  final int id;
  final String name;
  final UserShopStatus status;

  Map<String, Object?> toJson() => _$UserShopToJson(this);
}
