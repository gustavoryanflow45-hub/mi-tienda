// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'auth_password_forgot_request_body.g.dart';

@JsonSerializable()
class AuthPasswordForgotRequestBody {
  const AuthPasswordForgotRequestBody({
    required this.email,
  });
  
  factory AuthPasswordForgotRequestBody.fromJson(Map<String, Object?> json) => _$AuthPasswordForgotRequestBodyFromJson(json);
  
  final String email;

  Map<String, Object?> toJson() => _$AuthPasswordForgotRequestBodyToJson(this);
}
