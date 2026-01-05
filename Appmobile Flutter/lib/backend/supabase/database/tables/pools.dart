import '../database.dart';

class PoolsTable extends SupabaseTable<PoolsRow> {
  @override
  String get tableName => 'pools';

  @override
  PoolsRow createRow(Map<String, dynamic> data) => PoolsRow(data);
}

class PoolsRow extends SupabaseDataRow {
  PoolsRow(Map<String, dynamic> data) : super(data);

  @override
  SupabaseTable get table => PoolsTable();

  String get id => getField<String>('id')!;
  set id(String value) => setField<String>('id', value);

  String get accountId => getField<String>('account_id')!;
  set accountId(String value) => setField<String>('account_id', value);

  String? get name => getField<String>('name');
  set name(String? value) => setField<String>('name', value);

  double? get volumeLiters => getField<double>('volume_liters');
  set volumeLiters(double? value) => setField<double>('volume_liters', value);

  String? get shape => getField<String>('shape');
  set shape(String? value) => setField<String>('shape', value);

  String? get sanitizer => getField<String>('sanitizer');
  set sanitizer(String? value) => setField<String>('sanitizer', value);

  bool? get isIndoor => getField<bool>('is_indoor');
  set isIndoor(bool? value) => setField<bool>('is_indoor', value);

  String? get locationCity => getField<String>('location_city');
  set locationCity(String? value) => setField<String>('location_city', value);

  String? get locationZip => getField<String>('location_zip');
  set locationZip(String? value) => setField<String>('location_zip', value);

  String? get locationCountry => getField<String>('location_country');
  set locationCountry(String? value) =>
      setField<String>('location_country', value);

  DateTime? get createdAt => getField<DateTime>('created_at');
  set createdAt(DateTime? value) => setField<DateTime>('created_at', value);

  DateTime? get updatedAt => getField<DateTime>('updated_at');
  set updatedAt(DateTime? value) => setField<DateTime>('updated_at', value);
}
