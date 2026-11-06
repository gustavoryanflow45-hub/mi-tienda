// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'device_push_platform.dart';
import 'nullable_date_time.dart';

part 'device.g.dart';

@JsonSerializable()
class Device {
  const Device({
    required this.id,
    required this.deviceId,
    required this.deviceName,
    required this.abilities,
    required this.current,
    required this.lastUsedAt,
    required this.createdAt,
    required this.expiresAt,
    required this.pushEnabled,
    required this.pushPlatform,
  });
  
  factory Device.fromJson(Map<String, Object?> json) => _$DeviceFromJson(json);
  
  final int id;
  @JsonKey(name: 'device_id')
  final String? deviceId;
  @JsonKey(name: 'device_name')
  final String deviceName;
  final List<String> abilities;
  final bool current;
  @JsonKey(name: 'last_used_at')
  final NullableDateTime lastUsedAt;
  @JsonKey(name: 'created_at')
  final NullableDateTime createdAt;
  @JsonKey(name: 'expires_at')
  final NullableDateTime expiresAt;

  /// Hay un token FCM registrado (nunca se devuelve).
  @JsonKey(name: 'push_enabled')
  final bool pushEnabled;
  @JsonKey(name: 'push_platform')
  final DevicePushPlatform? pushPlatform;

  Map<String, Object?> toJson() => _$DeviceToJson(this);
}
