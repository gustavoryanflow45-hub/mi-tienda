// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'seller_dashboard.dart';

part 'get_seller_dashboard_response.g.dart';

@JsonSerializable()
class GetSellerDashboardResponse {
  const GetSellerDashboardResponse({
    required this.data,
  });
  
  factory GetSellerDashboardResponse.fromJson(Map<String, Object?> json) => _$GetSellerDashboardResponseFromJson(json);
  
  final SellerDashboard data;

  Map<String, Object?> toJson() => _$GetSellerDashboardResponseToJson(this);
}
