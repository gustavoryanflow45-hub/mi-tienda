// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'category_ref.g.dart';

@JsonSerializable()
class CategoryRef {
  const CategoryRef({
    required this.id,
    required this.name,
    required this.slug,
  });
  
  factory CategoryRef.fromJson(Map<String, Object?> json) => _$CategoryRefFromJson(json);
  
  final int id;
  final String name;
  final String slug;

  Map<String, Object?> toJson() => _$CategoryRefToJson(this);
}
