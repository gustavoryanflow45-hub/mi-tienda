// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'user.dart';

part 'get_profile_response.g.dart';

@JsonSerializable()
class GetProfileResponse {
  const GetProfileResponse({
    required this.data,
  });
  
  factory GetProfileResponse.fromJson(Map<String, Object?> json) => _$GetProfileResponseFromJson(json);
  
  final User data;

  Map<String, Object?> toJson() => _$GetProfileResponseToJson(this);
}
