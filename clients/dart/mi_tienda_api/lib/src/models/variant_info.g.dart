// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'variant_info.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

VariantInfo _$VariantInfoFromJson(Map<String, dynamic> json) => VariantInfo(
  text: json['text'] as String,
  size: json['size'] as String?,
  sizeLabel: json['size_label'] as String?,
  color: json['color'] as String?,
  colorLabel: json['color_label'] as String?,
  colorHex: json['color_hex'] as String?,
);

Map<String, dynamic> _$VariantInfoToJson(VariantInfo instance) =>
    <String, dynamic>{
      'text': instance.text,
      'size': instance.size,
      'size_label': instance.sizeLabel,
      'color': instance.color,
      'color_label': instance.colorLabel,
      'color_hex': instance.colorHex,
    };
