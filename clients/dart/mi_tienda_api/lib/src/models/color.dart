// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'color.g.dart';

@JsonSerializable()
class Color {
  const Color({
    required this.key,
    required this.label,
    required this.hex,
  });
  
  factory Color.fromJson(Map<String, Object?> json) => _$ColorFromJson(json);
  
  final String key;
  final String label;
  final String? hex;

  Map<String, Object?> toJson() => _$ColorToJson(this);
}
