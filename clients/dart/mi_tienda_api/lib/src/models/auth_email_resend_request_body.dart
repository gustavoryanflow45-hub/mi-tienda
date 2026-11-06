// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'auth_email_resend_request_body.g.dart';

@JsonSerializable()
class AuthEmailResendRequestBody {
  const AuthEmailResendRequestBody({
    required this.email,
  });
  
  factory AuthEmailResendRequestBody.fromJson(Map<String, Object?> json) => _$AuthEmailResendRequestBodyFromJson(json);
  
  final String email;

  Map<String, Object?> toJson() => _$AuthEmailResendRequestBodyToJson(this);
}
