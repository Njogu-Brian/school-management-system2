export type OperationsStackParamList = {
  OperationsDashboard: undefined;
  TripsList: undefined;
  TripDetail: { tripId: number; tripName?: string };
  InventoryList: undefined;
  RequisitionsList: undefined;
  RequisitionDetail: { requisitionId: number };
  VisitorsList: undefined;
  VisitorDetail: { visitorId: number };
  VisitorCheckIn: undefined;
  AssetsList: undefined;
  AssetDetail: { assetId: number };
  TeacherTransport: undefined;
  DriverTrips: undefined;
  DriverTripDetail: { tripId: number; tripName?: string };
  VehiclesList: undefined;
  VehicleForm: { vehicleId?: number };
  TripForm: { tripId?: number };
};
