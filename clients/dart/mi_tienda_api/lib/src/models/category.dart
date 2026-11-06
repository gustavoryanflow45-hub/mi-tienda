// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'category.dart';

part 'category.g.dart';

@JsonSerializable()
class Category {
  const Category({
    required this.id,
    required this.name,
    required this.slug,
    required this.parentId,
    required this.iconUrl,
    required this.bannerUrl,
    this.children,
    this.parent,
  });
  
  factory Category.fromJson(Map<String, Object?> json) => _$CategoryFromJson(json);
  
  final int id;
  final String name;
  final String slug;
  @JsonKey(name: 'parent_id')
  final int? parentId;
  @JsonKey(name: 'icon_url')
  final String? iconUrl;
  @JsonKey(name: 'banner_url')
  final String? bannerUrl;

  /// Subcategorías activas (en el árbol y en `/categories/{slug}`).
  final List<Category>? children;

  /// Solo en `/categories/{slug}`; null en una raíz.
  final Category? parent;

  Map<String, Object?> toJson() => _$CategoryToJson(this);
}
