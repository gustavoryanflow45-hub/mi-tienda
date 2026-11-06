// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'auth_register_request_body.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AuthRegisterRequestBody _$AuthRegisterRequestBodyFromJson(
  Map<String, dynamic> json,
) => AuthRegisterRequestBody(
  name: json['name'] as String,
  email: json['email'] as String,
  password: json['password'] as String,
  passwordConfirmation: json['password_confirmation'] as String,
  phone: json['phone'] as String?,
);

Map<String, dynamic> _$AuthRegisterRequestBodyToJson(
  AuthRegisterRequestBody instance,
) => <String, dynamic>{
  'name': instance.name,
  'email': instance.email,
  'password': instance.password,
  'password_confirmation': instance.passwordConfirmation,
  'phone': instance.phone,
};
