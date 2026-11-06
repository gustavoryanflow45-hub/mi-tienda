// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'product_variants.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProductVariants _$ProductVariantsFromJson(Map<String, dynamic> json) =>
    ProductVariants(
      sizeLabel: json['size_label'] as String,
      sizes: (json['sizes'] as List<dynamic>).map((e) => e as String).toList(),
      colors: (json['colors'] as List<dynamic>)
          .map((e) => Color.fromJson(e as Map<String, dynamic>))
          .toList(),
      combinations: (json['combinations'] as List<dynamic>)
          .map((e) => Combination.fromJson(e as Map<String, dynamic>))
          .toList(),
    );

Map<String, dynamic> _$ProductVariantsToJson(ProductVariants instance) =>
    <String, dynamic>{
      'size_label': instance.sizeLabel,
      'sizes': instance.sizes,
      'colors': instance.colors,
      'combinations': instance.combinations,
    };
