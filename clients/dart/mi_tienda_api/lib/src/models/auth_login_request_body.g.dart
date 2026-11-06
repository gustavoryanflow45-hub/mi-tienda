// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'auth_login_request_body.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AuthLoginRequestBody _$AuthLoginRequestBodyFromJson(
  Map<String, dynamic> json,
) => AuthLoginRequestBody(
  email: json['email'] as String,
  password: json['password'] as String,
  deviceId: json['device_id'] as String,
  deviceName: json['device_name'] as String,
);

Map<String, dynamic> _$AuthLoginRequestBodyToJson(
  AuthLoginRequestBody instance,
) => <String, dynamic>{
  'email': instance.email,
  'password': instance.password,
  'device_id': instance.deviceId,
  'device_name': instance.deviceName,
};
