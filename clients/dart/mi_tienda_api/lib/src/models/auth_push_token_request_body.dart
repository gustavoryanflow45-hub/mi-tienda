// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'platform.dart';

part 'auth_push_token_request_body.g.dart';

@JsonSerializable()
class AuthPushTokenRequestBody {
  const AuthPushTokenRequestBody({
    required this.token,
    required this.platform,
  });
  
  factory AuthPushTokenRequestBody.fromJson(Map<String, Object?> json) => _$AuthPushTokenRequestBodyFromJson(json);
  
  final String token;
  final Platform platform;

  Map<String, Object?> toJson() => _$AuthPushTokenRequestBodyToJson(this);
}
