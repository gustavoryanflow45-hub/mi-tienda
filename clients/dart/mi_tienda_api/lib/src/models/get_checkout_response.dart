// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'checkout_summary.dart';

part 'get_checkout_response.g.dart';

@JsonSerializable()
class GetCheckoutResponse {
  const GetCheckoutResponse({
    required this.data,
  });
  
  factory GetCheckoutResponse.fromJson(Map<String, Object?> json) => _$GetCheckoutResponseFromJson(json);
  
  final CheckoutSummary data;

  Map<String, Object?> toJson() => _$GetCheckoutResponseToJson(this);
}
