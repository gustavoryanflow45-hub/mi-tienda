// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'user.dart';

part 'post_profile_avatar_response.g.dart';

@JsonSerializable()
class PostProfileAvatarResponse {
  const PostProfileAvatarResponse({
    required this.data,
  });
  
  factory PostProfileAvatarResponse.fromJson(Map<String, Object?> json) => _$PostProfileAvatarResponseFromJson(json);
  
  final User data;

  Map<String, Object?> toJson() => _$PostProfileAvatarResponseToJson(this);
}
