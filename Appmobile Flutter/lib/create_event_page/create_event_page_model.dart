import '/components/new_maintenance_sheet_widget.dart';
import '/flutter_flow/flutter_flow_theme.dart';
import '/flutter_flow/flutter_flow_util.dart';
import '/flutter_flow/flutter_flow_widgets.dart';
import 'create_event_page_widget.dart' show CreateEventPageWidget;
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';

class CreateEventPageModel extends FlutterFlowModel<CreateEventPageWidget> {
  ///  State fields for stateful widgets in this page.

  // Model for NewMaintenanceSheet component.
  late NewMaintenanceSheetModel newMaintenanceSheetModel;

  @override
  void initState(BuildContext context) {
    newMaintenanceSheetModel =
        createModel(context, () => NewMaintenanceSheetModel());
  }

  @override
  void dispose() {
    newMaintenanceSheetModel.dispose();
  }
}
