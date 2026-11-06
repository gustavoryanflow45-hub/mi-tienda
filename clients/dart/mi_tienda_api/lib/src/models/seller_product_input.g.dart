// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'seller_product_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SellerProductInput _$SellerProductInputFromJson(Map<String, dynamic> json) =>
    SellerProductInput(
      name: json['name'] as String?,
      categoryId: (json['category_id'] as num?)?.toInt(),
      brandId: (json['brand_id'] as num?)?.toInt(),
      unitPrice: json['unit_price'] as num?,
      purchasePrice: json['purchase_price'] as num?,
      discount: json['discount'] as num?,
      discountType: json['discount_type'] == null
          ? null
          : SellerProductInputDiscountType.fromJson(
              json['discount_type'] as String,
            ),
      unit: json['unit'] as String?,
      variantType: json['variant_type'] as String?,
      shortDescription: json['short_description'] as String?,
      description: json['description'] as String?,
      minQty: (json['min_qty'] as num?)?.toInt(),
      lowStockQty: (json['low_stock_qty'] as num?)?.toInt(),
      shippingCost: json['shipping_cost'] as num?,
      published: json['published'] as bool?,
      featured: json['featured'] as bool?,
      todaysDeal: json['todays_deal'] as bool?,
      stocks: (json['stocks'] as List<dynamic>?)
          ?.map((e) => StockInput.fromJson(e as Map<String, dynamic>))
          .toList(),
    );

Map<String, dynamic> _$SellerProductInputToJson(SellerProductInput instance) =>
    <String, dynamic>{
      'name': instance.name,
      'category_id': instance.categoryId,
      'brand_id': instance.brandId,
      'unit_price': instance.unitPrice,
      'purchase_price': instance.purchasePrice,
      'discount': instance.discount,
      'discount_type':
          _$SellerProductInputDiscountTypeEnumMap[instance.discountType],
      'unit': instance.unit,
      'variant_type': instance.variantType,
      'short_description': instance.shortDescription,
      'description': instance.description,
      'min_qty': instance.minQty,
      'low_stock_qty': instance.lowStockQty,
      'shipping_cost': instance.shippingCost,
      'published': instance.published,
      'featured': instance.featured,
      'todays_deal': instance.todaysDeal,
      'stocks': instance.stocks,
    };

const _$SellerProductInputDiscountTypeEnumMap = {
  SellerProductInputDiscountType.percent: 'percent',
  SellerProductInputDiscountType.amount: 'amount',
  SellerProductInputDiscountType.$unknown: r'$unknown',
};
