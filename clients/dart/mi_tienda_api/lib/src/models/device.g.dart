// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'device.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

Device _$DeviceFromJson(Map<String, dynamic> json) => Device(
  id: (json['id'] as num).toInt(),
  deviceId: json['device_id'] as String?,
  deviceName: json['device_name'] as String,
  abilities: (json['abilities'] as List<dynamic>)
      .map((e) => e as String)
      .toList(),
  current: json['current'] as bool,
  lastUsedAt: json['last_used_at'] == null
      ? null
      : DateTime.parse(json['last_used_at'] as String),
  createdAt: json['created_at'] == null
      ? null
      : DateTime.parse(json['created_at'] as String),
  expiresAt: json['expires_at'] == null
      ? null
      : DateTime.parse(json['expires_at'] as String),
  pushEnabled: json['push_enabled'] as bool,
  pushPlatform: json['push_platform'] == null
      ? null
      : DevicePushPlatform.fromJson(json['push_platform'] as String?),
);

Map<String, dynamic> _$DeviceToJson(Device instance) => <String, dynamic>{
  'id': instance.id,
  'device_id': instance.deviceId,
  'device_name': instance.deviceName,
  'abilities': instance.abilities,
  'current': instance.current,
  'last_used_at': instance.lastUsedAt?.toIso8601String(),
  'created_at': instance.createdAt?.toIso8601String(),
  'expires_at': instance.expiresAt?.toIso8601String(),
  'push_enabled': instance.pushEnabled,
  'push_platform': _$DevicePushPlatformEnumMap[instance.pushPlatform],
};

const _$DevicePushPlatformEnumMap = {
  DevicePushPlatform.android: 'android',
  DevicePushPlatform.ios: 'ios',
  DevicePushPlatform.valueNull: null,
  DevicePushPlatform.$unknown: r'$unknown',
};
