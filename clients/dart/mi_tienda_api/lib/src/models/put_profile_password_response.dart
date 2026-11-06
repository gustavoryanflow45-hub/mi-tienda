// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'put_profile_password_response.g.dart';

@JsonSerializable()
class PutProfilePasswordResponse {
  const PutProfilePasswordResponse({
    required this.message,
  });
  
  factory PutProfilePasswordResponse.fromJson(Map<String, Object?> json) => _$PutProfilePasswordResponseFromJson(json);
  
  final String message;

  Map<String, Object?> toJson() => _$PutProfilePasswordResponseToJson(this);
}
