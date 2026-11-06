// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'device.dart';

part 'get_auth_devices_response.g.dart';

@JsonSerializable()
class GetAuthDevicesResponse {
  const GetAuthDevicesResponse({
    required this.data,
  });
  
  factory GetAuthDevicesResponse.fromJson(Map<String, Object?> json) => _$GetAuthDevicesResponseFromJson(json);
  
  final List<Device> data;

  Map<String, Object?> toJson() => _$GetAuthDevicesResponseToJson(this);
}
