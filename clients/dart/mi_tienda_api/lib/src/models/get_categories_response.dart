// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'category.dart';

part 'get_categories_response.g.dart';

@JsonSerializable()
class GetCategoriesResponse {
  const GetCategoriesResponse({
    required this.data,
  });
  
  factory GetCategoriesResponse.fromJson(Map<String, Object?> json) => _$GetCategoriesResponseFromJson(json);
  
  final List<Category> data;

  Map<String, Object?> toJson() => _$GetCategoriesResponseToJson(this);
}
