// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'user.dart';
import 'token_abilities.dart';

part 'get_auth_me_response.g.dart';

@JsonSerializable()
class GetAuthMeResponse {
  const GetAuthMeResponse({
    required this.data,
    required this.meta,
  });
  
  factory GetAuthMeResponse.fromJson(Map<String, Object?> json) => _$GetAuthMeResponseFromJson(json);
  
  final User data;
  final TokenAbilities meta;

  Map<String, Object?> toJson() => _$GetAuthMeResponseToJson(this);
}
