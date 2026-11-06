// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'profile_password_request_body.g.dart';

@JsonSerializable()
class ProfilePasswordRequestBody {
  const ProfilePasswordRequestBody({
    required this.currentPassword,
    required this.password,
    required this.passwordConfirmation,
  });
  
  factory ProfilePasswordRequestBody.fromJson(Map<String, Object?> json) => _$ProfilePasswordRequestBodyFromJson(json);
  
  @JsonKey(name: 'current_password')
  final String currentPassword;

  /// Letras y números.
  final String password;
  @JsonKey(name: 'password_confirmation')
  final String passwordConfirmation;

  Map<String, Object?> toJson() => _$ProfilePasswordRequestBodyToJson(this);
}
