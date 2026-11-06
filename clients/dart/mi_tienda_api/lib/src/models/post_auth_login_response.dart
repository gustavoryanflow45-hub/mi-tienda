// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'token.dart';

part 'post_auth_login_response.g.dart';

@JsonSerializable()
class PostAuthLoginResponse {
  const PostAuthLoginResponse({
    required this.data,
  });
  
  factory PostAuthLoginResponse.fromJson(Map<String, Object?> json) => _$PostAuthLoginResponseFromJson(json);
  
  final Token data;

  Map<String, Object?> toJson() => _$PostAuthLoginResponseToJson(this);
}
