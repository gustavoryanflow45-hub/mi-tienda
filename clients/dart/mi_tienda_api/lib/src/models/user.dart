// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'nullable_date_time.dart';
import 'user_shop.dart';
import 'user_type.dart';

part 'user.g.dart';

@JsonSerializable()
class User {
  const User({
    required this.id,
    required this.name,
    required this.email,
    required this.phone,
    required this.avatarUrl,
    required this.type,
    required this.emailVerified,
    required this.balance,
    required this.shop,
    required this.createdAt,
  });
  
  factory User.fromJson(Map<String, Object?> json) => _$UserFromJson(json);
  
  final int id;
  final String name;
  final String email;
  final String? phone;
  @JsonKey(name: 'avatar_url')
  final String? avatarUrl;
  final UserType type;
  @JsonKey(name: 'email_verified')
  final bool emailVerified;
  final num balance;
  final UserShop? shop;
  @JsonKey(name: 'created_at')
  final NullableDateTime createdAt;

  Map<String, Object?> toJson() => _$UserToJson(this);
}
