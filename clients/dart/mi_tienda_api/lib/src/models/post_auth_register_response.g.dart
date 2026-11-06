// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'post_auth_register_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PostAuthRegisterResponse _$PostAuthRegisterResponseFromJson(
  Map<String, dynamic> json,
) => PostAuthRegisterResponse(
  data: User.fromJson(json['data'] as Map<String, dynamic>),
  message: json['message'] as String,
);

Map<String, dynamic> _$PostAuthRegisterResponseToJson(
  PostAuthRegisterResponse instance,
) => <String, dynamic>{'data': instance.data, 'message': instance.message};
