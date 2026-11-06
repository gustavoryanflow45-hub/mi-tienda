// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'nullable_date_time.dart';
import 'token_device.dart';
import 'user.dart';

part 'token.g.dart';

@JsonSerializable()
class Token {
  const Token({
    required this.token,
    required this.tokenType,
    required this.expiresAt,
    required this.abilities,
    required this.device,
    required this.user,
  });
  
  factory Token.fromJson(Map<String, Object?> json) => _$TokenFromJson(json);
  
  /// Guardar en almacenamiento seguro.
  final String token;
  @JsonKey(name: 'token_type')
  final String tokenType;
  @JsonKey(name: 'expires_at')
  final NullableDateTime expiresAt;
  final List<String> abilities;
  final TokenDevice device;
  final User user;

  Map<String, Object?> toJson() => _$TokenToJson(this);
}
