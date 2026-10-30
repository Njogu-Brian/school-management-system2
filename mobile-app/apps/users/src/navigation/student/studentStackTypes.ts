export type StudentStackParamList = {
  StudentHome: undefined;
  StudentHomeworkMain: undefined;
  StudentResultsMain: undefined;
  StudentMoreMenu: undefined;
  Announcements: undefined;
  Notifications: undefined;
  Settings: undefined;
  MyProfile: undefined;
  ConcernsList: undefined;
  ConcernDetail: { concernId: number };
  RaiseConcern: { studentId?: number } | undefined;
};
