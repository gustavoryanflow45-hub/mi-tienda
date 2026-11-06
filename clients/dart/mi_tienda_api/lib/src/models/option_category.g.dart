// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'option_category.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

OptionCategory _$OptionCategoryFromJson(Map<String, dynamic> json) =>
    OptionCategory(
      id: (json['id'] as num).toInt(),
      name: json['name'] as String,
      parentId: (json['parent_id'] as num?)?.toInt(),
      variantType: json['variant_type'] as String,
    );

Map<String, dynamic> _$OptionCategoryToJson(OptionCategory instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'parent_id': instance.parentId,
      'variant_type': instance.variantType,
    };
