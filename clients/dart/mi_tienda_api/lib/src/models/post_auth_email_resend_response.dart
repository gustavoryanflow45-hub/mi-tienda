// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'post_auth_email_resend_response.g.dart';

@JsonSerializable()
class PostAuthEmailResendResponse {
  const PostAuthEmailResendResponse({
    required this.message,
  });
  
  factory PostAuthEmailResendResponse.fromJson(Map<String, Object?> json) => _$PostAuthEmailResendResponseFromJson(json);
  
  final String message;

  Map<String, Object?> toJson() => _$PostAuthEmailResendResponseToJson(this);
}
