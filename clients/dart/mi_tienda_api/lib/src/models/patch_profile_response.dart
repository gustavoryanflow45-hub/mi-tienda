// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'user.dart';

part 'patch_profile_response.g.dart';

@JsonSerializable()
class PatchProfileResponse {
  const PatchProfileResponse({
    required this.data,
  });
  
  factory PatchProfileResponse.fromJson(Map<String, Object?> json) => _$PatchProfileResponseFromJson(json);
  
  final User data;

  Map<String, Object?> toJson() => _$PatchProfileResponseToJson(this);
}
