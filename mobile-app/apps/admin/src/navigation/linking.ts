import type { LinkingOptions } from '@react-navigation/native';
import type { Student360TabId } from '@erp/ui';
import type { DrawerParamList } from './types';

const STUDENT_TABS: Student360TabId[] = [
  'overview',
  'attendance',
  'academics',
  'fees',
  'family',
  'health',
  'transport',
  'requirements',
  'documents',
];

/**
 * Deep-link configuration — nested stacks for Student 360, Finance, Academics, and Approvals.
 */
export const linking: LinkingOptions<DrawerParamList> = {
  prefixes: ['schoolerpadmin://', 'https://admin.schoolerp.app'],
  config: {
    screens: {
      Workspace: {
        screens: {
          Dashboard: {
            path: 'dashboard',
            screens: {
              DashboardHome: '',
              ApprovalCenter: 'approvals',
              ApprovalDetail: 'approvals/:id',
            },
          },
          Students: {
            path: 'students',
            screens: {
              StudentRegistry: '',
              StudentDetail: {
                path: ':studentId/:tab?',
                parse: {
                  studentId: Number,
                  tab: (value: string) =>
                    STUDENT_TABS.includes(value as Student360TabId)
                      ? (value as Student360TabId)
                      : 'overview',
                },
                stringify: {
                  tab: (value: Student360TabId) => (value === 'overview' ? '' : value),
                },
              },
              ReportCardDetail: 'report-cards/:reportCardId',
            },
          },
          Finance: {
            path: 'finance',
            screens: {
              FinanceDashboard: '',
              BillingList: 'billing',
              InvoiceDetail: 'invoices/:invoiceId',
              CollectionsList: 'collections',
              PaymentDetail: 'payments/:paymentId',
              Statements: 'statements',
              ReconciliationList: 'reconciliation',
              TransactionDetail: 'transactions/:transactionId',
            },
          },
          People: {
            path: 'people',
            screens: {
              StaffRegistry: '',
              StaffDetail: {
                path: ':staffId',
                parse: { staffId: Number },
              },
            },
          },
        },
      },
      Approvals: {
        path: 'approvals',
        screens: {
          ApprovalsHome: '',
          ApprovalDetail: ':id',
        },
      },
      Admissions: {
        path: 'admissions',
        screens: {
          AdmissionsWorkspace: '',
          ApplicationDetail: {
            path: ':applicationId',
            parse: { applicationId: Number },
          },
        },
      },
      Academics: {
        path: 'academics',
        screens: {
          AcademicsDashboard: '',
          Assessments: 'assessments',
          AssessmentHistory: 'assessments/student/:studentId',
          AssessmentDetail: 'assessments/detail',
          ExamsList: 'exams',
          ExamDetail: 'exams/:examId',
          Marks: 'marks',
          MarksMatrix: 'marks/matrix',
          ReportCards: 'report-cards',
          ReportCardHistory: 'report-cards/student/:studentId',
          ReportCardDetail: 'report-cards/detail/:reportCardId',
          Moderation: 'moderation',
          LessonPlanReview: 'lesson-plans/:lessonPlanId',
        },
      },
      Operations: {
        path: 'operations',
        screens: {
          OperationsDashboard: '',
          TripsList: 'transport',
          TripDetail: {
            path: 'transport/:tripId',
            parse: { tripId: Number },
          },
        },
      },
      Communication: {
        path: 'communication',
        screens: {
          CommunicationDashboard: '',
          AnnouncementsList: 'announcements',
        },
      },
      Reports: {
        path: 'reports',
        screens: {
          ReportsHub: '',
        },
      },
      Settings: 'settings',
    },
  },
};
