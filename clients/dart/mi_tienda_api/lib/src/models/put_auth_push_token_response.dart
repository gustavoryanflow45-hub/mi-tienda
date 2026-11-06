// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'device.dart';

part 'put_auth_push_token_response.g.dart';

@JsonSerializable()
class PutAuthPushTokenResponse {
  const PutAuthPushTokenResponse({
    required this.data,
  });
  
  factory PutAuthPushTokenResponse.fromJson(Map<String, Object?> json) => _$PutAuthPushTokenResponseFromJson(json);
  
  final Device data;

  Map<String, Object?> toJson() => _$PutAuthPushTokenResponseToJson(this);
}
