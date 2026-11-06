// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'category_ref.dart';
import 'discount.dart';
import 'named_ref.dart';
import 'product_card.dart';
import 'product_variants.dart';

part 'product_detail.g.dart';

@JsonSerializable()
class ProductDetail {
  const ProductDetail({
    required this.id,
    required this.slug,
    required this.name,
    required this.thumbnailUrl,
    required this.price,
    required this.originalPrice,
    required this.discount,
    required this.unit,
    required this.rating,
    required this.inStock,
    required this.hasVariants,
    required this.category,
    required this.brand,
    required this.photos,
    required this.shortDescription,
    required this.description,
    required this.minQty,
    required this.shippingCost,
    required this.shop,
    required this.variants,
    this.related,
  });
  
  factory ProductDetail.fromJson(Map<String, Object?> json) => _$ProductDetailFromJson(json);
  
  final int id;
  final String slug;
  final String name;
  @JsonKey(name: 'thumbnail_url')
  final String thumbnailUrl;

  /// Lo que se cobra (con descuento).
  final num price;
  @JsonKey(name: 'original_price')
  final num originalPrice;
  final Discount? discount;
  final String? unit;
  final num rating;
  @JsonKey(name: 'in_stock')
  final bool inStock;

  /// Hay que elegir combinación antes de añadir.
  @JsonKey(name: 'has_variants')
  final bool hasVariants;
  final CategoryRef? category;
  final NamedRef? brand;
  final List<String> photos;
  @JsonKey(name: 'short_description')
  final String? shortDescription;

  /// Texto plano.
  final String? description;
  @JsonKey(name: 'min_qty')
  final int minQty;
  @JsonKey(name: 'shipping_cost')
  final num shippingCost;
  final NamedRef? shop;
  final ProductVariants variants;
  final List<ProductCard>? related;

  Map<String, Object?> toJson() => _$ProductDetailToJson(this);
}
