// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_brands_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetBrandsResponse _$GetBrandsResponseFromJson(Map<String, dynamic> json) =>
    GetBrandsResponse(
      data: (json['data'] as List<dynamic>)
          .map((e) => Brand.fromJson(e as Map<String, dynamic>))
          .toList(),
    );

Map<String, dynamic> _$GetBrandsResponseToJson(GetBrandsResponse instance) =>
    <String, dynamic>{'data': instance.data};
