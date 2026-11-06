// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'token.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

Token _$TokenFromJson(Map<String, dynamic> json) => Token(
  token: json['token'] as String,
  tokenType: json['token_type'] as String,
  expiresAt: json['expires_at'] == null
      ? null
      : DateTime.parse(json['expires_at'] as String),
  abilities: (json['abilities'] as List<dynamic>)
      .map((e) => e as String)
      .toList(),
  device: TokenDevice.fromJson(json['device'] as Map<String, dynamic>),
  user: User.fromJson(json['user'] as Map<String, dynamic>),
);

Map<String, dynamic> _$TokenToJson(Token instance) => <String, dynamic>{
  'token': instance.token,
  'token_type': instance.tokenType,
  'expires_at': instance.expiresAt?.toIso8601String(),
  'abilities': instance.abilities,
  'device': instance.device,
  'user': instance.user,
};
