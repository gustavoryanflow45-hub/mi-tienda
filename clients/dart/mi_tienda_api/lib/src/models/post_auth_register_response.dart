// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'user.dart';

part 'post_auth_register_response.g.dart';

@JsonSerializable()
class PostAuthRegisterResponse {
  const PostAuthRegisterResponse({
    required this.data,
    required this.message,
  });
  
  factory PostAuthRegisterResponse.fromJson(Map<String, Object?> json) => _$PostAuthRegisterResponseFromJson(json);
  
  final User data;
  final String message;

  Map<String, Object?> toJson() => _$PostAuthRegisterResponseToJson(this);
}
