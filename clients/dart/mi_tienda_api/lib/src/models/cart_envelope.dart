// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'cart_data.dart';

part 'cart_envelope.g.dart';

@JsonSerializable()
class CartEnvelope {
  const CartEnvelope({
    required this.data,
    this.message,
  });
  
  factory CartEnvelope.fromJson(Map<String, Object?> json) => _$CartEnvelopeFromJson(json);
  
  final CartData data;
  final String? message;

  Map<String, Object?> toJson() => _$CartEnvelopeToJson(this);
}
