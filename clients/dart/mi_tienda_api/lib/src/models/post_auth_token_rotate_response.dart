// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'token.dart';

part 'post_auth_token_rotate_response.g.dart';

@JsonSerializable()
class PostAuthTokenRotateResponse {
  const PostAuthTokenRotateResponse({
    required this.data,
  });
  
  factory PostAuthTokenRotateResponse.fromJson(Map<String, Object?> json) => _$PostAuthTokenRotateResponseFromJson(json);
  
  final Token data;

  Map<String, Object?> toJson() => _$PostAuthTokenRotateResponseToJson(this);
}
