// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'category_ref.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CategoryRef _$CategoryRefFromJson(Map<String, dynamic> json) => CategoryRef(
  id: (json['id'] as num).toInt(),
  name: json['name'] as String,
  slug: json['slug'] as String,
);

Map<String, dynamic> _$CategoryRefToJson(CategoryRef instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'slug': instance.slug,
    };
