// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_auth_devices_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetAuthDevicesResponse _$GetAuthDevicesResponseFromJson(
  Map<String, dynamic> json,
) => GetAuthDevicesResponse(
  data: (json['data'] as List<dynamic>)
      .map((e) => Device.fromJson(e as Map<String, dynamic>))
      .toList(),
);

Map<String, dynamic> _$GetAuthDevicesResponseToJson(
  GetAuthDevicesResponse instance,
) => <String, dynamic>{'data': instance.data};
