// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'seller_dashboard.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SellerDashboard _$SellerDashboardFromJson(Map<String, dynamic> json) =>
    SellerDashboard(
      shop: NamedRef.fromJson(json['shop'] as Map<String, dynamic>),
      currency: json['currency'] as String,
      products: (json['products'] as num).toInt(),
      pendingOrders: (json['pending_orders'] as num).toInt(),
      deliveredOrders: (json['delivered_orders'] as num).toInt(),
      currentCycle: SettlementCycle.fromJson(
        json['current_cycle'] as Map<String, dynamic>,
      ),
      lastSettlement: json['last_settlement'] == null
          ? null
          : LastSettlement.fromJson(
              json['last_settlement'] as Map<String, dynamic>,
            ),
      balance: json['balance'] as num,
    );

Map<String, dynamic> _$SellerDashboardToJson(SellerDashboard instance) =>
    <String, dynamic>{
      'shop': instance.shop,
      'currency': instance.currency,
      'products': instance.products,
      'pending_orders': instance.pendingOrders,
      'delivered_orders': instance.deliveredOrders,
      'current_cycle': instance.currentCycle,
      'last_settlement': instance.lastSettlement,
      'balance': instance.balance,
    };
