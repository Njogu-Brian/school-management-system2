export type CommunicationStackParamList = {
  CommunicationDashboard: undefined;
  AnnouncementsList: undefined;
  AnnouncementDetail: { announcementId: number };
  AnnouncementForm: { announcementId?: number } | undefined;
  SmsCompose: undefined;
  SmsHistory: undefined;
  SmsLogDetail: { logId: number };
  TemplatesList: undefined;
  TemplateDetail: { templateId: number };
  TemplateForm: { templateId?: number } | undefined;
};
