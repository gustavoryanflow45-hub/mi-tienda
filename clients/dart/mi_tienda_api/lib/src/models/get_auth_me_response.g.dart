// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_auth_me_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetAuthMeResponse _$GetAuthMeResponseFromJson(Map<String, dynamic> json) =>
    GetAuthMeResponse(
      data: User.fromJson(json['data'] as Map<String, dynamic>),
      meta: TokenAbilities.fromJson(json['meta'] as Map<String, dynamic>),
    );

Map<String, dynamic> _$GetAuthMeResponseToJson(GetAuthMeResponse instance) =>
    <String, dynamic>{'data': instance.data, 'meta': instance.meta};
