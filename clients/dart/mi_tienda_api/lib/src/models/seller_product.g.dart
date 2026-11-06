// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'seller_product.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SellerProduct _$SellerProductFromJson(Map<String, dynamic> json) =>
    SellerProduct(
      id: (json['id'] as num).toInt(),
      slug: json['slug'] as String,
      name: json['name'] as String,
      category: json['category'] == null
          ? null
          : NamedRef.fromJson(json['category'] as Map<String, dynamic>),
      brandId: (json['brand_id'] as num?)?.toInt(),
      unitPrice: json['unit_price'] as num,
      purchasePrice: json['purchase_price'] as num?,
      discount: json['discount'] as num,
      discountType: SellerProductDiscountType.fromJson(
        json['discount_type'] as String,
      ),
      finalPrice: json['final_price'] as num,
      unit: json['unit'] as String?,
      variantType: json['variant_type'] as String?,
      sizeLabel: json['size_label'] as String?,
      shortDescription: json['short_description'] as String?,
      description: json['description'] as String?,
      minQty: (json['min_qty'] as num).toInt(),
      lowStockQty: (json['low_stock_qty'] as num).toInt(),
      shippingCost: json['shipping_cost'] as num,
      published: json['published'] as bool,
      approved: json['approved'] as bool,
      onSale: json['on_sale'] as bool,
      thumbnailUrl: json['thumbnail_url'] as String,
      photos: (json['photos'] as List<dynamic>)
          .map((e) => e as String)
          .toList(),
      stocks: (json['stocks'] as List<dynamic>)
          .map((e) => SellerStock.fromJson(e as Map<String, dynamic>))
          .toList(),
      totalQty: (json['total_qty'] as num).toInt(),
      createdAt: json['created_at'] == null
          ? null
          : DateTime.parse(json['created_at'] as String),
    );

Map<String, dynamic> _$SellerProductToJson(
  SellerProduct instance,
) => <String, dynamic>{
  'id': instance.id,
  'slug': instance.slug,
  'name': instance.name,
  'category': instance.category,
  'brand_id': instance.brandId,
  'unit_price': instance.unitPrice,
  'purchase_price': instance.purchasePrice,
  'discount': instance.discount,
  'discount_type': _$SellerProductDiscountTypeEnumMap[instance.discountType]!,
  'final_price': instance.finalPrice,
  'unit': instance.unit,
  'variant_type': instance.variantType,
  'size_label': instance.sizeLabel,
  'short_description': instance.shortDescription,
  'description': instance.description,
  'min_qty': instance.minQty,
  'low_stock_qty': instance.lowStockQty,
  'shipping_cost': instance.shippingCost,
  'published': instance.published,
  'approved': instance.approved,
  'on_sale': instance.onSale,
  'thumbnail_url': instance.thumbnailUrl,
  'photos': instance.photos,
  'stocks': instance.stocks,
  'total_qty': instance.totalQty,
  'created_at': instance.createdAt?.toIso8601String(),
};

const _$SellerProductDiscountTypeEnumMap = {
  SellerProductDiscountType.percent: 'percent',
  SellerProductDiscountType.amount: 'amount',
  SellerProductDiscountType.$unknown: r'$unknown',
};
