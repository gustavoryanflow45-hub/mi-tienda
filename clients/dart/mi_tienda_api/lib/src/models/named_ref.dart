// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'named_ref.g.dart';

@JsonSerializable()
class NamedRef {
  const NamedRef({
    required this.id,
    required this.name,
  });
  
  factory NamedRef.fromJson(Map<String, Object?> json) => _$NamedRefFromJson(json);
  
  final int id;
  final String name;

  Map<String, Object?> toJson() => _$NamedRefToJson(this);
}
