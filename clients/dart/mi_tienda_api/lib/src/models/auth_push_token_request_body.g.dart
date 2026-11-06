// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'auth_push_token_request_body.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AuthPushTokenRequestBody _$AuthPushTokenRequestBodyFromJson(
  Map<String, dynamic> json,
) => AuthPushTokenRequestBody(
  token: json['token'] as String,
  platform: Platform.fromJson(json['platform'] as String),
);

Map<String, dynamic> _$AuthPushTokenRequestBodyToJson(
  AuthPushTokenRequestBody instance,
) => <String, dynamic>{
  'token': instance.token,
  'platform': _$PlatformEnumMap[instance.platform]!,
};

const _$PlatformEnumMap = {
  Platform.android: 'android',
  Platform.ios: 'ios',
  Platform.$unknown: r'$unknown',
};
