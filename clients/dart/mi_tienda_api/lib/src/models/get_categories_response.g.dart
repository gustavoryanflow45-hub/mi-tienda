// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'get_categories_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GetCategoriesResponse _$GetCategoriesResponseFromJson(
  Map<String, dynamic> json,
) => GetCategoriesResponse(
  data: (json['data'] as List<dynamic>)
      .map((e) => Category.fromJson(e as Map<String, dynamic>))
      .toList(),
);

Map<String, dynamic> _$GetCategoriesResponseToJson(
  GetCategoriesResponse instance,
) => <String, dynamic>{'data': instance.data};
