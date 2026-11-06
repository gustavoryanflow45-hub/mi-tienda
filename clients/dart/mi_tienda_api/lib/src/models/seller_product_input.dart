// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'seller_product_input_discount_type.dart';
import 'stock_input.dart';

part 'seller_product_input.g.dart';

/// Campos del formulario web; cualquier otro (added_by, approved…) se ignora.
@JsonSerializable()
class SellerProductInput {
  const SellerProductInput({
    this.name,
    this.categoryId,
    this.brandId,
    this.unitPrice,
    this.purchasePrice,
    this.discount,
    this.discountType,
    this.unit,
    this.variantType,
    this.shortDescription,
    this.description,
    this.minQty,
    this.lowStockQty,
    this.shippingCost,
    this.published,
    this.featured,
    this.todaysDeal,
    this.stocks,
  });
  
  factory SellerProductInput.fromJson(Map<String, Object?> json) => _$SellerProductInputFromJson(json);
  
  final String? name;
  @JsonKey(name: 'category_id')
  final int? categoryId;
  @JsonKey(name: 'brand_id')
  final int? brandId;
  @JsonKey(name: 'unit_price')
  final num? unitPrice;
  @JsonKey(name: 'purchase_price')
  final num? purchasePrice;
  final num? discount;
  @JsonKey(name: 'discount_type')
  final SellerProductInputDiscountType? discountType;
  final String? unit;

  /// Clave de `variant_types`.
  @JsonKey(name: 'variant_type')
  final String? variantType;
  @JsonKey(name: 'short_description')
  final String? shortDescription;
  final String? description;
  @JsonKey(name: 'min_qty')
  final int? minQty;
  @JsonKey(name: 'low_stock_qty')
  final int? lowStockQty;
  @JsonKey(name: 'shipping_cost')
  final num? shippingCost;
  final bool? published;
  final bool? featured;
  @JsonKey(name: 'todays_deal')
  final bool? todaysDeal;

  /// Una fila por combinación talla × color (en multipart, `stocks[0][size]`…).
  final List<StockInput>? stocks;

  Map<String, Object?> toJson() => _$SellerProductInputToJson(this);
}
