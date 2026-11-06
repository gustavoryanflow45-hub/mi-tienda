// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

part 'pagination_meta.g.dart';

@JsonSerializable()
class PaginationMeta {
  const PaginationMeta({
    required this.currentPage,
    required this.lastPage,
    required this.perPage,
    required this.total,
    this.from,
    this.path,
    this.to,
  });
  
  factory PaginationMeta.fromJson(Map<String, Object?> json) => _$PaginationMetaFromJson(json);
  
  @JsonKey(name: 'current_page')
  final int currentPage;
  final int? from;
  @JsonKey(name: 'last_page')
  final int lastPage;
  final String? path;
  @JsonKey(name: 'per_page')
  final int perPage;
  final int? to;
  final int total;

  Map<String, Object?> toJson() => _$PaginationMetaToJson(this);
}
