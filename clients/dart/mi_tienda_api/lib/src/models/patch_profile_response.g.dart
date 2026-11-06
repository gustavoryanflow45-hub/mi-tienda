// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'patch_profile_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PatchProfileResponse _$PatchProfileResponseFromJson(
  Map<String, dynamic> json,
) => PatchProfileResponse(
  data: User.fromJson(json['data'] as Map<String, dynamic>),
);

Map<String, dynamic> _$PatchProfileResponseToJson(
  PatchProfileResponse instance,
) => <String, dynamic>{'data': instance.data};
