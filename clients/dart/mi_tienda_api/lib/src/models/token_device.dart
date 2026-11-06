// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'token_device.g.dart';

@JsonSerializable()
class TokenDevice {
  const TokenDevice({
    required this.id,
    required this.deviceId,
    required this.deviceName,
  });
  
  factory TokenDevice.fromJson(Map<String, Object?> json) => _$TokenDeviceFromJson(json);
  
  final int id;
  @JsonKey(name: 'device_id')
  final String? deviceId;
  @JsonKey(name: 'device_name')
  final String deviceName;

  Map<String, Object?> toJson() => _$TokenDeviceToJson(this);
}
