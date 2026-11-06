// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'token_device.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TokenDevice _$TokenDeviceFromJson(Map<String, dynamic> json) => TokenDevice(
  id: (json['id'] as num).toInt(),
  deviceId: json['device_id'] as String?,
  deviceName: json['device_name'] as String,
);

Map<String, dynamic> _$TokenDeviceToJson(TokenDevice instance) =>
    <String, dynamic>{
      'id': instance.id,
      'device_id': instance.deviceId,
      'device_name': instance.deviceName,
    };
