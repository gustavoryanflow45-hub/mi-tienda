// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'seller_product_options.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SellerProductOptions _$SellerProductOptionsFromJson(
  Map<String, dynamic> json,
) => SellerProductOptions(
  categories: (json['categories'] as List<dynamic>)
      .map((e) => OptionCategory.fromJson(e as Map<String, dynamic>))
      .toList(),
  brands: (json['brands'] as List<dynamic>)
      .map((e) => NamedRef.fromJson(e as Map<String, dynamic>))
      .toList(),
  variantTypes: (json['variant_types'] as List<dynamic>)
      .map((e) => VariantType.fromJson(e as Map<String, dynamic>))
      .toList(),
  colors: (json['colors'] as List<dynamic>)
      .map((e) => Color.fromJson(e as Map<String, dynamic>))
      .toList(),
  maxUploadKb: (json['max_upload_kb'] as num).toInt(),
  maxPhotos: (json['max_photos'] as num).toInt(),
);

Map<String, dynamic> _$SellerProductOptionsToJson(
  SellerProductOptions instance,
) => <String, dynamic>{
  'categories': instance.categories,
  'brands': instance.brands,
  'variant_types': instance.variantTypes,
  'colors': instance.colors,
  'max_upload_kb': instance.maxUploadKb,
  'max_photos': instance.maxPhotos,
};
