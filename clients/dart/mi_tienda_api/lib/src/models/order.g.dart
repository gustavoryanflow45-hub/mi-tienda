// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'order.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

Order _$OrderFromJson(Map<String, dynamic> json) => Order(
  id: (json['id'] as num).toInt(),
  code: json['code'] as String,
  paymentStatus: OrderPaymentStatus.fromJson(json['payment_status'] as String),
  paymentStatusLabel: json['payment_status_label'] as String,
  paymentGateway: json['payment_gateway'] == null
      ? null
      : OrderPaymentGateway.fromJson(json['payment_gateway'] as String?),
  deliveryStatus: DeliveryStatus.fromJson(json['delivery_status'] as String),
  deliveryStatusLabel: json['delivery_status_label'] as String,
  currency: json['currency'] as String,
  totals: Totals.fromJson(json['totals'] as Map<String, dynamic>),
  shippingAddress: ShippingAddress.fromJson(
    json['shipping_address'] as Map<String, dynamic>,
  ),
  items: (json['items'] as List<dynamic>)
      .map((e) => OrderItem.fromJson(e as Map<String, dynamic>))
      .toList(),
  tracking: Tracking.fromJson(json['tracking'] as Map<String, dynamic>),
  createdAt: json['created_at'] == null
      ? null
      : DateTime.parse(json['created_at'] as String),
  paidAt: json['paid_at'] == null
      ? null
      : DateTime.parse(json['paid_at'] as String),
);

Map<String, dynamic> _$OrderToJson(Order instance) => <String, dynamic>{
  'id': instance.id,
  'code': instance.code,
  'payment_status': _$OrderPaymentStatusEnumMap[instance.paymentStatus]!,
  'payment_status_label': instance.paymentStatusLabel,
  'payment_gateway': _$OrderPaymentGatewayEnumMap[instance.paymentGateway],
  'delivery_status': _$DeliveryStatusEnumMap[instance.deliveryStatus]!,
  'delivery_status_label': instance.deliveryStatusLabel,
  'currency': instance.currency,
  'totals': instance.totals,
  'shipping_address': instance.shippingAddress,
  'items': instance.items,
  'tracking': instance.tracking,
  'created_at': instance.createdAt?.toIso8601String(),
  'paid_at': instance.paidAt?.toIso8601String(),
};

const _$OrderPaymentStatusEnumMap = {
  OrderPaymentStatus.paid: 'paid',
  OrderPaymentStatus.unpaid: 'unpaid',
  OrderPaymentStatus.partial: 'partial',
  OrderPaymentStatus.$unknown: r'$unknown',
};

const _$OrderPaymentGatewayEnumMap = {
  OrderPaymentGateway.stripe: 'stripe',
  OrderPaymentGateway.payphone: 'payphone',
  OrderPaymentGateway.kushki: 'kushki',
  OrderPaymentGateway.valueNull: null,
  OrderPaymentGateway.$unknown: r'$unknown',
};

const _$DeliveryStatusEnumMap = {
  DeliveryStatus.pending: 'pending',
  DeliveryStatus.confirmed: 'confirmed',
  DeliveryStatus.warehouse: 'warehouse',
  DeliveryStatus.onTheWay: 'on_the_way',
  DeliveryStatus.delivered: 'delivered',
  DeliveryStatus.$unknown: r'$unknown',
};
