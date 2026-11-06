// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'category.dart';

part 'get_categories_slug_response.g.dart';

@JsonSerializable()
class GetCategoriesSlugResponse {
  const GetCategoriesSlugResponse({
    required this.data,
  });
  
  factory GetCategoriesSlugResponse.fromJson(Map<String, Object?> json) => _$GetCategoriesSlugResponseFromJson(json);
  
  final Category data;

  Map<String, Object?> toJson() => _$GetCategoriesSlugResponseToJson(this);
}
