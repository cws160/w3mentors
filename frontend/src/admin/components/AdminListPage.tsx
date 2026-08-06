import { useCallback, useEffect, useState, type ReactNode } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import moment from 'moment';
import { useSite } from '../../w3mentors/context/SiteContext';
import { adminApi } from '../api/adminClient';
import type { AdminModuleConfig } from '../config/adminModuleTypes';
import { useAdminAuth } from '../context/AdminAuthContext';
import { useAdminPageMeta } from '../context/AdminPageMetaContext';
import { AdminLegacyPagination } from './AdminLegacyPagination';
import { AdminStatusSwitch } from './AdminStatusSwitch';
import { AdminModuleSearchForm } from './AdminModuleSearchForm';
import { AdminTeacherRequestModals, type AdminTeacherRequestModalType } from './AdminTeacherRequestModals';
import { AdminTeacherRequestsActions } from './AdminTeacherRequestsActions';
import { AdminUserModals, openUserLogin, type AdminUserModalType } from './AdminUserModals';
import { AdminUsersActions } from './AdminUsersActions';
import { AdminUsersSearchForm } from './AdminUsersSearchForm';
import { AdminNoRecords } from './AdminNoRecords';
import { AdminWithdrawRequestsActions } from './AdminWithdrawRequestsActions';
import { AdminRatingReviewModal } from './AdminRatingReviewModal';
import { AdminGdprRequestModal } from './AdminGdprRequestModal';
import { AdminManageAdminsActions } from './AdminManageAdminsActions';
import { AdminManageAdminModals, type AdminManageAdminModalType } from './AdminManageAdminModals';
import { AdminGroupClassesActions } from './AdminGroupClassesActions';
import { AdminGroupClassModal } from './AdminGroupClassModal';
import { AdminGroupClassViewModal } from './AdminGroupClassViewModal';
import { AdminCoursesActions, openCoursePreview } from './AdminCoursesActions';
import { AdminCourseRequestsActions } from './AdminCourseRequestsActions';
import { AdminCourseEditRequestsActions } from './AdminCourseEditRequestsActions';
import {
  AdminCourseRequestModals,
  type AdminCourseRequestModalType,
} from './AdminCourseRequestModals';
import {
  AdminCourseEditRequestModals,
  type AdminCourseEditRequestModalType,
} from './AdminCourseEditRequestModals';
import {
  AdminCourseRefundRequestModals,
  type AdminCourseRefundRequestModalType,
} from './AdminCourseRefundRequestModals';
import { AdminCourseRefundRequestsActions } from './AdminCourseRefundRequestsActions';
import { AdminCoursesSearchForm } from './AdminCoursesSearchForm';
import { AdminQuestionsSearchForm } from './AdminQuestionsSearchForm';
import { AdminQuizzesSearchForm } from './AdminQuizzesSearchForm';
import { AdminQuestionsActions } from './AdminQuestionsActions';
import { AdminQuizzesActions } from './AdminQuizzesActions';
import { AdminQuestionViewModal } from './AdminQuestionViewModal';
import { AdminQuizViewModal } from './AdminQuizViewModal';
import { AdminOrdersActions, type OrderActionModule } from './AdminOrdersActions';
import { AdminSubOrderViewModal } from './AdminSubOrderViewModal';
import { AdminReportedIssuesActions } from './AdminReportedIssuesActions';
import { AdminReportedIssueViewModal } from './AdminReportedIssueViewModal';
import { AdminPreferencesActions } from './AdminPreferencesActions';
import { AdminPreferenceModal } from './AdminPreferenceModal';
import { AdminCourseViewModal } from './AdminCourseViewModal';
import { AdminForumQuestionActions } from './AdminForumQuestionActions';
import { AdminForumTagActions } from './AdminForumTagActions';
import { AdminForumReportedQuestionActions } from './AdminForumReportedQuestionActions';
import { AdminForumTagRequestActions } from './AdminForumTagRequestActions';
import { AdminForumQuestionViewModal } from './AdminForumQuestionViewModal';
import { AdminForumTagModal } from './AdminForumTagModal';
import { AdminForumReportedQuestionViewModal } from './AdminForumReportedQuestionViewModal';
import { AdminForumReportedQuestionActionModal } from './AdminForumReportedQuestionActionModal';
import { AdminForumTagRequestStatusModal } from './AdminForumTagRequestStatusModal';
import { AdminUrlRewritingActions } from './AdminUrlRewritingActions';
import { AdminUrlRewritingModal } from './AdminUrlRewritingModal';
import { AdminUrlRewritingSearchForm } from './AdminUrlRewritingSearchForm';
import { AdminContentBlockModal } from './AdminContentBlockModal';
import { AdminContentPageModal } from './AdminContentPageModal';
import { AdminCountryModal } from './AdminCountryModal';
import { AdminNavigationModal } from './AdminNavigationModal';
import { AdminPageLangDataModal } from './AdminPageLangDataModal';
import { AdminStateModal } from './AdminStateModal';
import { AdminVideoContentModal } from './AdminVideoContentModal';
import { AdminTestimonialModal } from './AdminTestimonialModal';
import { AdminLanguageLabelModal } from './AdminLanguageLabelModal';
import { AdminFaqCategoryModal } from './AdminFaqCategoryModal';
import { AdminFaqModal } from './AdminFaqModal';
import { AdminEmailTemplateModal } from './AdminEmailTemplateModal';
import { AdminAbusiveWordModal } from './AdminAbusiveWordModal';
import { AdminCertificateModal } from './AdminCertificateModal';
import { ensureAdminInnovEditor } from '../hooks/adminInnovEditorResources';
import { adminLegacyConfirms } from '../utils/adminLegacyConfirms';
import { FORUM_MODULES } from '../config/adminForumModules';
import { AdminSpriteIcon } from './AdminSpriteIcon';
import { legacyFlagUrl, userProfileImageUrl } from '../utils/adminMedia';


const questionTypeLabel = (type: unknown, lbl: (key: string, fallback: string) => string) => {
  switch (Number(type)) {
    case 1:
      return lbl('LBL_SINGLE_CHOICE', 'Single choice');
    case 2:
      return lbl('LBL_MULTIPLE_CHOICE', 'Multiple choice');
    case 3:
      return lbl('LBL_TEXT', 'Text');
    default:
      return '—';
  }
};

const quizTypeLabel = (type: unknown, lbl: (key: string, fallback: string) => string) => {
  switch (Number(type)) {
    case 1:
      return lbl('LBL_AUTO_GRADED', 'Auto graded');
    case 2:
      return lbl('LBL_NON_GRADED', 'Non graded');
    default:
      return '—';
  }
};

const quizStatusLabel = (status: unknown, lbl: (key: string, fallback: string) => string) => {
  switch (Number(status)) {
    case 1:
      return lbl('LBL_DRAFTED', 'Drafted');
    case 2:
      return lbl('LBL_PUBLISHED', 'Published');
    default:
      return '—';
  }
};

const CONTENT_BLOCK_TYPES: Record<number, { key: string; fallback: string }> = {
  1: { key: 'LBL_HOMEPAGE', fallback: 'Homepage' },
  2: { key: 'LBL_APPLY_TO_TEACH', fallback: 'Apply To Teach' },
  3: { key: 'LBL_CONTACT_US', fallback: 'Contact Us' },
  5: { key: 'LBL_AVAILABILITY', fallback: 'Availability' },
  6: { key: 'LBL_AFFILIATE_REGISTRATION', fallback: 'Affiliate Registration' },
};

const teacherRequestStatusLabel = (status: unknown, lbl: (key: string, fallback: string) => string) => {
  switch (Number(status)) {
    case 0:
      return lbl('LBL_Pending', 'Pending');
    case 1:
      return lbl('LBL_Approved', 'Approved');
    case 2:
      return lbl('LBL_Cancelled_Teacher_Req', 'Cancelled');
    default:
      return status === null || status === undefined || status === '' ? '—' : String(status);
  }
};

const withdrawRequestStatusLabel = (status: unknown, lbl: (key: string, fallback: string) => string) => {
  switch (Number(status)) {
    case 1:
      return lbl('LBL_PENDING', 'Pending');
    case 2:
      return lbl('LBL_COMPLETED', 'Completed');
    case 3:
      return lbl('LBL_DECLINED', 'Declined');
    case 4:
      return lbl('LBL_PAYOUT_SENT', 'Payout sent');
    case 5:
      return lbl('LBL_PAYOUT_FAILED', 'Payout failed');
    default:
      return status === null || status === undefined || status === '' ? '—' : String(status);
  }
};

const ratingReviewStatusLabel = (status: unknown, lbl: (key: string, fallback: string) => string) => {
  switch (Number(status)) {
    case 0:
      return lbl('STATUS_PENDING', 'Pending');
    case 1:
      return lbl('STATUS_APPROVED', 'Approved');
    case 2:
      return lbl('STATUS_DECLINED', 'Declined');
    default:
      return status === null || status === undefined || status === '' ? '—' : String(status);
  }
};

const gdprRequestStatusLabel = (status: unknown, lbl: (key: string, fallback: string) => string) => {
  switch (Number(status)) {
    case 1:
      return lbl('LBL_GDPR_PENDING', 'Pending');
    case 2:
      return lbl('LBL_GDPR_DECLINED', 'Declined');
    case 3:
      return lbl('LBL_GDPR_APPROVED', 'Approved');
    default:
      return status === null || status === undefined || status === '' ? '—' : String(status);
  }
};

const courseRequestStatusLabel = (status: unknown, lbl: (key: string, fallback: string) => string) => {
  switch (Number(status)) {
    case 0:
      return lbl('LBL_PENDING', 'Pending');
    case 1:
      return lbl('LBL_APPROVED', 'Approved');
    case 2:
      return lbl('LBL_DECLINED', 'Declined');
    default:
      return status === null || status === undefined || status === '' ? '—' : String(status);
  }
};

const courseEditRequestStatusLabel = (status: unknown, lbl: (key: string, fallback: string) => string) => {
  switch (Number(status)) {
    case 0:
      return lbl('LBL_EDIT_REQUEST_PENDING', 'Edit request pending');
    case 1:
      return lbl('LBL_EDIT_REQUEST_APPROVED', 'Edit request approved');
    case 2:
      return lbl('LBL_EDIT_REQUEST_DECLINED', 'Edit request declined');
    default:
      return status === null || status === undefined || status === '' ? '—' : String(status);
  }
};

const courseRefundRequestStatusLabel = (status: unknown, lbl: (key: string, fallback: string) => string) => {
  switch (Number(status)) {
    case 0:
      return lbl('LBL_REFUND_PENDING', 'Refund pending');
    case 1:
      return lbl('LBL_REFUND_APPROVED', 'Refund approved');
    case 2:
      return lbl('LBL_REFUND_DECLINED', 'Refund declined');
    default:
      return status === null || status === undefined || status === '' ? '—' : String(status);
  }
};

const COURSE_REQUEST_PENDING = 0;
const COURSE_EDIT_REQUEST_PENDING = 0;
const COURSE_REFUND_PENDING = 0;

const GDPR_STATUS_PENDING = 1;

const groupClassStatusLabel = (status: unknown, lbl: (key: string, fallback: string) => string) => {
  switch (Number(status)) {
    case 1:
      return lbl('LBL_SCHEDULED', 'Scheduled');
    case 2:
      return lbl('LBL_COMPLETED', 'Completed');
    case 3:
      return lbl('LBL_CANCELLED', 'Cancelled');
    default:
      return status === null || status === undefined || status === '' ? '—' : String(status);
  }
};

const formatAdminMoney = (value: unknown) => {
  const amount = Number(value ?? 0);
  if (Number.isNaN(amount)) return '—';
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(amount);
};

const BANK_PAYOUT = 'BankPayout';
const PAYPAL_PAYOUT = 'PaypalPayout';

const USER_TEACHER = 2;

const ORDER_MODULES = new Set([
  'orders',
  'lessons',
  'subscriptions',
  'classes',
  'course-orders',
  'packages',
  'giftcards',
  'wallet',
  'order-subscription-plans',
]);

const TEACHER_PREF_MODULES = new Set([
  'preferences',
  'speak-language',
  'speak-language-levels',
  'teach-language',
  'issue-report-options',
]);

type Props = {
  config: AdminModuleConfig;
};

export function AdminListPage({ config }: Props) {
  const { lbl, langId: siteLangId } = useSite();
  const { privileges, admin } = useAdminAuth();
  const [searchParams, setSearchParams] = useSearchParams();
  const { setMeta, clearMeta } = useAdminPageMeta();
  const [filters, setFilters] = useState<Record<string, string>>({});
  const [draft, setDraft] = useState<Record<string, string>>({});
  const [rows, setRows] = useState<Record<string, unknown>[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [pagination, setPagination] = useState({ current_page: 1, per_page: 10, total: 0, last_page: 1 });
  const [filterOpen, setFilterOpen] = useState(false);
  const [pendingMessage, setPendingMessage] = useState('');
  const [userModal, setUserModal] = useState<{ type: AdminUserModalType; userId: number } | null>(null);
  const [teacherRequestModal, setTeacherRequestModal] = useState<{
    type: AdminTeacherRequestModalType;
    requestId: number;
    userId: number;
  } | null>(null);
  const [ratingReviewId, setRatingReviewId] = useState<number | null>(null);
  const [ratingReviewCourseName, setRatingReviewCourseName] = useState('');
  const [gdprRequestId, setGdprRequestId] = useState<number | null>(null);
  const [manageAdminModal, setManageAdminModal] = useState<{
    type: AdminManageAdminModalType;
    adminId: number;
  } | null>(null);
  const [groupClassModalOpen, setGroupClassModalOpen] = useState(false);
  const [groupClassModalType, setGroupClassModalType] = useState<1 | 2>(1);
  const [groupClassViewId, setGroupClassViewId] = useState<number | null>(null);
  const [courseViewId, setCourseViewId] = useState<number | null>(null);
  const [courseRequestModal, setCourseRequestModal] = useState<{
    type: AdminCourseRequestModalType;
    requestId: number;
  } | null>(null);
  const [courseEditRequestModal, setCourseEditRequestModal] = useState<{
    type: AdminCourseEditRequestModalType;
    requestId: number;
  } | null>(null);
  const [courseRefundRequestModal, setCourseRefundRequestModal] = useState<{
    type: AdminCourseRefundRequestModalType;
    requestId: number;
  } | null>(null);
  const [questionViewId, setQuestionViewId] = useState<number | null>(null);
  const [quizViewId, setQuizViewId] = useState<number | null>(null);
  const [subOrderView, setSubOrderView] = useState<{
    module: OrderActionModule;
    row: Record<string, unknown>;
  } | null>(null);
  const [reportedIssueView, setReportedIssueView] = useState<Record<string, unknown> | null>(null);
  const [preferenceModalId, setPreferenceModalId] = useState<number | null>(null);
  const [preferenceDragId, setPreferenceDragId] = useState<number | null>(null);
  const [forumQuestionViewId, setForumQuestionViewId] = useState<number | null>(null);
  const [forumTagModalOpen, setForumTagModalOpen] = useState(false);
  const [forumTagId, setForumTagId] = useState(0);
  const [urlRewritingModalOpen, setUrlRewritingModalOpen] = useState(false);
  const [urlRewritingId, setUrlRewritingId] = useState(0);
  const [contentBlockModalOpen, setContentBlockModalOpen] = useState(false);
  const [contentBlockId, setContentBlockId] = useState(0);
  const [contentBlockType, setContentBlockType] = useState(1);
  const [contentPageModalOpen, setContentPageModalOpen] = useState(false);
  const [contentPageId, setContentPageId] = useState(0);
  const [countryModalOpen, setCountryModalOpen] = useState(false);
  const [countryId, setCountryId] = useState(0);
  const [stateModalOpen, setStateModalOpen] = useState(false);
  const [stateId, setStateId] = useState(0);
  const [stateSearchCountries, setStateSearchCountries] = useState<
    { value: string; labelKey: string; labelFallback: string }[]
  >([]);
  const [videoContentModalOpen, setVideoContentModalOpen] = useState(false);
  const [videoContentId, setVideoContentId] = useState(0);
  const [testimonialModalOpen, setTestimonialModalOpen] = useState(false);
  const [testimonialId, setTestimonialId] = useState(0);
  const [faqCategoryModalOpen, setFaqCategoryModalOpen] = useState(false);
  const [faqCategoryId, setFaqCategoryId] = useState(0);
  const [faqModalOpen, setFaqModalOpen] = useState(false);
  const [faqId, setFaqId] = useState(0);
  const [languageLabelId, setLanguageLabelId] = useState(0);
  const [languageLabelImportOpen, setLanguageLabelImportOpen] = useState(false);
  const [emailTemplateCode, setEmailTemplateCode] = useState('');
  const [emailTemplateLangId, setEmailTemplateLangId] = useState(1);
  const [emailTemplateModalOpen, setEmailTemplateModalOpen] = useState(false);
  const [abusiveWordId, setAbusiveWordId] = useState(0);
  const [abusiveWordModalOpen, setAbusiveWordModalOpen] = useState(false);
  const [certificateCode, setCertificateCode] = useState('');
  const [certificateLangId, setCertificateLangId] = useState(1);
  const [certificateModalOpen, setCertificateModalOpen] = useState(false);
  const [navigationModalOpen, setNavigationModalOpen] = useState(false);
  const [navigationId, setNavigationId] = useState(0);
  const [forumReportedViewId, setForumReportedViewId] = useState<number | null>(null);
  const [forumReportedActionId, setForumReportedActionId] = useState(0);
  const [forumTagRequestId, setForumTagRequestId] = useState(0);
  const [pageLangModalOpen, setPageLangModalOpen] = useState(false);
  const [pageLangEditId, setPageLangEditId] = useState(0);
  const [pageLangEditLangId, setPageLangEditLangId] = useState(1);

  const navigate = useNavigate();
  const legacyConfirms = adminLegacyConfirms(lbl);

  const openUserModal = useCallback((type: AdminUserModalType, userId: number) => {
    setUserModal({ type, userId });
  }, []);

  const openCourseRequestModal = useCallback(
    (type: AdminCourseRequestModalType, requestId: number) => {
      setCourseRequestModal({ type, requestId });
    },
    [],
  );

  const openCourseEditRequestModal = useCallback(
    (type: AdminCourseEditRequestModalType, requestId: number) => {
      setCourseEditRequestModal({ type, requestId });
    },
    [],
  );

  const openCourseRefundRequestModal = useCallback(
    (type: AdminCourseRefundRequestModalType, requestId: number) => {
      setCourseRefundRequestModal({ type, requestId });
    },
    [],
  );

  const openTeacherRequestModal = useCallback(
    (type: AdminTeacherRequestModalType, requestId: number, userId: number) => {
      setTeacherRequestModal({ type, requestId, userId });
    },
    [],
  );

  const load = useCallback((options?: { silent?: boolean }) => {
    const silent = options?.silent === true;
    if (!silent) {
      setLoading(true);
    }
    adminApi
      .moduleList(config.module, { page, ...filters })
      .then((res) => {
        const meta = res.data.meta ?? { current_page: 1, last_page: 1, per_page: 10, total: 0 };
        setRows(res.data.data ?? []);
        setPagination(meta);
        setPendingMessage(res.data.message ?? '');
        if (meta.last_page > 0 && page > meta.last_page) {
          setPage(meta.last_page);
        }
      })
      .catch(() => {
        setRows([]);
        setPendingMessage('');
      })
      .finally(() => {
        if (!silent) {
          setLoading(false);
        }
      });
  }, [config.module, filters, page]);

  const reloadSilently = useCallback(() => {
    load({ silent: true });
  }, [load]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    if (config.module === 'page-lang-data') {
      void ensureAdminInnovEditor();
    }
  }, [config.module]);

  useEffect(() => {
    if (config.module !== 'states') {
      setStateSearchCountries([]);
      return;
    }
    void adminApi.stateSearchForm(siteLangId || 1).then((res) => {
      const countries = ((res.data.data?.countries ?? []) as Array<{ id: number; name: string }>).map((country) => ({
        value: String(country.id),
        labelKey: `COUNTRY_${country.id}`,
        labelFallback: country.name,
      }));
      setStateSearchCountries([
        { value: '', labelKey: 'LBL_SELECT', labelFallback: 'Select' },
        ...countries,
      ]);
    });
  }, [config.module, siteLangId]);

  useEffect(() => {
    setPage(1);
    const initialFilters: Record<string, string> =
      config.module === 'rating-reviews' && searchParams.get('type') === 'course'
        ? { ratrev_type: '3' }
        : {};
    if (config.module === 'questions') {
      const cateId = searchParams.get('ques_cate_id');
      const subCateId = searchParams.get('ques_subcate_id');
      const quizId = searchParams.get('quiz_id');
      if (cateId) initialFilters.ques_cate_id = cateId;
      if (subCateId) initialFilters.ques_subcate_id = subCateId;
      if (quizId) initialFilters.quiz_id = quizId;
      if (cateId || subCateId || quizId) {
        setFilterOpen(true);
      }
    }
    if (ORDER_MODULES.has(config.module)) {
      const orderId = searchParams.get('order_id');
      if (orderId) {
        initialFilters.order_id = orderId;
        setFilterOpen(true);
      }
    }
    if (config.module === 'reported-issues' && searchParams.get('escalated') === '1') {
      initialFilters.repiss_status = '3';
    }
    if (config.module === 'content-block') {
      const type = Number(searchParams.get('type') ?? 1) || 1;
      setContentBlockType(type);
      initialFilters.type = String(type);
    }
    if (config.defaultFilters) {
      Object.assign(initialFilters, config.defaultFilters);
    }
    if (config.module === 'teach-language') {
      const parentId = searchParams.get('parent_id');
      if (parentId) {
        initialFilters.parent_id = parentId;
      }
    }
    setFilters(initialFilters);
    setDraft(initialFilters);
  }, [config.module, config.defaultFilters, searchParams]);

  useEffect(() => {
    if (config.module !== 'group-classes' && config.module !== 'package-classes') {
      return;
    }
    const parent = searchParams.get('grpcls_parent');
    if (parent) {
      setFilters((prev) => ({ ...prev, grpcls_parent: parent }));
      setDraft((prev) => ({ ...prev, grpcls_parent: parent }));
      setFilterOpen(true);
      setPage(1);
    }
  }, [config.module, searchParams]);

  useEffect(() => {
    if (config.module !== 'categories') {
      return;
    }
    const parent = searchParams.get('parent_id');
    if (parent) {
      setFilters((prev) => ({ ...prev, parent_id: parent }));
      setDraft((prev) => ({ ...prev, parent_id: parent }));
      setPage(1);
    }
  }, [config.module, searchParams]);

  useEffect(() => {
    const pageKey =
      config.module === 'rating-reviews' && searchParams.get('type') === 'course'
        ? 'course-reviews'
        : (config.pageLangKey ?? config.module);
    let cancelled = false;

    void adminApi.pageText(pageKey).then((res) => {
      if (cancelled) return;
      const pageText = res.data.data ?? {};
      const courseReviewsPage =
        config.module === 'rating-reviews' && searchParams.get('type') === 'course';
      const escalatedIssuesPage =
        config.module === 'reported-issues' && searchParams.get('escalated') === '1';
      setMeta({
        title: escalatedIssuesPage
          ? lbl('LBL_ESCALATED_ISSUES', 'Escalated Issues')
          : !config.useConfigTitle && pageText.title
          ? (config.module === 'certificates' ? lbl('LBL_COURSE_CERTIFICATE', 'Course Certificate') : pageText.title)
          : (courseReviewsPage
            ? lbl('LBL_COURSE_RATING_REVIEWS', 'Course reviews & ratings')
            : lbl(config.titleKey, config.titleFallback)),
        summary: config.module === 'testimonials'
          ? lbl(
              'LBL_TESTIMONIALS_OLD_SUMMARY',
              'View and manage the client/user testimonials for the platform displayed on the front-end homepage.',
            )
          : config.module === 'certificates'
            ? lbl(
                'LBL_CERTIFICATES_OLD_SUMMARY',
                'Manage the content for the default course completion certificate.',
              )
          : pageText.summary,
        warning: config.hidePageWarning ? '' : pageText.warning,
        recommendations: pageText.recommendations,
        helpingText: pageText.helping_text,
        plangId: pageText.plang_id,
      });
    });

    return () => {
      cancelled = true;
      clearMeta();
    };
  }, [clearMeta, config.module, config.pageLangKey, config.titleFallback, config.titleKey, lbl, searchParams, setMeta]);

  const onSearch = (e: React.FormEvent) => {
    e.preventDefault();
    setPage(1);
    const nextFilters = { ...draft };
    if (config.defaultFilters) {
      Object.assign(nextFilters, config.defaultFilters);
    }
    setFilters(nextFilters);
  };

  const onClear = () => {
    if (
      (config.module === 'group-classes' || config.module === 'package-classes') &&
      searchParams.get('grpcls_parent')
    ) {
      setSearchParams({});
    }
    if (config.module === 'rating-reviews' && searchParams.get('type') === 'course') {
      const courseReviewFilters = { ratrev_type: '3' };
      setDraft(courseReviewFilters);
      setFilters(courseReviewFilters);
      setPage(1);
      return;
    }
    setDraft({});
    setFilters({});
    setPage(1);
  };

  const onCoursesClear = () => {
    setDraft({});
    setFilters({});
    setPage(1);
  };

  const onQuestionsClear = () => {
    if (
      searchParams.get('ques_cate_id') ||
      searchParams.get('ques_subcate_id') ||
      searchParams.get('quiz_id')
    ) {
      setSearchParams({});
    }
    setDraft({});
    setFilters({});
    setPage(1);
  };

  const onReportedIssuesClear = () => {
    const escalated =
      config.module === 'reported-issues' && searchParams.get('escalated') === '1'
        ? { repiss_status: '3' }
        : {};
    setDraft(escalated);
    setFilters(escalated);
    setPage(1);
  };

  const onTeacherPrefClear = () => {
    const base = { ...(config.defaultFilters ?? {}) };
    if (config.module === 'teach-language') {
      const parentId = searchParams.get('parent_id');
      if (parentId) {
        base.parent_id = parentId;
      }
    }
    setDraft(base);
    setFilters(base);
    setPage(1);
  };

  const onUsersClear = () => {
    setDraft({});
    setFilters({});
    setPage(1);
  };

  const onAddNew = () => {
    if (config.module === 'users') {
      openUserModal('create', 0);
      return;
    }
    if (config.module === 'admin-users') {
      setManageAdminModal({ type: 'create', adminId: 0 });
      return;
    }
    if (config.module === 'group-classes') {
      setGroupClassModalType(1);
      setGroupClassModalOpen(true);
      return;
    }
    if (config.module === 'package-classes') {
      setGroupClassModalType(2);
      setGroupClassModalOpen(true);
      return;
    }
    if (config.module === 'preferences') {
      setPreferenceModalId(0);
      return;
    }
    if (config.module === 'forum-tags') {
      setForumTagId(0);
      setForumTagModalOpen(true);
      return;
    }
    if (config.module === 'url-rewriting') {
      setUrlRewritingId(0);
      setUrlRewritingModalOpen(true);
      return;
    }
    if (config.module === 'content-pages') {
      setContentPageId(0);
      setContentPageModalOpen(true);
      return;
    }
    if (config.module === 'states') {
      setStateId(0);
      setStateModalOpen(true);
      return;
    }
    if (config.module === 'video-content') {
      setVideoContentId(0);
      setVideoContentModalOpen(true);
      return;
    }
    if (config.module === 'testimonials') {
      setTestimonialId(0);
      setTestimonialModalOpen(true);
      return;
    }
    if (config.module === 'faq-categories') {
      setFaqCategoryId(0);
      setFaqCategoryModalOpen(true);
      return;
    }
    if (config.module === 'faq') {
      setFaqId(0);
      setFaqModalOpen(true);
      return;
    }
    if (config.module === 'abusive-words') {
      setAbusiveWordId(0);
      setAbusiveWordModalOpen(true);
    }
  };

  const onExport = () => {
    if (config.module === 'users') {
      const params = new URLSearchParams({ ...filters, export: '1' });
      const url = `${import.meta.env.VITE_API_URL || '/api/v1'}/admin/users/export?${params.toString()}`;
      const token = localStorage.getItem('admin_token');
      void fetch(url, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      })
        .then((res) => res.blob())
        .then((blob) => {
          const link = document.createElement('a');
          link.href = URL.createObjectURL(blob);
          link.download = 'users.csv';
          link.click();
          URL.revokeObjectURL(link.href);
        });
      return;
    }
    if (config.module === 'teacher-requests') {
      void adminApi
        .exportTeacherRequests(filters)
        .then((res) => res.blob())
        .then((blob) => {
          const link = document.createElement('a');
          link.href = URL.createObjectURL(blob);
          link.download = 'teacher-requests.csv';
          link.click();
          URL.revokeObjectURL(link.href);
        });
      return;
    }
    if (config.module === 'withdraw-requests') {
      void adminApi
        .exportWithdrawRequests(filters)
        .then((res) => res.blob())
        .then((blob) => {
          const link = document.createElement('a');
          link.href = URL.createObjectURL(blob);
          link.download = 'withdraw-requests.csv';
          link.click();
          URL.revokeObjectURL(link.href);
        });
      return;
    }
    if (config.module === 'gdpr-requests') {
      void adminApi
        .exportGdprRequests(filters)
        .then((res) => res.blob())
        .then((blob) => {
          const link = document.createElement('a');
          link.href = URL.createObjectURL(blob);
          link.download = 'gdpr-requests.csv';
          link.click();
          URL.revokeObjectURL(link.href);
        });
      return;
    }
    if (config.module === 'admin-users') {
      void adminApi
        .exportManageAdmins(filters)
        .then((res) => res.blob())
        .then((blob) => {
          const link = document.createElement('a');
          link.href = URL.createObjectURL(blob);
          link.download = 'admin-users.csv';
          link.click();
          URL.revokeObjectURL(link.href);
        });
      return;
    }
    if (config.module === 'countries') {
      const cols = config.columns.filter((c) => c.key !== 'action' && c.key !== 'country_flag');
      const header = cols.map((c) => lbl(c.labelKey, c.labelFallback)).join(',');
      const lines = rows.map((row, index) =>
        cols
          .map((c) => {
            const val = c.key === 'serial' ? (page - 1) * perPage + index + 1 : row[c.key];
            const text = val === null || val === undefined ? '' : String(val);
            return `"${text.replace(/"/g, '""')}"`;
          })
          .join(','),
      );
      const csv = [header, ...lines].join('\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'countries.csv';
      link.click();
      URL.revokeObjectURL(link.href);
      return;
    }
    if (config.module === 'states') {
      const cols = config.columns.filter((c) => c.key !== 'action');
      const header = cols.map((c) => lbl(c.labelKey, c.labelFallback)).join(',');
      const lines = rows.map((row, index) =>
        cols
          .map((c) => {
            const val = c.key === 'serial' ? (page - 1) * perPage + index + 1 : row[c.key];
            const text = val === null || val === undefined ? '' : String(val);
            return `"${text.replace(/"/g, '""')}"`;
          })
          .join(','),
      );
      const csv = [header, ...lines].join('\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'states.csv';
      link.click();
      URL.revokeObjectURL(link.href);
      return;
    }
    if (config.module === 'video-content') {
      const cols = config.columns.filter((c) => c.key !== 'action' && c.key !== 'dragdrop');
      const header = cols.map((c) => lbl(c.labelKey, c.labelFallback)).join(',');
      const lines = rows.map((row, index) =>
        cols
          .map((c) => {
            const val = c.key === 'serial' ? index + 1 : row[c.key];
            const text = val === null || val === undefined ? '' : String(val);
            return `"${text.replace(/"/g, '""')}"`;
          })
          .join(','),
      );
      const csv = [header, ...lines].join('\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'video-content.csv';
      link.click();
      URL.revokeObjectURL(link.href);
      return;
    }
    if (config.module === 'testimonials') {
      const cols = config.columns.filter((c) => c.key !== 'action');
      const header = cols.map((c) => lbl(c.labelKey, c.labelFallback)).join(',');
      const lines = rows.map((row, index) =>
        cols
          .map((c) => {
            const val = c.key === 'serial' ? index + 1 : row[c.key];
            const text = val === null || val === undefined ? '' : String(val);
            return `"${text.replace(/"/g, '""')}"`;
          })
          .join(','),
      );
      const csv = [header, ...lines].join('\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'testimonials.csv';
      link.click();
      URL.revokeObjectURL(link.href);
      return;
    }
    if (config.module === 'faq-categories') {
      void adminApi
        .exportFaqCategories(filters)
        .then((res) => res.blob())
        .then((blob) => {
          const link = document.createElement('a');
          link.href = URL.createObjectURL(blob);
          link.download = 'faq-categories.csv';
          link.click();
          URL.revokeObjectURL(link.href);
        });
      return;
    }
    if (config.module === 'faq') {
      void adminApi
        .exportFaq(filters)
        .then((res) => res.blob())
        .then((blob) => {
          const link = document.createElement('a');
          link.href = URL.createObjectURL(blob);
          link.download = 'faq.csv';
          link.click();
          URL.revokeObjectURL(link.href);
        });
      return;
    }
    if (config.module === 'label') {
      void adminApi
        .exportLanguageLabels(filters)
        .then((res) => res.blob())
        .then((blob) => {
          const link = document.createElement('a');
          link.href = URL.createObjectURL(blob);
          link.download = 'Labels.csv';
          link.click();
          URL.revokeObjectURL(link.href);
        });
      return;
    }
    if (config.module === 'email-templates') {
      void adminApi
        .exportEmailTemplates(filters)
        .then((res) => res.blob())
        .then((blob) => {
          const link = document.createElement('a');
          link.href = URL.createObjectURL(blob);
          link.download = 'email-templates.csv';
          link.click();
          URL.revokeObjectURL(link.href);
        });
      return;
    }
    if (config.module === 'group-classes' || config.module === 'package-classes') {
      void adminApi
        .exportGroupClasses(filters, config.module)
        .then((res) => res.blob())
        .then((blob) => {
          const link = document.createElement('a');
          link.href = URL.createObjectURL(blob);
          link.download = `${config.module}.csv`;
          link.click();
          URL.revokeObjectURL(link.href);
        });
      return;
    }
    if (config.module === 'courses') {
      void adminApi
        .exportCourses(filters)
        .then((res) => res.blob())
        .then((blob) => {
          const link = document.createElement('a');
          link.href = URL.createObjectURL(blob);
          link.download = 'courses.csv';
          link.click();
          URL.revokeObjectURL(link.href);
        });
      return;
    }
    if (config.module === 'questions') {
      void adminApi
        .exportQuestions(filters)
        .then((res) => res.blob())
        .then((blob) => {
          const link = document.createElement('a');
          link.href = URL.createObjectURL(blob);
          link.download = 'questions.csv';
          link.click();
          URL.revokeObjectURL(link.href);
        });
      return;
    }
    if (config.module === 'quizzes') {
      void adminApi
        .exportQuizzes(filters)
        .then((res) => res.blob())
        .then((blob) => {
          const link = document.createElement('a');
          link.href = URL.createObjectURL(blob);
          link.download = 'quizzes.csv';
          link.click();
          URL.revokeObjectURL(link.href);
        });
      return;
    }
    if (ORDER_MODULES.has(config.module)) {
      void adminApi
        .exportOrders(config.module, filters)
        .then((res) => res.json())
        .then((payload: { data?: Record<string, unknown>[] }) => {
          const data = payload.data ?? [];
          const cols = config.columns.filter((c) => c.key !== 'action');
          const header = cols.map((c) => lbl(c.labelKey, c.labelFallback)).join(',');
          const lines = data.map((row) =>
            cols
              .map((c) => {
                const val = row[c.key];
                const text = val === null || val === undefined ? '' : String(val);
                return `"${text.replace(/"/g, '""')}"`;
              })
              .join(','),
          );
          const csv = [header, ...lines].join('\n');
          const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
          const link = document.createElement('a');
          link.href = URL.createObjectURL(blob);
          link.download = `${config.module}.csv`;
          link.click();
          URL.revokeObjectURL(link.href);
        });
      return;
    }
    if (config.module === 'preferences') {
      const cols = config.columns.filter((c) => c.key !== 'action' && c.key !== 'serial');
      const header = cols.map((c) => lbl(c.labelKey, c.labelFallback)).join(',');
      const lines = rows.map((row) =>
        cols
          .map((c) => {
            const val = row[c.key];
            const text = val === null || val === undefined ? '' : String(val);
            return `"${text.replace(/"/g, '""')}"`;
          })
          .join(','),
      );
      const csv = [header, ...lines].join('\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'preferences.csv';
      link.click();
      URL.revokeObjectURL(link.href);
      return;
    }
    if (TEACHER_PREF_MODULES.has(config.module) && config.module !== 'preferences') {
      const cols = config.columns.filter((c) => c.key !== 'action' && c.key !== 'serial');
      const header = cols.map((c) => lbl(c.labelKey, c.labelFallback)).join(',');
      const lines = rows.map((row) =>
        cols
          .map((c) => {
            const val = row[c.key];
            const text = val === null || val === undefined ? '' : String(val);
            return `"${text.replace(/"/g, '""')}"`;
          })
          .join(','),
      );
      const csv = [header, ...lines].join('\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = `${config.module}.csv`;
      link.click();
      URL.revokeObjectURL(link.href);
      return;
    }
    if (config.module === 'url-rewriting') {
      const cols = config.columns.filter((c) => c.key !== 'action' && c.key !== 'serial');
      const header = [lbl('LBL_SRNO', 'Sr. No.'), ...cols.map((c) => lbl(c.labelKey, c.labelFallback))].join(',');
      const lines = rows.map((row, index) => {
        const values = cols.map((c) => {
          const val = row[c.key];
          const text = val === null || val === undefined ? '' : String(val);
          return `"${text.replace(/"/g, '""')}"`;
        });
        return [String((page - 1) * perPage + index + 1), ...values].join(',');
      });
      const csv = [header, ...lines].join('\n');
      const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'url-rewriting.csv';
      link.click();
      URL.revokeObjectURL(link.href);
    }
  };

  const cancelOrder = (orderId: number) => {
    if (!window.confirm(lbl('LBL_ARE_YOU_SURE', 'Are you sure?'))) {
      return;
    }
    void adminApi
      .cancelOrder(orderId)
      .then(() => load())
      .catch((err: unknown) => {
        const message =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_INVALID_REQUEST', 'Invalid request');
        window.alert(message);
      });
  };

  const deletePreference = (preferId: number) => {
    if (!window.confirm(lbl('LBL_DO_YOU_WANT_TO_REMOVE', 'Do you want to remove?'))) {
      return;
    }
    void adminApi
      .deletePreference(preferId)
      .then(() => load())
      .catch((err: unknown) => {
        const message =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          lbl('LBL_INVALID_REQUEST', 'Invalid request');
        window.alert(message);
      });
  };

  const onPreferenceDrop = (targetId: number) => {
    if (preferenceDragId === null || preferenceDragId === targetId) {
      return;
    }
    const fromIndex = rows.findIndex((row) => Number(row.id) === preferenceDragId);
    const toIndex = rows.findIndex((row) => Number(row.id) === targetId);
    if (fromIndex < 0 || toIndex < 0) {
      return;
    }
    const nextRows = [...rows];
    const [moved] = nextRows.splice(fromIndex, 1);
    nextRows.splice(toIndex, 0, moved);
    setRows(nextRows);
    void adminApi
      .updatePreferenceOrder(nextRows.map((row) => Number(row.id)))
      .then(() => load())
      .catch(() => load());
  };

  const onContentBlockDrop = (targetId: number) => {
    if (preferenceDragId === null || preferenceDragId === targetId) {
      return;
    }
    const fromIndex = rows.findIndex((row) => Number(row.id) === preferenceDragId);
    const toIndex = rows.findIndex((row) => Number(row.id) === targetId);
    if (fromIndex < 0 || toIndex < 0) {
      return;
    }
    const nextRows = [...rows];
    const [moved] = nextRows.splice(fromIndex, 1);
    nextRows.splice(toIndex, 0, moved);
    setRows(nextRows);
    void adminApi
      .updateContentBlockOrder(nextRows.map((row) => Number(row.id)))
      .then(() => load())
      .catch(() => load());
  };

  const onVideoContentDrop = (targetId: number) => {
    if (preferenceDragId === null || preferenceDragId === targetId) {
      return;
    }
    const fromIndex = rows.findIndex((row) => Number(row.id) === preferenceDragId);
    const toIndex = rows.findIndex((row) => Number(row.id) === targetId);
    if (fromIndex < 0 || toIndex < 0) {
      return;
    }
    const nextRows = [...rows];
    const [moved] = nextRows.splice(fromIndex, 1);
    nextRows.splice(toIndex, 0, moved);
    setRows(nextRows);
    void adminApi
      .updateVideoContentOrder(nextRows.map((row) => Number(row.id)))
      .then(() => load())
      .catch(() => load());
  };

  const onFaqCategoryDrop = (targetId: number) => {
    if (preferenceDragId === null || preferenceDragId === targetId) {
      return;
    }
    const fromIndex = rows.findIndex((row) => Number(row.id) === preferenceDragId);
    const toIndex = rows.findIndex((row) => Number(row.id) === targetId);
    if (fromIndex < 0 || toIndex < 0) {
      return;
    }
    const nextRows = [...rows];
    const [moved] = nextRows.splice(fromIndex, 1);
    nextRows.splice(toIndex, 0, moved);
    setRows(nextRows);
    void adminApi
      .updateFaqCategoryOrder(nextRows.map((row) => Number(row.id)))
      .then(() => load())
      .catch(() => load());
  };

  const updateWithdrawStatus = (withdrawalId: number, status: number, statusName: string) => {
    const confirmPrefix = lbl('LBL_DO_YOU_WANT_TO', 'Do you want to');
    const confirmSuffix = lbl('LBL_THE_REQUEST', 'the request');
    if (!window.confirm(`${confirmPrefix} ${statusName} ${confirmSuffix}?`)) {
      return;
    }
    void adminApi
      .updateWithdrawRequestStatus(withdrawalId, status)
      .then(() => load())
      .catch((err: unknown) => {
        const message =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Update failed';
        window.alert(message);
      });
  };

  const deleteForumQuestion = (id: number) => {
    if (!window.confirm(legacyConfirms.delete)) {
      return;
    }
    void adminApi
      .deleteForumQuestion(id)
      .then(() => load())
      .catch((err: unknown) => {
        const message =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Unable to delete';
        window.alert(message);
      });
  };

  const deleteForumTag = (id: number) => {
    if (!window.confirm(legacyConfirms.delete)) {
      return;
    }
    void adminApi
      .deleteForumTag(id)
      .then(() => load())
      .catch((err: unknown) => {
        const message =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Unable to delete';
        window.alert(message);
      });
  };

  const deleteUrlRewriting = (id: number) => {
    if (!window.confirm(legacyConfirms.delete)) {
      return;
    }
    void adminApi
      .deleteUrlRewriting(id)
      .then(() => load())
      .catch((err: unknown) => {
        const message =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Unable to delete';
        window.alert(message);
      });
  };

  const deleteContentPage = (id: number) => {
    if (!window.confirm(legacyConfirms.delete)) {
      return;
    }
    void adminApi
      .deleteContentPage(id)
      .then(() => load())
      .catch((err: unknown) => {
        const message =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Unable to delete';
        window.alert(message);
      });
  };

  const restoreForumTag = (id: number) => {
    if (!window.confirm(lbl('LBL_DO_YOU_WANT_TO_RESTORE', 'Do you want to restore?'))) {
      return;
    }
    void adminApi
      .restoreForumTag(id)
      .then(() => load())
      .catch((err: unknown) => {
        const message =
          (err as { response?: { data?: { message?: string } } })?.response?.data?.message ??
          'Unable to restore';
        window.alert(message);
      });
  };

  const perPage = pagination.per_page || 10;
  const isTeacherRequests = config.module === 'teacher-requests';
  const isWithdrawRequests = config.module === 'withdraw-requests';
  const isRatingReviews = config.module === 'rating-reviews';
  const isGdprRequests = config.module === 'gdpr-requests';
  const isAdminUsers = config.module === 'admin-users';
  const isGroupClasses = config.module === 'group-classes';
  const isPackageClasses = config.module === 'package-classes';
  const isCourses = config.module === 'courses';
  const isCategories = config.module === 'categories';
  const isContentBlocks = config.module === 'content-block';
  const isContentPages = config.module === 'content-pages';
  const isNavigations = config.module === 'navigations';
  const isCountries = config.module === 'countries';
  const isStates = config.module === 'states';
  const isVideoContent = config.module === 'video-content';
  const isTestimonials = config.module === 'testimonials';
  const isFaqCategories = config.module === 'faq-categories';
  const isFaq = config.module === 'faq';
  const isLanguageLabels = config.module === 'label';
  const isEmailTemplates = config.module === 'email-templates';
  const isAbusiveWords = config.module === 'abusive-words';
  const isCertificates = config.module === 'certificates';
  const isCourseRequests = config.module === 'course-requests';
  const isCourseEditRequests = config.module === 'course-edit-requests';
  const isCourseRefundRequests = config.module === 'course-refund-requests';
  const isQuestions = config.module === 'questions';
  const isQuizzes = config.module === 'quizzes';
  const isReportedIssues = config.module === 'reported-issues';
  const isEscalatedIssues = isReportedIssues && searchParams.get('escalated') === '1';
  const isPreferences = config.module === 'preferences';
  const preferenceType = Number(config.defaultFilters?.type ?? 0);
  const isTeacherPrefModule = TEACHER_PREF_MODULES.has(config.module);
  const isOrderModule = ORDER_MODULES.has(config.module);
  const isForumQuestions = config.module === 'forum';
  const isForumTags = config.module === 'forum-tags';
  const isForumReportedQuestions = config.module === 'forum-reported-questions';
  const isForumTagRequests = config.module === 'forum-tag-requests';
  const isUrlRewriting = config.module === 'url-rewriting';
  const isPageLangData = config.module === 'page-lang-data';
  const isForumModule = FORUM_MODULES.has(config.module);
  const canViewQuestions = Boolean(privileges.canViewQuestions);
  const isCourseReviews =
    config.module === 'rating-reviews' && searchParams.get('type') === 'course';
  const canEdit = isCourseReviews
    ? Boolean(privileges.canEditCourseReviews)
    : !config.canEditPrivilege
      || Boolean(privileges[config.canEditPrivilege as keyof typeof privileges])
      || admin?.id === 1;
  const showToolbar = Boolean((config.creatable && canEdit) || (config.importable && canEdit) || config.exportable);
  const usesLegacyPagination =
    config.module === 'users' ||
    isTeacherRequests ||
    isWithdrawRequests ||
    isRatingReviews ||
    isGdprRequests ||
    isAdminUsers ||
    isGroupClasses ||
    isPackageClasses ||
    isCourses ||
    isContentBlocks ||
    isContentPages ||
    isNavigations ||
    isCountries ||
    isStates ||
    isVideoContent ||
    isTestimonials ||
    isFaqCategories ||
    isFaq ||
    isLanguageLabels ||
    isEmailTemplates ||
    isAbusiveWords ||
    isCertificates ||
    isCourseRequests ||
    isCourseEditRequests ||
    isCourseRefundRequests ||
    isQuestions ||
    isQuizzes ||
    isReportedIssues ||
    isTeacherPrefModule ||
    isOrderModule ||
    isForumModule ||
    isPageLangData;
  const loggedInAdminId = admin?.id ?? 0;
  const canViewAdminPermissions = Boolean(privileges.canViewAdminPermissions);

  const visibleColumns = (() => {
    if (config.module === 'users') {
      return config.columns;
    }
    if (isTeacherRequests || isWithdrawRequests || isRatingReviews || isGdprRequests || isAdminUsers || isGroupClasses || isPackageClasses || isCourses || isContentBlocks || isContentPages || isNavigations || isCountries || isStates || isVideoContent || isTestimonials || isFaqCategories || isFaq || isLanguageLabels || isEmailTemplates || isAbusiveWords || isCertificates || isCourseRequests || isCourseEditRequests || isCourseRefundRequests || isQuestions || isQuizzes || isReportedIssues || isTeacherPrefModule || isOrderModule || isForumModule) {
      if (isAdminUsers) {
        return canEdit || canViewAdminPermissions
          ? config.columns
          : config.columns.filter((col) => col.key !== 'action');
      }
      if (isGroupClasses || isPackageClasses || isCourses) {
        return config.columns;
      }
      if (isContentBlocks) {
        const columns = contentBlockType === 1 && canEdit ? config.columns : config.columns.filter((col) => col.key !== 'dragdrop');
        return canEdit ? columns : columns.filter((col) => col.key !== 'action');
      }
      if (isContentPages) {
        return canEdit ? config.columns : config.columns.filter((col) => col.key !== 'action');
      }
      if (isNavigations) {
        return canEdit ? config.columns : config.columns.filter((col) => col.key !== 'action');
      }
      if (isCountries) {
        return canEdit ? config.columns : config.columns.filter((col) => col.key !== 'action');
      }
      if (isStates) {
        return canEdit ? config.columns : config.columns.filter((col) => col.key !== 'action');
      }
      if (isVideoContent) {
        return canEdit ? config.columns : config.columns.filter((col) => col.key !== 'action' && col.key !== 'dragdrop');
      }
      if (isTestimonials) {
        return canEdit ? config.columns : config.columns.filter((col) => col.key !== 'action');
      }
      if (isFaqCategories) {
        return canEdit ? config.columns : config.columns.filter((col) => col.key !== 'action' && col.key !== 'dragdrop');
      }
      if (isFaq) {
        return canEdit ? config.columns : config.columns.filter((col) => col.key !== 'action');
      }
      if (isLanguageLabels) {
        return canEdit ? config.columns : config.columns.filter((col) => col.key !== 'action');
      }
      if (isEmailTemplates) {
        return canEdit ? config.columns : config.columns.filter((col) => col.key !== 'action');
      }
      if (isAbusiveWords) {
        return canEdit ? config.columns : config.columns.filter((col) => col.key !== 'action');
      }
      if (isCertificates) {
        return config.columns;
      }
      if (isCourseRequests || isCourseEditRequests || isCourseRefundRequests) {
        return config.columns;
      }
      if (isQuestions) {
        return config.columns;
      }
      if (isQuizzes) {
        return config.columns;
      }
      if (isReportedIssues) {
        return config.columns;
      }
      if (isTeacherPrefModule) {
        return canEdit ? config.columns : config.columns.filter((col) => col.key !== 'action');
      }
      if (isOrderModule) {
        return config.columns;
      }
      if (isForumReportedQuestions || isForumTagRequests) {
        return canEdit ? config.columns : config.columns.filter((col) => col.key !== 'action');
      }
      if (isPageLangData) {
        return canEdit ? config.columns : config.columns.filter((col) => col.key !== 'action');
      }
      if (isForumQuestions || isForumTags) {
        return canEdit ? config.columns : config.columns.filter((col) => col.key !== 'action');
      }
      return canEdit ? config.columns : config.columns.filter((col) => col.key !== 'action');
    }
    if (rows.length === 0) {
      return config.columns;
    }
    return config.columns.filter((col) => {
      if (col.key === 'serial' || col.key === 'action' || col.key === 'user_image') return true;
      return rows.some((row) => {
        const val = row[col.key];
        return val !== null && val !== undefined && val !== '';
      });
    });
  })();

  const tableColumns =
    config.showDragHandle && canEdit
      ? [{ key: 'dragdrop', labelKey: 'LBL_DRAG', labelFallback: '' }, ...visibleColumns]
      : visibleColumns;

  const formatValue = (key: string, value: unknown) => {
    if (value === null || value === undefined || value === '') return '—';
    if (key === 'active' || key === 'featured' || key === 'verified' || key === 'published') {
      return value ? lbl('LBL_YES', 'Yes') : lbl('LBL_NO', 'No');
    }
    if (key === 'amount' && typeof value === 'number') {
      return value.toFixed(2);
    }
    if (
      (key === 'order_net_amount' ||
        key === 'order_total_amount' ||
        key === 'ordles_net_amount' ||
        key === 'ordcls_net_amount' ||
        key === 'ordcrs_net_amount') &&
      typeof value === 'number'
    ) {
      return value.toFixed(2);
    }
    return String(value);
  };

  const formatRegisteredDate = (value: unknown) => {
    if (!value || value === '0000-00-00 00:00:00') {
      return lbl('LBL_NA', 'N/A');
    }
    const parsed = moment(String(value));
    return parsed.isValid() ? parsed.format('MMM DD, YYYY hh:mm A') : String(value);
  };

  const formatTeacherRequestDate = (value: unknown) => {
    if (!value || value === '0000-00-00 00:00:00') {
      return lbl('LBL_NA', 'N/A');
    }
    const parsed = moment(String(value));
    return parsed.isValid() ? parsed.format('MMM DD, YYYY HH:mm') : String(value);
  };

  const formatWithdrawDate = (value: unknown) => {
    if (!value || value === '0000-00-00 00:00:00') {
      return lbl('LBL_NA', 'N/A');
    }
    const parsed = moment(String(value));
    return parsed.isValid() ? parsed.format('MMM D, YYYY HH:mm') : String(value);
  };

  const formatOrderDate = formatWithdrawDate;
  const formatRatingReviewDate = formatWithdrawDate;

  const formatClassDate = formatWithdrawDate;
  const formatGdprDate = formatClassDate;

  const formatGroupClassDate = (value: unknown) => {
    if (!value || value === '0000-00-00 00:00:00') {
      return lbl('LBL_NA', 'N/A');
    }
    const parsed = moment(String(value));
    if (!parsed.isValid()) {
      return String(value);
    }
    return parsed.format('MMM DD, YYYY HH:mm');
  };

  const formatQuestionDate = (value: unknown) => {
    if (!value || value === '0000-00-00 00:00:00') {
      return lbl('LBL_NA', 'N/A');
    }
    const parsed = moment(String(value));
    if (!parsed.isValid()) {
      return String(value);
    }
    return (
      <>
        {parsed.format('MMM DD, YYYY')}
        <br />
        {parsed.format('HH:mm')}
      </>
    );
  };

  const renderWithdrawAccountDetails = (row: Record<string, unknown>) => {
    const code = String(row.payment_method_code ?? '');
    const lines: { key: string; node: ReactNode }[] = [];
    const addLine = (labelKey: string, fallback: string, value: unknown, multiline = false) => {
      if (value === null || value === undefined || value === '') {
        return;
      }
      lines.push({
        key: `${labelKey}-${lines.length}`,
        node: (
          <>
            <strong>{lbl(labelKey, fallback)}: </strong>
            {multiline
              ? String(value)
                  .split('\n')
                  .map((line, index, parts) => (
                    <span key={index}>
                      {line}
                      {index < parts.length - 1 ? <br /> : null}
                    </span>
                  ))
              : String(value)}
          </>
        ),
      });
    };

    if (code === BANK_PAYOUT) {
      addLine('LBL_BANK_NAME', 'Bank name', row.bank_name);
      addLine('LBL_AC_NAME', 'Account holder name', row.account_holder_name);
      addLine('LBL_AC_NUMBER', 'Account number', row.account_number);
      addLine('LBL_IFSC/SWIFT_CODE', 'IFSC/SWIFT code', row.ifsc_swift_code);
      addLine('LBL_BANK_ADDRESS', 'Bank address', row.bank_address, true);
    } else if (code === PAYPAL_PAYOUT) {
      addLine('LBL_PAYPAL_EMAIL', 'PayPal Email', row.paypal_email);
    }
    addLine('LBL_COMMENTS', 'Comments', row.comments, true);

    if (lines.length === 0) {
      return '—';
    }

    return lines.map((line, index) => (
      <span key={line.key}>
        {line.node}
        {index < lines.length - 1 ? <br /> : null}
      </span>
    ));
  };

  const renderCell = (key: string, row: Record<string, unknown>, index: number) => {
    switch (key) {
      case 'dragdrop':
        if (isVideoContent && Number(row.active) !== 1) {
          return null;
        }
        if (isFaqCategories && Number(row.active) !== 1) {
          return null;
        }
        if (isContentBlocks && (contentBlockType !== 1 || Number(row.active) !== 1)) {
          return null;
        }
        return (
          <i
            className="ion-arrow-move icon"
            draggable
            onDragStart={() => setPreferenceDragId(Number(row.id))}
            onDragEnd={() => setPreferenceDragId(null)}
          />
        );
      case 'serial':
        return (page - 1) * perPage + index + 1;
      case 'request_number':
        return String(row.request_number ?? row.id ?? '');
      case 'user_details':
        return (
          <>
            {String(row.full_name ?? '')}
            <br />
            <small>({String(row.email ?? '')})</small>
          </>
        );
      case 'transaction_fee':
        return formatAdminMoney(row.transaction_fee);
      case 'account_details':
        return isWithdrawRequests ? renderWithdrawAccountDetails(row) : formatValue(key, row[key]);
      case 'user_image':
        return (
          <img
            style={{ width: 40 }}
            src={userProfileImageUrl(Number(row.id))}
            alt=""
          />
        );
      case 'country_flag':
        return (
          <img
            src={legacyFlagUrl(String(row.country_code ?? ''))}
            alt=""
            style={{ border: '1px solid #ddd', width: 30 }}
          />
        );
      case 'full_name':
        if (isGdprRequests) {
          if (Number(row.user_deleted) === 1) {
            return lbl('LBL_DELETED_USER', 'Deleted User');
          }
          return String(row.full_name ?? '');
        }
        if (isAdminUsers) {
          return String(row.full_name ?? '');
        }
        if (isTeacherRequests || isWithdrawRequests) {
          return String(row.full_name ?? '');
        }
        return (
          <>
            {String(row.full_name ?? '')}
            <br />
            {lbl('LBL_USER_ID', 'User ID')}: {String(row.id ?? '')}
          </>
        );
      case 'email':
        if (isGdprRequests) {
          return row.email ? String(row.email) : '—';
        }
        if (isTeacherRequests) {
          return String(row.email ?? '');
        }
        return (
          <>
            {String(row.email ?? '')}
            <br />
            <span dir="ltr">
              {row.phone_display
                ? String(row.phone_display)
                : row.phone_number
                  ? String(row.phone_number)
                  : ''}
            </span>
          </>
        );
      case 'type': {
        const chips: { label: string; className: string }[] = [];
        if (row.is_affiliate) {
          chips.push({ label: lbl('LBL_Affiliate', 'Affiliate'), className: 'chip supplier' });
        } else {
          chips.push({ label: lbl('LBL_Learner', 'Learner'), className: 'chip supplier' });
          if (row.is_teacher) {
            chips.push({ label: lbl('LBL_Teacher', 'Teacher'), className: 'chip advertiser' });
          } else if (Number(row.registered_as) === USER_TEACHER) {
            return (
              <ul className="chips">
                <li className="chip supplier">{lbl('LBL_Learner', 'Learner')}</li>
                <li>
                  <small className="badge badge-danger">
                    {lbl('LBL_SIGNING_UP_FOR_TEACHER', 'Signing up for teacher')}
                  </small>
                </li>
              </ul>
            );
          }
        }
        return (
          <ul className="chips">
            {chips.map((c) => (
              <li key={c.label} className={c.className}>
                {c.label}
              </li>
            ))}
          </ul>
        );
      }
      case 'created_at':
        if (isTeacherRequests) {
          return formatTeacherRequestDate(row.created_at);
        }
        if (isWithdrawRequests) {
          return formatWithdrawDate(row.created_at);
        }
        if (isRatingReviews) {
          return formatRatingReviewDate(row.created_at);
        }
        if (isGdprRequests) {
          return formatGdprDate(row.created_at);
        }
        if (isGroupClasses || isPackageClasses) {
          return formatGroupClassDate(row.created_at);
        }
        if (isCourseRequests || isCourseEditRequests || isCourseRefundRequests) {
          return formatWithdrawDate(row.created_at);
        }
        if (isQuestions) {
          return formatQuestionDate(row.created_at);
        }
        if (isQuizzes) {
          return formatQuestionDate(row.created_at);
        }
        return config.module === 'users' ? formatRegisteredDate(row.created_at) : formatValue(key, row[key]);
      case 'published_at':
      case 'expired_at':
        return formatWithdrawDate(row[key]);
      case 'subcategories':
        if (isCategories && Number(row.subcategories) > 0) {
          return (
            <Link to={`/admin/categories?parent_id=${row.id}`} className="link-text link-underline">
              {String(row.subcategories)}
            </Link>
          );
        }
        return formatValue(key, row[key]);
      case 'identifier':
        if (isVideoContent) {
          const value = String(row.identifier ?? '');
          return value.length > 30 ? `${value.slice(0, 30)}...` : value;
        }
        if (isTeacherPrefModule) {
          return String(row.identifier ?? '—');
        }
        return formatValue(key, row[key]);
      case 'title':
        if (isVideoContent) {
          const value = String(row.title ?? '');
          return value.length > 30 ? `${value.slice(0, 30)}...` : value;
        }
        if (isTeacherPrefModule) {
          return String(row.title ?? '—');
        }
        return formatValue(key, row[key]);
      case 'min_price_label':
      case 'max_price_label':
      case 'hourly_price_label':
      case 'featured_label':
      case 'subcategories_label':
        if (isTeacherPrefModule) {
          if (key === 'subcategories_label' && Number(row.subcategories) > 0) {
            return (
              <Link to={`/admin/teach-language?parent_id=${row.id}`} className="link-text link-underline">
                {String(row.subcategories_label)}
              </Link>
            );
          }
          return String(row[key] ?? '—');
        }
        return formatValue(key, row[key]);
      case 'featured':
      case 'verified':
        return row[key] ? lbl('LBL_YES', 'Yes') : lbl('LBL_NO', 'No');
      case 'published':
        return row.published ? lbl('LBL_PUBLISHED', 'Published') : lbl('LBL_DRAFT', 'Draft');
      case 'active':
        if (isAdminUsers) {
          const adminId = Number(row.id);
          if (adminId === 1 || adminId === loggedInAdminId) {
            return null;
          }
          return (
            <AdminStatusSwitch
              id={adminId}
              active={Boolean(row.active)}
              disabled={!canEdit}
              activeLabel={lbl('LBL_Active', 'Active')}
              inactiveLabel={lbl('LBL_Inactive', 'Inactive')}
              onToggle={async (next) => {
                await adminApi.updateManageAdminStatus(adminId, next);
                load();
              }}
            />
          );
        }
        if (config.module === 'users') {
          return (
            <AdminStatusSwitch
              id={Number(row.id)}
              active={Boolean(row.active)}
              disabled={!canEdit}
              activeLabel={lbl('LBL_ACTIVE', 'Active')}
              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
              confirmMessage={lbl(
                'LBL_ARE_YOU_SURE_YOU_WANT_TO_UPDATE_STATUS?',
                'Are you sure you want to update the status?',
              )}
              onToggle={async (next) => {
                await adminApi.updateUserStatus(Number(row.id), next);
                load();
              }}
            />
          );
        }
        if (isCourses) {
          return (
            <AdminStatusSwitch
              id={Number(row.id)}
              active={Number(row.active) === 1}
              disabled={!canEdit}
              activeLabel={lbl('LBL_Active', 'Active')}
              inactiveLabel={lbl('LBL_Inactive', 'Inactive')}
              confirmMessage={
                canEdit
                  ? lbl(
                      'LBL_ARE_YOU_SURE_YOU_WANT_TO_UPDATE_STATUS?',
                      'Are you sure you want to update the status?',
                    )
                  : undefined
              }
              onToggle={async () => {
                await adminApi.updateCourseStatus(Number(row.id), Number(row.active));
                load();
              }}
            />
          );
        }
        if (isCategories) {
          return Number(row.active) === 1 ? lbl('LBL_ACTIVE', 'Active') : lbl('LBL_INACTIVE', 'Inactive');
        }
        if (isContentBlocks) {
          return (
            <AdminStatusSwitch
              id={Number(row.id)}
              active={Number(row.active) === 1}
              disabled={!canEdit}
              activeLabel={lbl('LBL_ACTIVE', 'Active')}
              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
              confirmMessage={legacyConfirms.updateStatus}
              onToggle={async (next) => {
                await adminApi.updateContentBlockStatus(Number(row.id), next);
                load();
              }}
            />
          );
        }
        if (isNavigations) {
          return (
            <AdminStatusSwitch
              id={Number(row.id)}
              active={Number(row.active) === 1}
              disabled={!canEdit}
              activeLabel={lbl('LBL_ACTIVE', 'Active')}
              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
              confirmMessage={legacyConfirms.updateStatus}
              onToggle={async (next) => {
                await adminApi.updateNavigationStatus(Number(row.id), next);
                load();
              }}
            />
          );
        }
        if (isCountries) {
          return (
            <AdminStatusSwitch
              id={Number(row.id)}
              active={Number(row.active) === 1}
              disabled={!canEdit}
              activeLabel={lbl('LBL_ACTIVE', 'Active')}
              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
              confirmMessage={legacyConfirms.updateStatus}
              onToggle={async (next) => {
                await adminApi.updateCountryStatus(Number(row.id), next);
                load();
              }}
            />
          );
        }
        if (isStates) {
          return (
            <AdminStatusSwitch
              id={Number(row.id)}
              active={Number(row.active) === 1}
              disabled={!canEdit}
              activeLabel={lbl('LBL_ACTIVE', 'Active')}
              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
              confirmMessage={legacyConfirms.updateStatus}
              onToggle={async (next) => {
                await adminApi.updateStateStatus(Number(row.id), next);
                load();
              }}
            />
          );
        }
        if (isVideoContent) {
          return (
            <AdminStatusSwitch
              id={Number(row.id)}
              active={Number(row.active) === 1}
              disabled={!canEdit}
              activeLabel={lbl('LBL_ACTIVE', 'Active')}
              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
              confirmMessage={legacyConfirms.updateStatus}
              onToggle={async (next) => {
                await adminApi.updateVideoContentStatus(Number(row.id), next);
                load();
              }}
            />
          );
        }
        if (isTestimonials) {
          return (
            <AdminStatusSwitch
              id={Number(row.id)}
              active={Number(row.active) === 1}
              disabled={!canEdit}
              activeLabel={lbl('LBL_ACTIVE', 'Active')}
              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
              confirmMessage={legacyConfirms.updateStatus}
              onToggle={async (next) => {
                await adminApi.updateTestimonialStatus(Number(row.id), next);
                load();
              }}
            />
          );
        }
        if (isFaqCategories) {
          return (
            <AdminStatusSwitch
              id={Number(row.id)}
              active={Number(row.active) === 1}
              disabled={!canEdit}
              activeLabel={lbl('LBL_ACTIVE', 'Active')}
              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
              confirmMessage={legacyConfirms.updateStatus}
              onToggle={async (next) => {
                await adminApi.updateFaqCategoryStatus(Number(row.id), next);
                load();
              }}
            />
          );
        }
        if (isFaq) {
          return (
            <AdminStatusSwitch
              id={Number(row.id)}
              active={Number(row.active) === 1}
              disabled={!canEdit}
              activeLabel={lbl('LBL_ACTIVE', 'Active')}
              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
              confirmMessage={legacyConfirms.updateStatus}
              onToggle={async (next) => {
                await adminApi.updateFaqStatus(Number(row.id), next);
                load();
              }}
            />
          );
        }
        if (isEmailTemplates) {
          const templateCode = String(row.id ?? row.identifier ?? '');
          return (
            <AdminStatusSwitch
              id={templateCode}
              active={Number(row.active) === 1}
              disabled={!canEdit}
              activeLabel={lbl('LBL_ACTIVE', 'Active')}
              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
              confirmMessage={legacyConfirms.updateStatus}
              onToggle={async (next) => {
                await adminApi.updateEmailTemplateStatus(templateCode, next);
                load();
              }}
            />
          );
        }
        if (isCertificates) {
          const certCode = String(row.certpl_code ?? row.id ?? '');
          return (
            <AdminStatusSwitch
              id={certCode}
              active={Number(row.active) === 1}
              disabled={!canEdit}
              activeLabel={lbl('LBL_ACTIVE', 'Active')}
              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
              confirmMessage={legacyConfirms.updateStatus}
              onToggle={async (next) => {
                await adminApi.updateCertificateStatus(certCode, next);
                load();
              }}
            />
          );
        }
        if (isTeacherPrefModule && config.module !== 'preferences') {
          return (
            <AdminStatusSwitch
              id={Number(row.id)}
              active={Number(row.active) === 1}
              disabled
              activeLabel={lbl('LBL_ACTIVE', 'Active')}
              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
              onToggle={async () => {}}
            />
          );
        }
        if (isForumTags) {
          const tagDeleted = Number(row.deleted) === 1;
          return (
            <AdminStatusSwitch
              id={Number(row.id)}
              active={Number(row.active) === 1}
              disabled={!canEdit || tagDeleted}
              activeLabel={lbl('LBL_ACTIVE', 'Active')}
              inactiveLabel={lbl('LBL_INACTIVE', 'Inactive')}
              confirmMessage={legacyConfirms.updateStatus}
              onToggle={async (next) => {
                await adminApi.updateForumTagStatus(Number(row.id), next);
                load();
              }}
            />
          );
        }
        return row.active ? lbl('LBL_ACTIVE', 'Active') : lbl('LBL_INACTIVE', 'Inactive');
      case 'page_key':
        return String(row.page_key ?? '—');
      case 'action':
        if (isPageLangData && canEdit) {
          return (
            <ul className="actions">
              <li>
                <a
                  href="javascript:void(0)"
                  title={lbl('LBL_EDIT', 'Edit')}
                  onClick={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    setPageLangEditId(Number(row.id));
                    setPageLangEditLangId(Number(row.lang_id ?? 1));
                    setPageLangModalOpen(true);
                  }}
                >
                  <AdminSpriteIcon icon="edit" />
                </a>
              </li>
            </ul>
          );
        }
        if (isWithdrawRequests) {
          return (
            <AdminWithdrawRequestsActions
              withdrawalId={Number(row.id)}
              status={Number(row.status)}
              userDeleted={Number(row.user_deleted) === 1}
              canEdit={canEdit}
              labels={{
                approve: lbl('LBL_APPROVE', 'Approve'),
                decline: lbl('LBL_DECLINE', 'Decline'),
              }}
              onUpdateStatus={updateWithdrawStatus}
            />
          );
        }
        if (isRatingReviews && canEdit) {
          return (
            <ul className="actions">
              <li>
                <a
                  href="javascript:void(0)"
                  title={lbl('LBL_EDIT', 'Edit')}
                  data-bs-toggle="tooltip"
                  data-placement="top"
                  onClick={(e) => {
                    e.preventDefault();
                    setRatingReviewId(Number(row.id));
                    setRatingReviewCourseName(
                      isCourseReviews ? String(row.teacher_name ?? '') : '',
                    );
                  }}
                >
                  <AdminSpriteIcon icon="edit" />
                </a>
              </li>
            </ul>
          );
        }
        if (isGdprRequests && canEdit) {
          const isPending = Number(row.status) === GDPR_STATUS_PENDING;
          return (
            <ul className="actions">
              <li>
                <a
                  href="javascript:void(0)"
                  title={lbl('LBL_EDIT', 'Edit')}
                  data-bs-toggle="tooltip"
                  data-placement="top"
                  className={isPending ? undefined : 'disabled'}
                  onClick={(e) => {
                    e.preventDefault();
                    if (!isPending) return;
                    setGdprRequestId(Number(row.id));
                  }}
                >
                  <AdminSpriteIcon icon="edit" />
                </a>
              </li>
            </ul>
          );
        }
        if (isAdminUsers && (canEdit || canViewAdminPermissions)) {
          return (
            <div className="align-right">
              <AdminManageAdminsActions
                adminId={Number(row.id)}
                loggedInAdminId={loggedInAdminId}
                canEdit={canEdit}
                canViewPermissions={canViewAdminPermissions}
                labels={{
                  edit: lbl('LBL_EDIT', 'Edit'),
                  changePassword: lbl('LBL_CHANGE_PASSWORD', 'Change password'),
                  permissions: lbl('LBL_Admin_Permissions', 'Admin permissions'),
                }}
                onEdit={(id) => setManageAdminModal({ type: 'edit', adminId: id })}
                onChangePassword={(id) => setManageAdminModal({ type: 'change-password', adminId: id })}
              />
            </div>
          );
        }
        if (isGroupClasses || isPackageClasses) {
          return (
            <AdminGroupClassesActions
              classId={Number(row.id)}
              classType={Number(row.class_type)}
              module={isPackageClasses ? 'package-classes' : 'group-classes'}
              labels={{
                viewDetails: lbl('LBL_VIEW', 'View'),
                classes: lbl('LBL_CLasses', 'Classes'),
              }}
              onViewDetails={setGroupClassViewId}
            />
          );
        }
        if (isCourses) {
          return (
            <div className="align-right">
              <AdminCoursesActions
                courseId={Number(row.id)}
                teacherId={Number(row.course_teacher_id ?? 0)}
                canEdit={canEdit}
                labels={{
                  view: lbl('LBL_VIEW', 'View'),
                  preview: lbl('LBL_PREVIEW', 'Preview'),
                }}
                onView={(id) => setCourseViewId(id)}
                onPreview={(teacherId, courseId) => void openCoursePreview(teacherId, courseId)}
              />
            </div>
          );
        }
        if (isCourseRequests) {
          return (
            <div className="align-right">
              <AdminCourseRequestsActions
                requestId={Number(row.id)}
                courseId={Number(row.course_id ?? 0)}
                teacherId={Number(row.user_id ?? 0)}
                status={Number(row.status ?? -1)}
                courseDeleted={Boolean(row.course_deleted)}
                canEdit={canEdit}
                labels={{
                  view: lbl('LBL_VIEW', 'View'),
                  preview: lbl('LBL_PREVIEW', 'Preview'),
                  changeStatus: lbl('LBL_CHANGE_STATUS', 'Change status'),
                }}
                onView={(id) => openCourseRequestModal('view', id)}
                onPreview={(teacherId, courseId) => void openCoursePreview(teacherId, courseId)}
                onChangeStatus={(id) => openCourseRequestModal('change-status', id)}
              />
            </div>
          );
        }
        if (isCourseEditRequests) {
          return (
            <div className="align-right">
              <AdminCourseEditRequestsActions
                requestId={Number(row.id)}
                status={Number(row.status ?? -1)}
                canEdit={canEdit}
                labels={{
                  view: lbl('LBL_VIEW', 'View'),
                  changeStatus: lbl('LBL_CHANGE_STATUS', 'Change status'),
                }}
                onView={(id) => openCourseEditRequestModal('view', id)}
                onChangeStatus={(id) => openCourseEditRequestModal('change-status', id)}
              />
            </div>
          );
        }
        if (isCourseRefundRequests) {
          return (
            <div className="align-right">
              <AdminCourseRefundRequestsActions
                requestId={Number(row.id)}
                status={Number(row.status)}
                canEdit={canEdit}
                labels={{
                  view: lbl('LBL_VIEW', 'View'),
                  changeStatus: lbl('LBL_CHANGE_STATUS', 'Change status'),
                }}
                onView={(id) => openCourseRefundRequestModal('view', id)}
                onChangeStatus={(id) => openCourseRefundRequestModal('change-status', id)}
              />
            </div>
          );
        }
        if (isQuestions) {
          return (
            <div className="align-right">
              <AdminQuestionsActions
                questionId={Number(row.id)}
                labels={{ view: lbl('LBL_VIEW', 'View') }}
                onView={(id) => setQuestionViewId(id)}
              />
            </div>
          );
        }
        if (isQuizzes) {
          return (
            <div className="align-right">
              <AdminQuizzesActions
                quizId={Number(row.id)}
                canViewQuestions={canViewQuestions}
                labels={{
                  view: lbl('LBL_VIEW', 'View'),
                  questionBank: lbl('LBL_QUESTION_BANK', 'Question bank'),
                }}
                onView={(id) => setQuizViewId(id)}
              />
            </div>
          );
        }
        if (isReportedIssues) {
          return (
            <div className="align-right">
              <AdminReportedIssuesActions
                issueId={Number(row.repiss_id ?? row.id ?? 0)}
                labels={{ view: lbl('LBL_VIEW', 'View') }}
                onView={() => setReportedIssueView(row)}
              />
            </div>
          );
        }
        if (isPreferences) {
          return (
            <div className="align-right">
              <AdminPreferencesActions
                preferId={Number(row.prefer_id ?? row.id ?? 0)}
                canEdit={canEdit}
                labels={{
                  edit: lbl('LBL_EDIT', 'Edit'),
                  delete: lbl('LBL_Delete', 'Delete'),
                }}
                onEdit={(id) => setPreferenceModalId(id)}
                onDelete={deletePreference}
              />
            </div>
          );
        }
        if (isOrderModule) {
          return (
            <div className="align-right">
              <AdminOrdersActions
                module={config.module as OrderActionModule}
                row={row}
                canEditOrders={Boolean(privileges.canEditOrders)}
                labels={{
                  view: lbl('LBL_VIEW', 'View'),
                  downloadInvoice: lbl('LBL_DOWNLOAD_INVOICE', 'Download invoice'),
                  cancel: lbl('LBL_CANCEL_ORDER', 'Cancel order'),
                }}
                onViewSubOrder={(module, subRow) => setSubOrderView({ module, row: subRow })}
                onCancelOrder={cancelOrder}
              />
            </div>
          );
        }
        if (isTeacherRequests) {
          return (
            <div className="align-right">
              <AdminTeacherRequestsActions
                requestId={Number(row.id)}
                userId={Number(row.user_id)}
                status={Number(row.status)}
                canEdit={canEdit}
                labels={{
                  view: lbl('LBL_VIEW', 'View'),
                  qualifications: lbl('LBL_QUALIFICATIONS', 'Qualifications'),
                  changeStatus: lbl('LBL_CHANGE_STATUS', 'Change status'),
                }}
                onView={(id) => openTeacherRequestModal('view', id, Number(row.user_id))}
                onChangeStatus={(id) => openTeacherRequestModal('change-status', id, Number(row.user_id))}
              />
            </div>
          );
        }
        if (config.module === 'users') {
          return (
            <div className="align-right">
              <AdminUsersActions
                userId={Number(row.id)}
                canEdit={canEdit}
                labels={{
                  view: lbl('LBL_VIEW', 'View'),
                  edit: lbl('LBL_EDIT', 'Edit'),
                  login: lbl('LBL_LOGIN_INTO_PROFILE', 'Login into profile'),
                  transactions: lbl('LBL_TRANSACTIONS', 'Transactions'),
                  addresses: lbl('LBL_ADDRESSES', 'Addresses'),
                  changePassword: lbl('LBL_CHANGE_PASSWORD', 'Change password'),
                  actions: lbl('LBL_ACTIONS', 'Actions'),
                }}
                onView={(id) => openUserModal('view', id)}
                onEdit={(id) => openUserModal('edit', id)}
                onLogin={(id) => void openUserLogin(id)}
                onTransactions={(id) => openUserModal('transactions', id)}
                onAddresses={(id) => openUserModal('addresses', id)}
                onChangePassword={(id) => openUserModal('change-password', id)}
              />
            </div>
          );
        }
        if (isForumQuestions) {
          return (
            <div className="align-right">
              <AdminForumQuestionActions
                questionId={Number(row.fque_id ?? row.id)}
                commentCount={Number(row.comment_count ?? 0)}
                commentsAllowed={Number(row.comments_allowed ?? 0) === 1}
                canEdit={canEdit}
                labels={{
                  view: lbl('LBL_View', 'View'),
                  delete: lbl('LBL_Delete', 'Delete'),
                  viewComments: lbl('LBL_View_Comments', 'View comments'),
                }}
                onView={setForumQuestionViewId}
                onDelete={deleteForumQuestion}
                onViewComments={(id) => navigate(`/admin/forum/${id}/comments`)}
              />
            </div>
          );
        }
        if (isForumTags) {
          return (
            <div className="align-right">
              <AdminForumTagActions
                tagId={Number(row.ftag_id ?? row.id)}
                deleted={Number(row.deleted) === 1}
                canEdit={canEdit}
                labels={{
                  edit: lbl('LBL_Edit', 'Edit'),
                  delete: lbl('LBL_Delete', 'Delete'),
                  restore: lbl('LBL_Restore_Tag', 'Restore tag'),
                }}
                onEdit={(id) => {
                  setForumTagId(id);
                  setForumTagModalOpen(true);
                }}
                onDelete={deleteForumTag}
                onRestore={restoreForumTag}
              />
            </div>
          );
        }
        if (isUrlRewriting) {
          return (
            <div className="align-right">
              <AdminUrlRewritingActions
                seoUrlId={Number(row.id)}
                canEdit={canEdit}
                labels={{
                  edit: lbl('LBL_Edit', 'Edit'),
                  delete: lbl('LBL_Delete', 'Delete'),
                }}
                onEdit={(id) => {
                  setUrlRewritingId(id);
                  setUrlRewritingModalOpen(true);
                }}
                onDelete={deleteUrlRewriting}
              />
            </div>
          );
        }
        if (isContentPages && canEdit) {
          return (
            <ul className="actions">
              <li>
                <a
                  href="javascript:void(0)"
                  title={lbl('LBL_Edit', 'Edit')}
                  onClick={(e) => {
                    e.preventDefault();
                    setContentPageId(Number(row.id));
                    setContentPageModalOpen(true);
                  }}
                >
                  <AdminSpriteIcon icon="edit" />
                </a>
              </li>
              <li>
                <a
                  href="javascript:void(0)"
                  title={lbl('LBL_Delete', 'Delete')}
                  onClick={(e) => {
                    e.preventDefault();
                    deleteContentPage(Number(row.id));
                  }}
                >
                  <AdminSpriteIcon icon="delete" />
                </a>
              </li>
            </ul>
          );
        }
        if (isContentBlocks && canEdit && Number(row.editable) === 1) {
          return (
            <ul className="actions">
              <li>
                <a
                  href="javascript:void(0)"
                  title={lbl('LBL_Edit', 'Edit')}
                  onClick={(e) => {
                    e.preventDefault();
                    setContentBlockId(Number(row.id));
                    setContentBlockModalOpen(true);
                  }}
                >
                  <AdminSpriteIcon icon="edit" />
                </a>
              </li>
            </ul>
          );
        }
        if (isContentBlocks) {
          return null;
        }
        if (isNavigations) {
          return (
            <ul className="actions">
              {canEdit ? (
                <li>
                  <a
                    href="javascript:void(0)"
                    title={lbl('LBL_Edit', 'Edit')}
                    onClick={(e) => {
                      e.preventDefault();
                      setNavigationId(Number(row.id));
                      setNavigationModalOpen(true);
                    }}
                  >
                    <AdminSpriteIcon icon="edit" />
                  </a>
                </li>
              ) : null}
              <li>
                <a href={`/admin/navigations/${row.id}/pages`} title={lbl('LBL_Pages', 'Pages')}>
                  <AdminSpriteIcon icon="list" />
                </a>
              </li>
            </ul>
          );
        }
        if (isCountries && canEdit) {
          return (
            <ul className="actions">
              <li>
                <a
                  href="javascript:void(0)"
                  title={lbl('LBL_Edit', 'Edit')}
                  onClick={(e) => {
                    e.preventDefault();
                    setCountryId(Number(row.id));
                    setCountryModalOpen(true);
                  }}
                >
                  <AdminSpriteIcon icon="edit" />
                </a>
              </li>
            </ul>
          );
        }
        if (isStates && canEdit) {
          return (
            <ul className="actions">
              <li>
                <a
                  href="javascript:void(0)"
                  title={lbl('LBL_Edit', 'Edit')}
                  onClick={(e) => {
                    e.preventDefault();
                    setStateId(Number(row.id));
                    setStateModalOpen(true);
                  }}
                >
                  <AdminSpriteIcon icon="edit" />
                </a>
              </li>
            </ul>
          );
        }
        if (isVideoContent && canEdit) {
          return (
            <ul className="actions">
              <li>
                <a
                  href="javascript:void(0)"
                  title={lbl('LBL_Edit', 'Edit')}
                  onClick={(e) => {
                    e.preventDefault();
                    setVideoContentId(Number(row.id));
                    setVideoContentModalOpen(true);
                  }}
                >
                  <AdminSpriteIcon icon="edit" />
                </a>
              </li>
              <li>
                <a
                  href="javascript:void(0)"
                  title={lbl('LBL_Delete', 'Delete')}
                  onClick={(e) => {
                    e.preventDefault();
                    if (!window.confirm(legacyConfirms.delete)) {
                      return;
                    }
                    void adminApi.deleteVideoContent(Number(row.id)).then(() => load());
                  }}
                >
                  <AdminSpriteIcon icon="delete" />
                </a>
              </li>
            </ul>
          );
        }
        if (isTestimonials && canEdit) {
          return (
            <ul className="actions">
              <li>
                <a
                  href="javascript:void(0)"
                  title={lbl('LBL_Edit', 'Edit')}
                  onClick={(e) => {
                    e.preventDefault();
                    setTestimonialId(Number(row.id));
                    setTestimonialModalOpen(true);
                  }}
                >
                  <AdminSpriteIcon icon="edit" />
                </a>
              </li>
              <li>
                <a
                  href="javascript:void(0)"
                  title={lbl('LBL_Delete', 'Delete')}
                  onClick={(e) => {
                    e.preventDefault();
                    if (!window.confirm(legacyConfirms.delete)) {
                      return;
                    }
                    void adminApi.deleteTestimonial(Number(row.id)).then(() => load());
                  }}
                >
                  <AdminSpriteIcon icon="delete" />
                </a>
              </li>
            </ul>
          );
        }
        if (isFaqCategories && canEdit) {
          return (
            <ul className="actions">
              <li>
                <a
                  href="javascript:void(0)"
                  title={lbl('LBL_Edit', 'Edit')}
                  onClick={(e) => {
                    e.preventDefault();
                    setFaqCategoryId(Number(row.id));
                    setFaqCategoryModalOpen(true);
                  }}
                >
                  <AdminSpriteIcon icon="edit" />
                </a>
              </li>
              <li>
                <a
                  href="javascript:void(0)"
                  title={lbl('LBL_Delete', 'Delete')}
                  onClick={(e) => {
                    e.preventDefault();
                    if (!window.confirm(legacyConfirms.delete)) {
                      return;
                    }
                    void adminApi.deleteFaqCategory(Number(row.id)).then(() => load());
                  }}
                >
                  <AdminSpriteIcon icon="delete" />
                </a>
              </li>
            </ul>
          );
        }
        if (isFaq && canEdit) {
          return (
            <ul className="actions">
              <li>
                <a
                  href="javascript:void(0)"
                  title={lbl('LBL_Edit', 'Edit')}
                  onClick={(e) => {
                    e.preventDefault();
                    setFaqId(Number(row.id));
                    setFaqModalOpen(true);
                  }}
                >
                  <AdminSpriteIcon icon="edit" />
                </a>
              </li>
              <li>
                <a
                  href="javascript:void(0)"
                  title={lbl('LBL_Delete', 'Delete')}
                  onClick={(e) => {
                    e.preventDefault();
                    if (!window.confirm(legacyConfirms.delete)) {
                      return;
                    }
                    void adminApi.deleteFaq(Number(row.id)).then(() => load());
                  }}
                >
                  <AdminSpriteIcon icon="delete" />
                </a>
              </li>
            </ul>
          );
        }
        if (isLanguageLabels && canEdit) {
          return (
            <ul className="actions">
              <li>
                <a
                  href="javascript:void(0)"
                  title={lbl('LBL_Edit', 'Edit')}
                  onClick={(e) => {
                    e.preventDefault();
                    setLanguageLabelId(Number(row.id));
                  }}
                >
                  <AdminSpriteIcon icon="edit" />
                </a>
              </li>
            </ul>
          );
        }
        if (isEmailTemplates) {
          const templateCode = String(row.id ?? row.identifier ?? '');
          const templateLangId = Number(row.etpl_lang_id ?? siteLangId ?? 1);
          return (
            <ul className="actions">
              {canEdit ? (
                <li>
                  <a
                    href="javascript:void(0)"
                    title={lbl('LBL_Edit', 'Edit')}
                    onClick={(e) => {
                      e.preventDefault();
                      setEmailTemplateCode(templateCode);
                      setEmailTemplateLangId(templateLangId);
                      setEmailTemplateModalOpen(true);
                    }}
                  >
                    <AdminSpriteIcon icon="edit" />
                  </a>
                </li>
              ) : null}
              {templateCode !== 'emails_header_footer_layout' ? (
                <li>
                  <a
                    href={`/admin/email-templates/${encodeURIComponent(templateCode)}/preview/${templateLangId}`}
                    target="_blank"
                    rel="noreferrer"
                    title={lbl('LBL_PREVIEW', 'Preview')}
                  >
                    <AdminSpriteIcon icon="arrow-right" />
                  </a>
                </li>
              ) : null}
            </ul>
          );
        }
        if (isAbusiveWords && canEdit) {
          return (
            <ul className="actions">
              <li>
                <a
                  href="javascript:void(0)"
                  title={lbl('LBL_Edit', 'Edit')}
                  onClick={(e) => {
                    e.preventDefault();
                    setAbusiveWordId(Number(row.id));
                    setAbusiveWordModalOpen(true);
                  }}
                >
                  <AdminSpriteIcon icon="edit" />
                </a>
              </li>
              <li>
                <a
                  href="javascript:void(0)"
                  title={lbl('LBL_Delete', 'Delete')}
                  onClick={(e) => {
                    e.preventDefault();
                    if (!window.confirm(legacyConfirms.delete)) {
                      return;
                    }
                    void adminApi.deleteAbusiveWord(Number(row.id)).then(() => load());
                  }}
                >
                  <AdminSpriteIcon icon="delete" />
                </a>
              </li>
            </ul>
          );
        }
        if (isCertificates) {
          const certCode = String(row.certpl_code ?? row.id ?? '');
          const certLangId = Number(row.certpl_lang_id ?? siteLangId ?? 1);
          return (
            <ul className="actions">
              {canEdit ? (
                <li>
                  <a
                    href="javascript:void(0)"
                    title={lbl('LBL_Edit', 'Edit')}
                    onClick={(e) => {
                      e.preventDefault();
                      setCertificateCode(certCode);
                      setCertificateLangId(certLangId);
                      setCertificateModalOpen(true);
                    }}
                  >
                    <AdminSpriteIcon icon="edit" />
                  </a>
                </li>
              ) : null}
              <li>
                <a
                  href={`/admin/certificates/${encodeURIComponent(certCode)}/preview/${certLangId}`}
                  target="_blank"
                  rel="noreferrer"
                  title={lbl('LBL_PREVIEW', 'Preview')}
                >
                  <AdminSpriteIcon icon="arrow-right" />
                </a>
              </li>
            </ul>
          );
        }
        if (isForumReportedQuestions) {
          return (
            <div className="align-right">
              <AdminForumReportedQuestionActions
                reportId={Number(row.fquerep_id ?? row.id)}
                status={Number(row.status ?? 0)}
                canEdit={canEdit}
                labels={{
                  view: lbl('LBL_View', 'View'),
                  action: lbl('LBL_Action', 'Action'),
                }}
                onView={setForumReportedViewId}
                onAction={(id) => setForumReportedActionId(id)}
              />
            </div>
          );
        }
        if (isForumTagRequests) {
          return (
            <div className="align-right">
              <AdminForumTagRequestActions
                requestId={Number(row.ftagreq_id ?? row.id)}
                status={Number(row.status ?? 0)}
                canEdit={canEdit}
                labels={{
                  changeStatus: lbl('LBL_Change_Status', 'Change status'),
                  na: lbl('ERR_NA', 'N/A'),
                }}
                onChangeStatus={(id) => setForumTagRequestId(id)}
              />
            </div>
          );
        }
        return (
          <div className="actions">
            <a className="btn btn--bordered btn--small" href={`#view-${row.id}`}>
              {lbl('LBL_VIEW', 'View')}
            </a>
          </div>
        );
      case 'title':
        if (isForumTags && Number(row.deleted) === 1) {
          return (
            <>
              {String(row.title ?? '')}
              <br />
              <span className="text-danger"> {lbl('LBL_Deleted_Record', 'Deleted record')} </span>
            </>
          );
        }
        if (isGroupClasses || isPackageClasses) {
          return String(row.title ?? '');
        }
        if ((isCourseRequests || isCourseEditRequests) && row.course_deleted) {
          return (
            <>
              {String(row.title ?? '')}
              <br />
              <span>[{lbl('LBL_DELETED', 'Deleted')}]</span>
            </>
          );
        }
        if (isQuestions) {
          return String(row.title ?? '');
        }
        if (isQuizzes) {
          return String(row.title ?? '');
        }
        if (isVideoContent) {
          const value = String(row.title ?? '');
          return value.length > 30 ? `${value.slice(0, 30)}...` : value;
        }
        return formatValue(key, row[key]);
      case 'type_label':
        if (isQuestions) {
          return questionTypeLabel(row.type, lbl);
        }
        if (isQuizzes) {
          return quizTypeLabel(row.type, lbl);
        }
        return formatValue(key, row[key]);
      case 'status_label':
        if (isQuizzes) {
          return quizStatusLabel(row.status, lbl);
        }
        if (isReportedIssues) {
          return String(row.status_label ?? '—');
        }
        return formatValue(key, row[key]);
      case 'reported_on':
        if (isReportedIssues) {
          return formatOrderDate(row.reported_on);
        }
        return formatValue(key, row[key]);
      case 'order_id_formatted':
        if (isReportedIssues || isOrderModule) {
          return String(row.order_id_formatted ?? '—');
        }
        return formatValue(key, row[key]);
      case 'repiss_title':
      case 'reporter_name':
      case 'record_type_label':
      case 'repiss_record_id':
        if (isReportedIssues) {
          return String(row[key] ?? '—');
        }
        return formatValue(key, row[key]);
      case 'active_label':
        if (isQuizzes) {
          return Number(row.active) === 1 ? lbl('LBL_YES', 'Yes') : lbl('LBL_NO', 'No');
        }
        return formatValue(key, row[key]);
      case 'duration_label':
      case 'passmark_label':
      case 'questions_count':
      case 'attempts':
        if (isQuizzes) {
          const value = row[key];
          if (key === 'passmark_label' && (value === '-' || value === null || value === undefined || value === '')) {
            return '—';
          }
          return formatValue(key, value);
        }
        return formatValue(key, row[key]);
      case 'category_name':
      case 'subcategory_name':
        if (isQuestions) {
          return String(row[key] ?? '');
        }
        return formatValue(key, row[key]);
      case 'teacher_name':
        if (isQuestions || isQuizzes) {
          return String(row.teacher_name ?? '');
        }
        if (isGroupClasses || isPackageClasses || isCourses || isCourseRequests || isCourseEditRequests) {
          return String(row.teacher_name ?? '');
        }
        return formatValue(key, row[key]);
      case 'class_type_label':
        if (Number(row.parent_id) > 0) {
          return lbl('LBL_PACKAGE', 'Package');
        }
        return Number(row.class_type) === 2
          ? lbl('LBL_PACKAGE', 'Package')
          : lbl('LBL_REGULAR', 'Regular');
      case 'service_type_label':
        if (isOrderModule) {
          return String(row.service_type_label ?? '—');
        }
        return Number(row.offline) === 1
          ? lbl('LBL_OFFLINE', 'Offline')
          : lbl('LBL_ONLINE', 'Online');
      case 'order_addedon':
      case 'order_created':
      case 'ordsub_startdate':
      case 'ordsub_enddate':
      case 'ordsplan_start_date':
      case 'ordsplan_end_date':
        if (isOrderModule) {
          return formatOrderDate(row[key]);
        }
        return formatValue(key, row[key]);
      case 'order_net_amount':
      case 'order_total_amount':
      case 'ordles_net_amount':
      case 'ordcls_net_amount':
      case 'ordcrs_net_amount':
        if (isOrderModule) {
          return formatAdminMoney(row[key]);
        }
        return formatValue(key, row[key]);
      case 'entry_fee':
        return formatAdminMoney(row.entry_fee);
      case 'created_by_label':
        if (row.created_by_label) {
          const label = String(row.created_by_label);
          if (label === 'Admin') {
            return lbl('LBL_ADMIN', 'Admin');
          }
          if (label === 'Teacher') {
            return lbl('LBL_TEACHER', 'Teacher');
          }
          return label;
        }
        return lbl('LBL_TEACHER', 'Teacher');
      case 'customer_name':
        if (isGroupClasses || isPackageClasses || isCourses || isCourseRequests || isCourseEditRequests) {
          return String(row.teacher_name ?? '');
        }
        return formatValue(key, row[key]);
      case 'learner_name':
        if (isCourseRefundRequests) {
          return String(row.learner_name ?? '');
        }
        return formatValue(key, row[key]);
      case 'reason_short':
      case 'reference':
        return formatValue(key, row[key]);
      case 'updated_at':
        if (isGdprRequests) {
          if (Number(row.status) === GDPR_STATUS_PENDING) {
            return lbl('LBL_NA', 'N/A');
          }
          return formatGdprDate(row.updated_at);
        }
        return formatValue(key, row[key]);
      case 'comments':
        return row.comments === null || row.comments === undefined || row.comments === ''
          ? ''
          : String(row.comments);
      case 'category_name':
      case 'subcategory_name':
        if (isCourses && (row[key] === null || row[key] === undefined || row[key] === '')) {
          return lbl('LBL_NA', 'N/A');
        }
        return formatValue(key, row[key]);
      case 'order_id':
        return formatValue(key, row[key]);
      case 'status':
        if (isTeacherRequests) {
          return teacherRequestStatusLabel(row.status, lbl);
        }
        if (isWithdrawRequests) {
          return row.status_label
            ? String(row.status_label)
            : withdrawRequestStatusLabel(row.status, lbl);
        }
        if (isRatingReviews) {
          return row.status_label
            ? String(row.status_label)
            : ratingReviewStatusLabel(row.status, lbl);
        }
        if (isGdprRequests) {
          return row.status_label
            ? String(row.status_label)
            : gdprRequestStatusLabel(row.status, lbl);
        }
        if (isGroupClasses || isPackageClasses) {
          return row.status_label
            ? String(row.status_label)
            : groupClassStatusLabel(row.status, lbl);
        }
        if (isCourseRequests) {
          return courseRequestStatusLabel(row.status, lbl);
        }
        if (isCourseEditRequests) {
          return courseEditRequestStatusLabel(row.status, lbl);
        }
        if (isCourseRefundRequests) {
          return courseRefundRequestStatusLabel(row.status, lbl);
        }
        return formatValue(key, row[key]);
      case 'amount':
        if (isWithdrawRequests) {
          return formatAdminMoney(row.amount);
        }
        return formatValue(key, row[key]);
      case 'payment_status':
      case 'start_at':
      case 'end_at':
        if (isGroupClasses || isPackageClasses) {
          return formatGroupClassDate(row[key]);
        }
        return formatValue(key, row[key]);
      case 'testimonial_text':
        return String(row.testimonial_text ?? '');
      case 'seats':
      case 'price':
      case 'display_order':
        return formatValue(key, row[key]);
      default:
        return formatValue(key, row[key]);
    }
  };

  const breadcrumbTitle = isNavigations
    ? lbl('LBL_NAVIGATIONS', 'Navigations')
    : isCourseReviews
      ? lbl('LBL_COURSE_RATING_REVIEWS', 'Course reviews & ratings')
      : isEscalatedIssues
        ? lbl('LBL_REPORTED_ISSUES', 'Reported issues')
      : lbl(config.titleKey, config.titleFallback);
  const breadcrumbCurrent = isEscalatedIssues
    ? lbl('LBL_ESCALATED', 'Escalated')
    : null;
  const mainClassName = [
    'main',
    isNavigations ? 'admin-navigations-page' : '',
    isCountries ? 'admin-countries-page' : '',
    isStates ? 'admin-states-page' : '',
    isVideoContent ? 'admin-video-content-page' : '',
    isTestimonials ? 'admin-testimonials-page' : '',
  ].filter(Boolean).join(' ');
  const searchFormConfig: AdminModuleConfig = isStates
    ? {
        ...config,
        searchFields: config.searchFields?.map((field) =>
          field.name === 'state_country_id'
            ? { ...field, options: stateSearchCountries }
            : field,
        ),
      }
    : config;

  return (
    <main className={mainClassName}>
      <div className="container">
        <div className="breadcrumb-wrap">
          <ul className="breadcrumb">
            <li className="breadcrumb-item">
              <Link to="/admin">{lbl('LBL_Home', 'Home')}</Link>
            </li>
            <li className="breadcrumb-item">{breadcrumbTitle}</li>
            {breadcrumbCurrent ? <li className="breadcrumb-item">{breadcrumbCurrent}</li> : null}
          </ul>
          {showToolbar ? (
            <div className="action-toolbar">
              {config.creatable && canEdit ? (
                <a href="javascript:void(0)" className="btn btn-primary" onClick={onAddNew}>
                  {lbl(
                    config.createLabelKey ?? 'LBL_ADD_NEW',
                    config.createLabelFallback ?? 'Add new',
                  )}
                </a>
              ) : null}
              {config.importable && canEdit ? (
                <a href="javascript:void(0)" className="btn btn-primary" onClick={() => setLanguageLabelImportOpen(true)}>
                  {lbl('LBL_IMPORT', 'Import')}
                </a>
              ) : null}
              {config.exportable ? (
                <a href="javascript:void(0)" className="btn btn-primary" onClick={onExport}>
                  {lbl('LBL_EXPORT', 'Export')}
                </a>
              ) : null}
            </div>
          ) : null}
        </div>

        {config.searchFields && config.searchFields.length > 0 && !config.hideSearchPanel ? (
          <div className="card">
            <div
              className={`card-head js--filter-trigger${filterOpen ? ' active' : ''}`}
              onClick={() => setFilterOpen((v) => !v)}
            >
              <h4>{lbl('LBL_Search...', 'Search...')}</h4>
            </div>
            <div className="card-body js--filter-target" style={{ display: filterOpen ? 'block' : 'none' }}>
              {config.module === 'users' ? (
                <AdminUsersSearchForm
                  config={config}
                  draft={draft}
                  lbl={lbl}
                  onDraftChange={setDraft}
                  onSearch={onSearch}
                  onClear={onUsersClear}
                />
              ) : isCourses ? (
                <AdminCoursesSearchForm
                  draft={draft}
                  lbl={lbl}
                  onDraftChange={setDraft}
                  onSearch={onSearch}
                  onClear={onCoursesClear}
                />
              ) : isQuestions ? (
                <AdminQuestionsSearchForm
                  draft={draft}
                  lbl={lbl}
                  onDraftChange={setDraft}
                  onSearch={onSearch}
                  onClear={onQuestionsClear}
                />
              ) : isQuizzes ? (
                <AdminQuizzesSearchForm
                  draft={draft}
                  lbl={lbl}
                  onDraftChange={setDraft}
                  onSearch={onSearch}
                  onClear={onClear}
                />
              ) : isUrlRewriting ? (
                <AdminUrlRewritingSearchForm
                  draft={draft}
                  lbl={lbl}
                  onDraftChange={setDraft}
                  onSearch={onSearch}
                  onClear={onClear}
                />
              ) : (
                <AdminModuleSearchForm
                  config={searchFormConfig}
                  draft={draft}
                  lbl={lbl}
                  onDraftChange={setDraft}
                  onSearch={onSearch}
                  onClear={isReportedIssues ? onReportedIssuesClear : isTeacherPrefModule ? onTeacherPrefClear : onClear}
                />
              )}
            </div>
          </div>
        ) : null}

        {isContentBlocks ? (
          <div className="grid-layout">
            <div className="grid-layout-left">
              <ContentBlockTypeTabs
                activeType={contentBlockType}
                lbl={lbl}
                onChange={(typeId) => {
                  setContentBlockType(typeId);
                  setPage(1);
                  setFilters((prev) => ({ ...prev, type: String(typeId) }));
                  setDraft((prev) => ({ ...prev, type: String(typeId) }));
                  setSearchParams((prev) => {
                    const next = new URLSearchParams(prev);
                    next.set('type', String(typeId));
                    return next;
                  });
                }}
              />
            </div>
            <div className="grid-layout-right">
              <AdminListingTableCard
                config={config}
                loading={loading}
                tableColumns={tableColumns}
                rows={rows}
                canEdit={canEdit}
                isForumTags={isForumTags}
                isContentBlocks={isContentBlocks}
                isVideoContent={isVideoContent}
                isFaqCategories={isFaqCategories}
                contentBlockType={contentBlockType}
                pendingMessage={pendingMessage}
                lbl={lbl}
                renderCell={renderCell}
                onPreferenceDrop={onPreferenceDrop}
                onContentBlockDrop={onContentBlockDrop}
                onVideoContentDrop={onVideoContentDrop}
                onFaqCategoryDrop={onFaqCategoryDrop}
              />
            </div>
          </div>
        ) : (
        <AdminListingTableCard
          config={config}
          className={
            isNavigations
              ? 'admin-navigations-table-card'
              : isCountries
                ? 'admin-countries-table-card'
                : isStates
                  ? 'admin-states-table-card'
                  : isVideoContent
                    ? 'admin-video-content-table-card'
                    : isTestimonials
                      ? 'admin-testimonials-table-card'
                    : undefined
          }
          loading={loading}
          tableColumns={tableColumns}
          rows={rows}
          canEdit={canEdit}
          isForumTags={isForumTags}
          isContentBlocks={isContentBlocks}
          isVideoContent={isVideoContent}
          isFaqCategories={isFaqCategories}
          contentBlockType={contentBlockType}
          pendingMessage={pendingMessage}
          lbl={lbl}
          renderCell={renderCell}
          onPreferenceDrop={onPreferenceDrop}
          onContentBlockDrop={onContentBlockDrop}
          onVideoContentDrop={onVideoContentDrop}
          onFaqCategoryDrop={onFaqCategoryDrop}
        />
        )}

        {false ? <div className="card">
          <div className="card-table">
            <div className="table-responsive" id={config.module === 'users' ? 'userListing' : undefined}>
              {loading ? (
                <div className="table-processing loaderJs">
                  <div className="spinner spinner--sm spinner--brand" />
                </div>
              ) : (
                <table className={`table${config.tableClassName ? ` ${config.tableClassName}` : ''}`} width="100%">
                  <thead>
                    <tr>
                      {tableColumns.map((col) => (
                        <th key={col.key} className={col.align === 'right' ? 'align-right' : undefined}>
                          {col.key === 'dragdrop' ? (
                            <i className="ion-arrow-move icon" />
                          ) : (
                            lbl(col.labelKey, col.labelFallback)
                          )}
                        </th>
                      ))}
                    </tr>
                  </thead>
                  <tbody>
                    {rows.length === 0 ? (
                      <tr>
                        <td colSpan={tableColumns.length} className="admin-empty-state-cell">
                          <AdminNoRecords
                            title={pendingMessage || lbl('LBL_NO_RESULTS_FOUND', 'No results found')}
                            message={lbl(
                              'LBL_NO_RESULTS_FOUND_HELP_TEXT',
                              "We couldn't find any records matching your search. Try adjusting your filters or search terms.",
                            )}
                          />
                        </td>
                      </tr>
                    ) : (
                      rows.map((row, index) => (
                        <tr
                          key={String(row.id ?? index)}
                          id={(config.showDragHandle || (isContentBlocks && contentBlockType === 1)) && canEdit ? String(row.id) : undefined}
                          className={isForumTags && Number(row.deleted) === 1 ? 'disabled' : undefined}
                          onDragOver={
                            (config.showDragHandle || (isContentBlocks && contentBlockType === 1)) && canEdit
                              ? (e) => {
                                  e.preventDefault();
                                }
                              : undefined
                          }
                          onDrop={
                            (config.showDragHandle || (isContentBlocks && contentBlockType === 1)) && canEdit
                              ? (e) => {
                                  e.preventDefault();
                                  if (isContentBlocks) {
                                    onContentBlockDrop(Number(row.id));
                                  } else {
                                    onPreferenceDrop(Number(row.id));
                                  }
                                }
                              : undefined
                          }
                        >
                          {tableColumns.map((col) => (
                            <td
                              key={col.key}
                              className={
                                col.key === 'dragdrop'
                                  ? 'dragHandle'
                                  : col.align === 'right'
                                    ? 'align-right'
                                    : undefined
                              }
                            >
                              {renderCell(col.key, row, index)}
                            </td>
                          ))}
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              )}
              {usesLegacyPagination && !config.hidePagination ? (
                <AdminLegacyPagination
                  page={page}
                  lastPage={pagination.last_page}
                  perPage={perPage}
                  total={pagination.total}
                  onPageChange={setPage}
                  labels={{
                    showing: lbl('LBL_Showing', 'Showing'),
                    to: lbl('LBL_to', 'to'),
                    of: lbl('LBL_of', 'of'),
                    entries: lbl('LBL_Entries', 'Entries'),
                  }}
                />
              ) : null}
            </div>
          </div>
        </div> : null}

        {!usesLegacyPagination && pagination.last_page > 1 ? (
          <div className="pagination-wrap">
            <ul className="pagination">
              <li className={page <= 1 ? 'disabled' : ''}>
                <button type="button" className="btn btn--bordered btn--small" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
                  {lbl('LBL_PREVIOUS', 'Previous')}
                </button>
              </li>
              <li>
                <span className="px-3">
                  {page} / {pagination.last_page}
                </span>
              </li>
              <li className={page >= pagination.last_page ? 'disabled' : ''}>
                <button
                  type="button"
                  className="btn btn--secondary btn--small"
                  disabled={page >= pagination.last_page}
                  onClick={() => setPage((p) => p + 1)}
                >
                  {lbl('LBL_NEXT', 'Next')}
                </button>
              </li>
            </ul>
          </div>
        ) : null}

        {config.module === 'users' ? (
          <AdminUserModals
            active={userModal}
            canEdit={canEdit}
            onClose={() => setUserModal(null)}
            onOpen={openUserModal}
            onUpdated={load}
          />
        ) : null}

        {isTeacherRequests ? (
          <AdminTeacherRequestModals
            active={teacherRequestModal}
            onClose={() => setTeacherRequestModal(null)}
            onUpdated={load}
          />
        ) : null}

        {isRatingReviews ? (
          <AdminRatingReviewModal
            reviewId={ratingReviewId}
            isCourseReviews={isCourseReviews}
            fallbackCourseName={ratingReviewCourseName}
            onClose={() => {
              setRatingReviewId(null);
              setRatingReviewCourseName('');
            }}
            onUpdated={load}
          />
        ) : null}

        {isGdprRequests ? (
          <AdminGdprRequestModal
            requestId={gdprRequestId}
            onClose={() => setGdprRequestId(null)}
            onUpdated={load}
          />
        ) : null}

        {isAdminUsers ? (
          <AdminManageAdminModals
            active={manageAdminModal}
            onClose={() => setManageAdminModal(null)}
            onUpdated={load}
          />
        ) : null}

        {isGroupClasses || isPackageClasses ? (
          <>
            <AdminGroupClassModal
              open={groupClassModalOpen}
              classType={groupClassModalType}
              onClose={() => setGroupClassModalOpen(false)}
              onSaved={reloadSilently}
            />
            <AdminGroupClassViewModal
              classId={groupClassViewId}
              onClose={() => setGroupClassViewId(null)}
            />
          </>
        ) : null}

        {isCourseRequests ? (
          <AdminCourseRequestModals
            active={courseRequestModal}
            onClose={() => setCourseRequestModal(null)}
            onUpdated={load}
          />
        ) : null}

        {isCourseEditRequests ? (
          <AdminCourseEditRequestModals
            active={courseEditRequestModal}
            onClose={() => setCourseEditRequestModal(null)}
            onUpdated={load}
          />
        ) : null}

        {isCourseRefundRequests ? (
          <AdminCourseRefundRequestModals
            active={courseRefundRequestModal}
            onClose={() => setCourseRefundRequestModal(null)}
            onUpdated={load}
          />
        ) : null}

        {isCourses ? (
          <AdminCourseViewModal courseId={courseViewId} onClose={() => setCourseViewId(null)} />
        ) : null}

        {isQuestions ? (
          <AdminQuestionViewModal questionId={questionViewId} onClose={() => setQuestionViewId(null)} />
        ) : null}

        {isQuizzes ? (
          <AdminQuizViewModal quizId={quizViewId} onClose={() => setQuizViewId(null)} />
        ) : null}
        {isOrderModule ? (
          <AdminSubOrderViewModal
            module={subOrderView?.module ?? null}
            row={subOrderView?.row ?? null}
            onClose={() => setSubOrderView(null)}
            lbl={lbl}
          />
        ) : null}
        {isReportedIssues ? (
          <AdminReportedIssueViewModal
            row={reportedIssueView}
            onClose={() => setReportedIssueView(null)}
          />
        ) : null}
        {isPreferences ? (
          <AdminPreferenceModal
            preferId={preferenceModalId}
            preferType={preferenceType}
            onClose={() => setPreferenceModalId(null)}
            onSaved={reloadSilently}
          />
        ) : null}
        {isForumQuestions ? (
          <AdminForumQuestionViewModal
            questionId={forumQuestionViewId}
            onClose={() => setForumQuestionViewId(null)}
          />
        ) : null}
        {isForumTags ? (
          <AdminForumTagModal
            open={forumTagModalOpen}
            tagId={forumTagId}
            onClose={() => setForumTagModalOpen(false)}
            onSaved={reloadSilently}
          />
        ) : null}
        {isUrlRewriting ? (
          <AdminUrlRewritingModal
            open={urlRewritingModalOpen}
            seoUrlId={urlRewritingId}
            onClose={() => setUrlRewritingModalOpen(false)}
            onSaved={reloadSilently}
          />
        ) : null}
        {isContentBlocks ? (
          <AdminContentBlockModal
            open={contentBlockModalOpen}
            blockId={contentBlockId}
            onClose={() => setContentBlockModalOpen(false)}
            onSaved={reloadSilently}
          />
        ) : null}
        {isContentPages ? (
          <AdminContentPageModal
            open={contentPageModalOpen}
            pageId={contentPageId}
            onClose={() => setContentPageModalOpen(false)}
            onSaved={reloadSilently}
          />
        ) : null}
        {isCountries ? (
          <AdminCountryModal
            open={countryModalOpen}
            countryId={countryId}
            onClose={() => {
              setCountryModalOpen(false);
              setCountryId(0);
            }}
            onSaved={reloadSilently}
          />
        ) : null}
        {isStates ? (
          <AdminStateModal
            open={stateModalOpen}
            stateId={stateId}
            onClose={() => {
              setStateModalOpen(false);
              setStateId(0);
            }}
            onSaved={reloadSilently}
          />
        ) : null}
        {isVideoContent ? (
          <AdminVideoContentModal
            open={videoContentModalOpen}
            contentId={videoContentId}
            onClose={() => {
              setVideoContentModalOpen(false);
              setVideoContentId(0);
            }}
            onSaved={reloadSilently}
          />
        ) : null}
        {isTestimonials ? (
          <AdminTestimonialModal
            open={testimonialModalOpen}
            testimonialId={testimonialId}
            onClose={() => {
              setTestimonialModalOpen(false);
              setTestimonialId(0);
            }}
            onSaved={reloadSilently}
          />
        ) : null}
        {isFaqCategories ? (
          <AdminFaqCategoryModal
            open={faqCategoryModalOpen}
            categoryId={faqCategoryId}
            onClose={() => {
              setFaqCategoryModalOpen(false);
              setFaqCategoryId(0);
            }}
            onSaved={reloadSilently}
          />
        ) : null}
        {isFaq ? (
          <AdminFaqModal
            open={faqModalOpen}
            faqId={faqId}
            onClose={() => {
              setFaqModalOpen(false);
              setFaqId(0);
            }}
            onSaved={reloadSilently}
          />
        ) : null}
        {isLanguageLabels ? (
          <AdminLanguageLabelModal
            labelId={languageLabelId}
            importOpen={languageLabelImportOpen}
            onClose={() => {
              setLanguageLabelId(0);
              setLanguageLabelImportOpen(false);
            }}
            onSaved={reloadSilently}
          />
        ) : null}
        {isEmailTemplates ? (
          <AdminEmailTemplateModal
            open={emailTemplateModalOpen}
            code={emailTemplateCode}
            langId={emailTemplateLangId}
            onClose={() => {
              setEmailTemplateModalOpen(false);
              setEmailTemplateCode('');
              setEmailTemplateLangId(1);
            }}
            onSaved={reloadSilently}
          />
        ) : null}
        {isAbusiveWords ? (
          <AdminAbusiveWordModal
            open={abusiveWordModalOpen}
            wordId={abusiveWordId}
            onClose={() => {
              setAbusiveWordModalOpen(false);
              setAbusiveWordId(0);
            }}
            onSaved={reloadSilently}
          />
        ) : null}
        {isCertificates ? (
          <AdminCertificateModal
            open={certificateModalOpen}
            code={certificateCode}
            langId={certificateLangId}
            onClose={() => {
              setCertificateModalOpen(false);
              setCertificateCode('');
              setCertificateLangId(1);
            }}
            onSaved={reloadSilently}
          />
        ) : null}
        {isNavigations ? (
          <AdminNavigationModal
            open={navigationModalOpen}
            navigationId={navigationId}
            onClose={() => setNavigationModalOpen(false)}
            onSaved={reloadSilently}
          />
        ) : null}
        {isForumReportedQuestions ? (
          <>
            <AdminForumReportedQuestionViewModal
              reportId={forumReportedViewId}
              onClose={() => setForumReportedViewId(null)}
            />
            <AdminForumReportedQuestionActionModal
              open={forumReportedActionId > 0}
              reportId={forumReportedActionId}
              onClose={() => setForumReportedActionId(0)}
              onSaved={reloadSilently}
            />
          </>
        ) : null}
        {isForumTagRequests ? (
          <AdminForumTagRequestStatusModal
            open={forumTagRequestId > 0}
            requestId={forumTagRequestId}
            onClose={() => setForumTagRequestId(0)}
            onSaved={reloadSilently}
          />
        ) : null}
        {isPageLangData && pageLangModalOpen && pageLangEditId > 0 ? (
          <AdminPageLangDataModal
            open={pageLangModalOpen}
            plangId={pageLangEditId}
            langId={pageLangEditLangId}
            onClose={() => {
              setPageLangModalOpen(false);
              setPageLangEditId(0);
            }}
            onSaved={reloadSilently}
          />
        ) : null}
      </div>
    </main>
  );
}

function ContentBlockTypeTabs({
  activeType,
  lbl,
  onChange,
}: {
  activeType: number;
  lbl: (key: string, fallback: string) => string;
  onChange: (typeId: number) => void;
}) {
  return (
    <div className="card card-sticky">
      <div className="tab tab-vertical tabs-nav-js">
        <ul className="tabs-nav-js blocksTabJs">
          {Object.entries(CONTENT_BLOCK_TYPES).map(([type, label]) => {
            const typeId = Number(type);
            return (
              <li key={type}>
                <a
                  className={activeType === typeId ? 'active' : ''}
                  data-type={typeId}
                  href="javascript:void(0)"
                  onClick={(event) => {
                    event.preventDefault();
                    onChange(typeId);
                  }}
                >
                  {lbl(label.key, label.fallback)}
                </a>
              </li>
            );
          })}
        </ul>
      </div>
    </div>
  );
}

function AdminListingTableCard({
  config,
  className,
  loading,
  tableColumns,
  rows,
  canEdit,
  isForumTags,
  isContentBlocks,
  isVideoContent,
  isFaqCategories,
  contentBlockType,
  pendingMessage,
  lbl,
  renderCell,
  onPreferenceDrop,
  onContentBlockDrop,
  onVideoContentDrop,
  onFaqCategoryDrop,
}: {
  config: AdminModuleConfig;
  className?: string;
  loading: boolean;
  tableColumns: AdminModuleConfig['columns'];
  rows: Record<string, unknown>[];
  canEdit: boolean;
  isForumTags: boolean;
  isContentBlocks: boolean;
  isVideoContent: boolean;
  isFaqCategories: boolean;
  contentBlockType: number;
  pendingMessage: string;
  lbl: (key: string, fallback: string) => string;
  renderCell: (key: string, row: Record<string, unknown>, index: number) => ReactNode;
  onPreferenceDrop: (targetId: number) => void;
  onContentBlockDrop: (targetId: number) => void;
  onVideoContentDrop: (targetId: number) => void;
  onFaqCategoryDrop: (targetId: number) => void;
}) {
  const draggableRows = (config.showDragHandle || isVideoContent || isFaqCategories || (isContentBlocks && contentBlockType === 1)) && canEdit;

  return (
    <div className={`card${className ? ` ${className}` : ''}`}>
      <div className="card-table">
        <div className="table-responsive" id={config.module === 'users' ? 'userListing' : undefined}>
          {loading ? (
            <div className="table-processing loaderJs">
              <div className="spinner spinner--sm spinner--brand" />
            </div>
          ) : (
            <table className={`table${config.tableClassName ? ` ${config.tableClassName}` : ''}`} width="100%">
              <thead>
                <tr>
                  {tableColumns.map((col) => (
                    <th key={col.key} className={col.align === 'right' ? 'align-right' : undefined}>
                      {col.key === 'dragdrop' ? <i className="ion-arrow-move icon" /> : lbl(col.labelKey, col.labelFallback)}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {rows.length === 0 ? (
                  <tr>
                    <td colSpan={tableColumns.length} className="admin-empty-state-cell">
                      <AdminNoRecords
                        title={pendingMessage || lbl('LBL_NO_RESULTS_FOUND', 'No results found')}
                        message={lbl(
                          'LBL_NO_RESULTS_FOUND_HELP_TEXT',
                          "We couldn't find any records matching your search. Try adjusting your filters or search terms.",
                        )}
                      />
                    </td>
                  </tr>
                ) : (
                  rows.map((row, index) => (
                    <tr
                      key={String(row.id ?? index)}
                      id={draggableRows ? String(row.id) : undefined}
                      className={
                        isForumTags && Number(row.deleted) === 1
                          ? 'disabled'
                          : isVideoContent && Number(row.active) !== 1
                            ? 'inactive'
                            : isFaqCategories && Number(row.active) !== 1
                              ? 'nodrag nodrop'
                            : undefined
                      }
                      onDragOver={
                        draggableRows
                          ? (e) => {
                              e.preventDefault();
                            }
                          : undefined
                      }
                      onDrop={
                        draggableRows
                          ? (e) => {
                              e.preventDefault();
                              if (isContentBlocks) {
                                onContentBlockDrop(Number(row.id));
                              } else if (isVideoContent) {
                                onVideoContentDrop(Number(row.id));
                              } else if (isFaqCategories) {
                                onFaqCategoryDrop(Number(row.id));
                              } else {
                                onPreferenceDrop(Number(row.id));
                              }
                            }
                          : undefined
                      }
                    >
                      {tableColumns.map((col) => (
                        <td
                          key={col.key}
                          className={
                            col.key === 'dragdrop'
                              ? 'dragHandle'
                              : col.align === 'right'
                                ? 'align-right'
                                : undefined
                          }
                        >
                          {renderCell(col.key, row, index)}
                        </td>
                      ))}
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </div>
  );
}

