// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'color.dart';
import 'named_ref.dart';
import 'option_category.dart';
import 'variant_type.dart';

part 'seller_product_options.g.dart';

@JsonSerializable()
class SellerProductOptions {
  const SellerProductOptions({
    required this.categories,
    required this.brands,
    required this.variantTypes,
    required this.colors,
    required this.maxUploadKb,
    required this.maxPhotos,
  });
  
  factory SellerProductOptions.fromJson(Map<String, Object?> json) => _$SellerProductOptionsFromJson(json);
  
  final List<OptionCategory> categories;
  final List<NamedRef> brands;
  @JsonKey(name: 'variant_types')
  final List<VariantType> variantTypes;
  final List<Color> colors;
  @JsonKey(name: 'max_upload_kb')
  final int maxUploadKb;
  @JsonKey(name: 'max_photos')
  final int maxPhotos;

  Map<String, Object?> toJson() => _$SellerProductOptionsToJson(this);
}
