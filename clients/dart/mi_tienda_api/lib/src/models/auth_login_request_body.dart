// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'auth_login_request_body.g.dart';

@JsonSerializable()
class AuthLoginRequestBody {
  const AuthLoginRequestBody({
    required this.email,
    required this.password,
    required this.deviceId,
    required this.deviceName,
  });
  
  factory AuthLoginRequestBody.fromJson(Map<String, Object?> json) => _$AuthLoginRequestBodyFromJson(json);
  
  final String email;
  final String password;

  /// UUID estable generado por la app al instalarse.
  @JsonKey(name: 'device_id')
  final String deviceId;
  @JsonKey(name: 'device_name')
  final String deviceName;

  Map<String, Object?> toJson() => _$AuthLoginRequestBodyToJson(this);
}
