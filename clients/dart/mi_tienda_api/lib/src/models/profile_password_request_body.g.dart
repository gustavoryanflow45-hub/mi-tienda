// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'profile_password_request_body.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProfilePasswordRequestBody _$ProfilePasswordRequestBodyFromJson(
  Map<String, dynamic> json,
) => ProfilePasswordRequestBody(
  currentPassword: json['current_password'] as String,
  password: json['password'] as String,
  passwordConfirmation: json['password_confirmation'] as String,
);

Map<String, dynamic> _$ProfilePasswordRequestBodyToJson(
  ProfilePasswordRequestBody instance,
) => <String, dynamic>{
  'current_password': instance.currentPassword,
  'password': instance.password,
  'password_confirmation': instance.passwordConfirmation,
};
