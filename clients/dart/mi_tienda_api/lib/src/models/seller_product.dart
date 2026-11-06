// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'named_ref.dart';
import 'nullable_date_time.dart';
import 'seller_product_discount_type.dart';
import 'seller_stock.dart';

part 'seller_product.g.dart';

@JsonSerializable()
class SellerProduct {
  const SellerProduct({
    required this.id,
    required this.slug,
    required this.name,
    required this.category,
    required this.brandId,
    required this.unitPrice,
    required this.purchasePrice,
    required this.discount,
    required this.discountType,
    required this.finalPrice,
    required this.unit,
    required this.variantType,
    required this.sizeLabel,
    required this.shortDescription,
    required this.description,
    required this.minQty,
    required this.lowStockQty,
    required this.shippingCost,
    required this.published,
    required this.approved,
    required this.onSale,
    required this.thumbnailUrl,
    required this.photos,
    required this.stocks,
    required this.totalQty,
    required this.createdAt,
  });
  
  factory SellerProduct.fromJson(Map<String, Object?> json) => _$SellerProductFromJson(json);
  
  final int id;
  final String slug;
  final String name;
  final NamedRef? category;
  @JsonKey(name: 'brand_id')
  final int? brandId;
  @JsonKey(name: 'unit_price')
  final num unitPrice;
  @JsonKey(name: 'purchase_price')
  final num? purchasePrice;
  final num discount;
  @JsonKey(name: 'discount_type')
  final SellerProductDiscountType discountType;
  @JsonKey(name: 'final_price')
  final num finalPrice;
  final String? unit;

  /// null = el de la categoría.
  @JsonKey(name: 'variant_type')
  final String? variantType;
  @JsonKey(name: 'size_label')
  final String? sizeLabel;
  @JsonKey(name: 'short_description')
  final String? shortDescription;

  /// HTML del vendedor.
  final String? description;
  @JsonKey(name: 'min_qty')
  final int minQty;
  @JsonKey(name: 'low_stock_qty')
  final int lowStockQty;
  @JsonKey(name: 'shipping_cost')
  final num shippingCost;

  /// Decisión del vendedor.
  final bool published;

  /// false = desactivado por moderación.
  final bool approved;
  @JsonKey(name: 'on_sale')
  final bool onSale;
  @JsonKey(name: 'thumbnail_url')
  final String thumbnailUrl;
  final List<String> photos;
  final List<SellerStock> stocks;
  @JsonKey(name: 'total_qty')
  final int totalQty;
  @JsonKey(name: 'created_at')
  final NullableDateTime createdAt;

  Map<String, Object?> toJson() => _$SellerProductToJson(this);
}
