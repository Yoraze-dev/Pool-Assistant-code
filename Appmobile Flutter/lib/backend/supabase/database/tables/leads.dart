import '../database.dart';

class LeadsTable extends SupabaseTable<LeadsRow> {
  @override
  String get tableName => 'leads';

  @override
  LeadsRow createRow(Map<String, dynamic> data) => LeadsRow(data);
}

class LeadsRow extends SupabaseDataRow {
  LeadsRow(Map<String, dynamic> data) : super(data);

  @override
  SupabaseTable get table => LeadsTable();

  String get id => getField<String>('id')!;
  set id(String value) => setField<String>('id', value);

  String get accountId => getField<String>('account_id')!;
  set accountId(String value) => setField<String>('account_id', value);

  String? get poolId => getField<String>('pool_id');
  set poolId(String? value) => setField<String>('pool_id', value);

  String? get title => getField<String>('title');
  set title(String? value) => setField<String>('title', value);

  String? get description => getField<String>('description');
  set description(String? value) => setField<String>('description', value);

  String? get city => getField<String>('city');
  set city(String? value) => setField<String>('city', value);

  String? get zip => getField<String>('zip');
  set zip(String? value) => setField<String>('zip', value);

  String get status => getField<String>('status')!;
  set status(String value) => setField<String>('status', value);

  String? get createdBy => getField<String>('created_by');
  set createdBy(String? value) => setField<String>('created_by', value);

  String? get assignedTo => getField<String>('assigned_to');
  set assignedTo(String? value) => setField<String>('assigned_to', value);

  DateTime? get createdAt => getField<DateTime>('created_at');
  set createdAt(DateTime? value) => setField<DateTime>('created_at', value);

  DateTime? get updatedAt => getField<DateTime>('updated_at');
  set updatedAt(DateTime? value) => setField<DateTime>('updated_at', value);
}
