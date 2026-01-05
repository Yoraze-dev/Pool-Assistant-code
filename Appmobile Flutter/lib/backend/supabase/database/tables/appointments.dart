import '../database.dart';

class AppointmentsTable extends SupabaseTable<AppointmentsRow> {
  @override
  String get tableName => 'appointments';

  @override
  AppointmentsRow createRow(Map<String, dynamic> data) => AppointmentsRow(data);
}

class AppointmentsRow extends SupabaseDataRow {
  AppointmentsRow(Map<String, dynamic> data) : super(data);

  @override
  SupabaseTable get table => AppointmentsTable();

  String get id => getField<String>('id')!;
  set id(String value) => setField<String>('id', value);

  String get poolId => getField<String>('pool_id')!;
  set poolId(String value) => setField<String>('pool_id', value);

  String get proId => getField<String>('pro_id')!;
  set proId(String value) => setField<String>('pro_id', value);

  DateTime get scheduledStart => getField<DateTime>('scheduled_start')!;
  set scheduledStart(DateTime value) =>
      setField<DateTime>('scheduled_start', value);

  DateTime? get scheduledEnd => getField<DateTime>('scheduled_end');
  set scheduledEnd(DateTime? value) =>
      setField<DateTime>('scheduled_end', value);

  String get status => getField<String>('status')!;
  set status(String value) => setField<String>('status', value);

  String? get notes => getField<String>('notes');
  set notes(String? value) => setField<String>('notes', value);

  DateTime? get createdAt => getField<DateTime>('created_at');
  set createdAt(DateTime? value) => setField<DateTime>('created_at', value);

  DateTime? get updatedAt => getField<DateTime>('updated_at');
  set updatedAt(DateTime? value) => setField<DateTime>('updated_at', value);
}
