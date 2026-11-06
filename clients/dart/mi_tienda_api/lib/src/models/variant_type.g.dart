// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'variant_type.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VariantType _$VariantTypeFromJson(Map<String, dynamic> json) => VariantType(
  key: json['key'] as String,
  label: json['label'] as String,
  sizeLabel: json['size_label'] as String?,
  sizes: (json['sizes'] as List<dynamic>).map((e) => e as String).toList(),
);

Map<String, dynamic> _$VariantTypeToJson(VariantType instance) =>
    <String, dynamic>{
      'key': instance.key,
      'label': instance.label,
      'size_label': instance.sizeLabel,
      'sizes': instance.sizes,
    };
