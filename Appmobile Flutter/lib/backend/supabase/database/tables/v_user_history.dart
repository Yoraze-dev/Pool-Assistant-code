import '../database.dart';

class VUserHistoryTable extends SupabaseTable<VUserHistoryRow> {
  @override
  String get tableName => 'v_user_history';

  @override
  VUserHistoryRow createRow(Map<String, dynamic> data) => VUserHistoryRow(data);
}

class VUserHistoryRow extends SupabaseDataRow {
  VUserHistoryRow(Map<String, dynamic> data) : super(data);

  @override
  SupabaseTable get table => VUserHistoryTable();

  String? get rowId => getField<String>('row_id');
  set rowId(String? value) => setField<String>('row_id', value);

  String? get kind => getField<String>('kind');
  set kind(String? value) => setField<String>('kind', value);

  DateTime? get eventAt => getField<DateTime>('event_at');
  set eventAt(DateTime? value) => setField<DateTime>('event_at', value);

  String? get status => getField<String>('status');
  set status(String? value) => setField<String>('status', value);

  String? get proName => getField<String>('pro_name');
  set proName(String? value) => setField<String>('pro_name', value);

  String? get accountId => getField<String>('account_id');
  set accountId(String? value) => setField<String>('account_id', value);

  String? get proId => getField<String>('pro_id');
  set proId(String? value) => setField<String>('pro_id', value);

  DateTime? get scheduledStart => getField<DateTime>('scheduled_start');
  set scheduledStart(DateTime? value) =>
      setField<DateTime>('scheduled_start', value);

  DateTime? get scheduledEnd => getField<DateTime>('scheduled_end');
  set scheduledEnd(DateTime? value) =>
      setField<DateTime>('scheduled_end', value);
}
