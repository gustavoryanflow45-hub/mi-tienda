// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'error_code.dart';

part 'error.g.dart';

@JsonSerializable()
class Error {
  const Error({
    required this.message,
    required this.code,
  });
  
  factory Error.fromJson(Map<String, Object?> json) => _$ErrorFromJson(json);
  
  /// Traducido; para mostrar.
  final String message;
  final ErrorCode code;

  Map<String, Object?> toJson() => _$ErrorToJson(this);
}
