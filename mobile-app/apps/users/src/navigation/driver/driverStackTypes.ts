export type DriverStackParamList = {
  DriverHomeMain: undefined;
  TripDetail: { tripId: number };
  BoardingChecklist: { tripId: number };
  ActiveTrip: { tripId: number };
  DriverVehicle: undefined;
  RoutesList: undefined;
  DriverMoreMenu: undefined;
  DriverSettings: undefined;
  Notifications: undefined;
  StaffClock: undefined;
  LeaveApply: undefined;
  MyLeaveList: undefined;
  MyPayslips: undefined;
  MyAdvances: undefined;
  MyProfile: undefined;
  ConcernsList: undefined;
  RaiseConcern: { studentId?: number } | undefined;
};
