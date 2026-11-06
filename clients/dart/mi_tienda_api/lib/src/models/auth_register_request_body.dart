// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'auth_register_request_body.g.dart';

@JsonSerializable()
class AuthRegisterRequestBody {
  const AuthRegisterRequestBody({
    required this.name,
    required this.email,
    required this.password,
    required this.passwordConfirmation,
    this.phone,
  });
  
  factory AuthRegisterRequestBody.fromJson(Map<String, Object?> json) => _$AuthRegisterRequestBodyFromJson(json);
  
  final String name;
  final String email;

  /// Letras y números.
  final String password;
  @JsonKey(name: 'password_confirmation')
  final String passwordConfirmation;
  final String? phone;

  Map<String, Object?> toJson() => _$AuthRegisterRequestBodyToJson(this);
}
