// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'post_auth_login_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PostAuthLoginResponse _$PostAuthLoginResponseFromJson(
  Map<String, dynamic> json,
) => PostAuthLoginResponse(
  data: Token.fromJson(json['data'] as Map<String, dynamic>),
);

Map<String, dynamic> _$PostAuthLoginResponseToJson(
  PostAuthLoginResponse instance,
) => <String, dynamic>{'data': instance.data};
