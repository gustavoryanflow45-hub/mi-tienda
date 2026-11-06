// coverage:ignore-file
// GENERATED CODE - DO NOT MODIFY BY HAND
// ignore_for_file: type=lint, unused_import, invalid_annotation_target, unnecessary_import

import 'package:json_annotation/json_annotation.dart';

import 'pagination_links.dart';
import 'pagination_meta.dart';

part 'paginated.g.dart';

@JsonSerializable()
class Paginated {
  const Paginated({
    required this.data,
    required this.links,
    required this.meta,
  });
  
  factory Paginated.fromJson(Map<String, Object?> json) => _$PaginatedFromJson(json);
  
  final List<dynamic> data;
  final PaginationLinks links;
  final PaginationMeta meta;

  Map<String, Object?> toJson() => _$PaginatedToJson(this);
}
