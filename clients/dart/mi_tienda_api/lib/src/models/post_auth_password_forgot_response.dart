// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'post_auth_password_forgot_response.g.dart';

@JsonSerializable()
class PostAuthPasswordForgotResponse {
  const PostAuthPasswordForgotResponse({
    required this.message,
  });
  
  factory PostAuthPasswordForgotResponse.fromJson(Map<String, Object?> json) => _$PostAuthPasswordForgotResponseFromJson(json);
  
  final String message;

  Map<String, Object?> toJson() => _$PostAuthPasswordForgotResponseToJson(this);
}
