// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'last_settlement.dart';
import 'named_ref.dart';
import 'settlement_cycle.dart';

part 'seller_dashboard.g.dart';

@JsonSerializable()
class SellerDashboard {
  const SellerDashboard({
    required this.shop,
    required this.currency,
    required this.products,
    required this.pendingOrders,
    required this.deliveredOrders,
    required this.currentCycle,
    required this.lastSettlement,
    required this.balance,
  });
  
  factory SellerDashboard.fromJson(Map<String, Object?> json) => _$SellerDashboardFromJson(json);
  
  final NamedRef shop;
  final String currency;
  final int products;

  /// Pagados y sin confirmar.
  @JsonKey(name: 'pending_orders')
  final int pendingOrders;
  @JsonKey(name: 'delivered_orders')
  final int deliveredOrders;
  @JsonKey(name: 'current_cycle')
  final SettlementCycle currentCycle;
  @JsonKey(name: 'last_settlement')
  final LastSettlement? lastSettlement;
  final num balance;

  Map<String, Object?> toJson() => _$SellerDashboardToJson(this);
}
