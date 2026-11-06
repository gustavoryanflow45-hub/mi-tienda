// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'option_category.g.dart';

@JsonSerializable()
class OptionCategory {
  const OptionCategory({
    required this.id,
    required this.name,
    required this.parentId,
    required this.variantType,
  });
  
  factory OptionCategory.fromJson(Map<String, Object?> json) => _$OptionCategoryFromJson(json);
  
  final int id;
  final String name;
  @JsonKey(name: 'parent_id')
  final int? parentId;
  @JsonKey(name: 'variant_type')
  final String variantType;

  Map<String, Object?> toJson() => _$OptionCategoryToJson(this);
}
