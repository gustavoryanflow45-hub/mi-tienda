// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'cart_product.g.dart';

@JsonSerializable()
class CartProduct {
  const CartProduct({
    required this.id,
    required this.slug,
    required this.name,
    required this.thumbnailUrl,
    required this.onSale,
  });
  
  factory CartProduct.fromJson(Map<String, Object?> json) => _$CartProductFromJson(json);
  
  final int id;
  final String slug;
  final String name;
  @JsonKey(name: 'thumbnail_url')
  final String thumbnailUrl;

  /// false = despublicado o vetado tras añadirlo.
  @JsonKey(name: 'on_sale')
  final bool onSale;

  Map<String, Object?> toJson() => _$CartProductToJson(this);
}
