// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'seller_order.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SellerOrder _$SellerOrderFromJson(Map<String, dynamic> json) => SellerOrder(
  id: (json['id'] as num).toInt(),
  code: json['code'] as String,
  paymentStatus: SellerOrderPaymentStatus.fromJson(
    json['payment_status'] as String,
  ),
  paymentStatusLabel: json['payment_status_label'] as String,
  deliveryStatus: DeliveryStatus.fromJson(json['delivery_status'] as String),
  deliveryStatusLabel: json['delivery_status_label'] as String,
  actions: SellerOrderActions.fromJson(json['actions'] as Map<String, dynamic>),
  currency: json['currency'] as String,
  sellerTotal: json['seller_total'] as num,
  items: (json['items'] as List<dynamic>)
      .map((e) => OrderItem.fromJson(e as Map<String, dynamic>))
      .toList(),
  buyer: Buyer.fromJson(json['buyer'] as Map<String, dynamic>),
  shippingAddress: ShippingAddress.fromJson(
    json['shipping_address'] as Map<String, dynamic>,
  ),
  tracking: Tracking.fromJson(json['tracking'] as Map<String, dynamic>),
  createdAt: json['created_at'] == null
      ? null
      : DateTime.parse(json['created_at'] as String),
  paidAt: json['paid_at'] == null
      ? null
      : DateTime.parse(json['paid_at'] as String),
);

Map<String, dynamic> _$SellerOrderToJson(
  SellerOrder instance,
) => <String, dynamic>{
  'id': instance.id,
  'code': instance.code,
  'payment_status': _$SellerOrderPaymentStatusEnumMap[instance.paymentStatus]!,
  'payment_status_label': instance.paymentStatusLabel,
  'delivery_status': _$DeliveryStatusEnumMap[instance.deliveryStatus]!,
  'delivery_status_label': instance.deliveryStatusLabel,
  'actions': instance.actions,
  'currency': instance.currency,
  'seller_total': instance.sellerTotal,
  'items': instance.items,
  'buyer': instance.buyer,
  'shipping_address': instance.shippingAddress,
  'tracking': instance.tracking,
  'created_at': instance.createdAt?.toIso8601String(),
  'paid_at': instance.paidAt?.toIso8601String(),
};

const _$SellerOrderPaymentStatusEnumMap = {
  SellerOrderPaymentStatus.paid: 'paid',
  SellerOrderPaymentStatus.$unknown: r'$unknown',
};

const _$DeliveryStatusEnumMap = {
  DeliveryStatus.pending: 'pending',
  DeliveryStatus.confirmed: 'confirmed',
  DeliveryStatus.warehouse: 'warehouse',
  DeliveryStatus.onTheWay: 'on_the_way',
  DeliveryStatus.delivered: 'delivered',
  DeliveryStatus.$unknown: r'$unknown',
};
