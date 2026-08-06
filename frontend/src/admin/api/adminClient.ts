import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || '/api/v1';

export const adminApiClient = axios.create({
  baseURL: API_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

adminApiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('admin_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

adminApiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const url = String(error.config?.url ?? '');
      const isLogin = url.includes('/admin/auth/login');
      if (!isLogin && localStorage.getItem('admin_token')) {
        localStorage.removeItem('admin_token');
        localStorage.removeItem('admin_user');
        window.dispatchEvent(new Event('admin:cleared'));
      }
    }
    return Promise.reject(error);
  }
);

export type AdminUser = {
  id: number;
  username: string;
  name: string;
  email: string;
};

export type AdminNavChild = {
  labelKey: string;
  labelFallback?: string;
  path?: string;
  external?: boolean;
  action?: 'generate-sitemap';
};

export type AdminNavItem =
  | { type: 'link'; labelKey: string; labelFallback?: string; icon: string; path: string }
  | {
      type: 'dropdown';
      id: string;
      labelKey: string;
      labelFallback?: string;
      icon: string;
      children: AdminNavChild[];
    };

export type AdminBootstrap = {
  admin: AdminUser;
  navigation: AdminNavItem[];
  privileges: Record<string, boolean>;
  features: {
    courses_enabled: boolean;
    group_classes_enabled: boolean;
    subscription_plan_enabled: boolean;
    affiliate_enabled: boolean;
  };
  report_generated_at?: string | null;
};

export type PaginatedMeta = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
};

export const adminApi = {
  login(username: string, password: string) {
    return adminApiClient.post<{ token: string; admin: AdminUser }>('/admin/auth/login', {
      username,
      password,
    });
  },
  logout() {
    return adminApiClient.post('/admin/auth/logout');
  },
  me() {
    return adminApiClient.get<{ admin: AdminUser }>('/admin/auth/me');
  },
  profile() {
    return adminApiClient.get<{
      data: {
        id: number;
        username: string;
        full_name: string;
        email: string;
        timezone: string;
      };
      default_timezone: string;
      timezones: Array<{ id: string; label: string }>;
    }>('/admin/auth/profile');
  },
  updateProfile(payload: Record<string, unknown>) {
    return adminApiClient.put<{ message: string; admin: AdminUser }>('/admin/auth/profile', payload);
  },
  uploadProfileImage(file: Blob) {
    const formData = new FormData();
    formData.append('user_profile_image', file, 'profile.jpg');
    return adminApiClient.post<{ message: string; file: string }>('/admin/auth/profile/image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  removeProfileImage() {
    return adminApiClient.delete<{ message: string }>('/admin/auth/profile/image');
  },
  updatePassword(payload: { current_password: string; new_password: string; conf_new_password: string }) {
    return adminApiClient.put<{ message: string }>('/admin/auth/password', payload);
  },
  bootstrap() {
    return adminApiClient.get<AdminBootstrap>('/admin/bootstrap');
  },
  dashboard() {
    return adminApiClient.get<{
      stats: Record<string, number>;
      features: AdminBootstrap['features'];
      page_text?: {
        plang_id?: number;
        title?: string;
        summary?: string;
        warning?: string;
        recommendations?: string;
      };
    }>('/admin/dashboard');
  },
  dashboardCharts() {
    return adminApiClient.get<{
      userData: Record<string, number>;
      lessonData: Record<string, number>;
      classData: Record<string, number>;
      courseData: Record<string, number>;
    }>('/admin/dashboard/charts');
  },
  dashboardTopLessonLanguages(interval = 9) {
    return adminApiClient.get<{ data: Array<{ language: string; totalsold: number }> }>(
      '/admin/dashboard/top-lesson-languages',
      { params: { interval } }
    );
  },
  dashboardTopClassLanguages(interval = 9) {
    return adminApiClient.get<{ data: Array<{ language: string; totalsold: number }> }>(
      '/admin/dashboard/top-class-languages',
      { params: { interval } }
    );
  },
  dashboardTopCourseCategories(interval = 9) {
    return adminApiClient.get<{ data: Array<{ category: string; totalsold: number }> }>(
      '/admin/dashboard/top-course-categories',
      { params: { interval } }
    );
  },
  dashboardAnalyticsEvents(interval = 9) {
    return adminApiClient.get<{ error: boolean; message?: string; data?: Record<string, number> }>(
      '/admin/dashboard/analytics-events',
      { params: { interval } }
    );
  },
  dashboardAnalyticsTraffic(interval = 9) {
    return adminApiClient.get<{ error: boolean; message?: string; data?: Record<string, number> }>(
      '/admin/dashboard/analytics-traffic',
      { params: { interval } }
    );
  },
  moduleList(module: string, params?: Record<string, unknown>) {
    return adminApiClient.get<{ data: Record<string, unknown>[]; meta: PaginatedMeta; message?: string }>(
      `/admin/modules/${module}`,
      { params }
    );
  },
  reportedIssueDetail(issueId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/reported-issues/${issueId}`);
  },
  contentPageCreateForm() {
    return adminApiClient.get<{
      data: {
        layouts: Record<string, string>;
        site_languages: Array<{ id: number; name: string }>;
      };
    }>('/admin/content-pages/create-form');
  },
  contentPageShow(pageId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/content-pages/${pageId}`);
  },
  contentPageCreate(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data: { page_id: number; next_lang_id: number }; message: string }>(
      '/admin/content-pages',
      payload,
    );
  },
  contentPageUpdate(pageId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { page_id: number; next_lang_id: number }; message: string }>(
      `/admin/content-pages/${pageId}`,
      payload,
    );
  },
  contentPageLangForm(pageId: number, langId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/content-pages/${pageId}/lang/${langId}`,
    );
  },
  contentPageLangUpdate(pageId: number, langId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { page_id: number; next_lang_id: number }; message: string }>(
      `/admin/content-pages/${pageId}/lang/${langId}`,
      payload,
    );
  },
  contentPageBackgroundUpload(pageId: number, langId: number, payload: FormData) {
    return adminApiClient.post<{
      data?: { page_id?: number; lang_id?: number; bg_image?: Record<string, unknown> | null };
      message: string;
    }>(`/admin/content-pages/${pageId}/lang/${langId}/background-image`, payload);
  },
  contentPageBackgroundDelete(pageId: number, langId: number) {
    return adminApiClient.delete<{ message: string }>(
      `/admin/content-pages/${pageId}/lang/${langId}/background-image`,
    );
  },
  deleteContentPage(pageId: number) {
    return adminApiClient.delete<{ message: string }>(`/admin/content-pages/${pageId}`);
  },
  contentBlockTypes() {
    return adminApiClient.get<{ data: { types: Record<string, string> } }>('/admin/content-block/types');
  },
  contentBlockShow(blockId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/content-block/${blockId}`);
  },
  contentBlockUpdate(blockId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { epage_id: number; next_lang_id: number }; message: string }>(
      `/admin/content-block/${blockId}`,
      payload,
    );
  },
  contentBlockLangForm(blockId: number, langId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/content-block/${blockId}/lang/${langId}`,
    );
  },
  contentBlockLangUpdate(blockId: number, langId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { epage_id: number; next_lang_id: number }; message: string }>(
      `/admin/content-block/${blockId}/lang/${langId}`,
      payload,
    );
  },
  updateContentBlockStatus(blockId: number, active: boolean) {
    return adminApiClient.patch<{ message: string }>(`/admin/content-block/${blockId}/status`, {
      active,
    });
  },
  updateContentBlockOrder(ids: number[]) {
    return adminApiClient.put<{ message: string }>('/admin/content-block/order', { ids });
  },
  updateNavigationStatus(navigationId: number, active: boolean) {
    return adminApiClient.patch<{ message: string }>(`/admin/navigations/${navigationId}/status`, {
      active,
    });
  },
  navigationShow(navigationId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/navigations/${navigationId}`);
  },
  navigationPages(navigationId: number, langId = 1) {
    return adminApiClient.get<{
      data: {
        navigation: Record<string, unknown>;
        links: Record<string, unknown>[];
      };
    }>(`/admin/navigations/${navigationId}/pages`, { params: { lang_id: langId } });
  },
  navigationUpdate(navigationId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { nav_id: number; next_lang_id: number }; message: string }>(
      `/admin/navigations/${navigationId}`,
      payload,
    );
  },
  navigationLangForm(navigationId: number, langId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/navigations/${navigationId}/lang/${langId}`);
  },
  navigationLangUpdate(navigationId: number, langId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { nav_id: number; next_lang_id: number }; message: string }>(
      `/admin/navigations/${navigationId}/lang/${langId}`,
      payload,
    );
  },
  updateNavigationLinkStatus(linkId: number, active: boolean) {
    return adminApiClient.patch<{ message: string }>(`/admin/navigation-links/${linkId}/status`, {
      active,
    });
  },
  deleteNavigationLink(linkId: number) {
    return adminApiClient.delete<{ message: string }>(`/admin/navigation-links/${linkId}`);
  },
  navigationLinkForm(navigationId: number, linkId: number, langId = 1) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/navigations/${navigationId}/links/${linkId}`,
      { params: { lang_id: langId } },
    );
  },
  navigationLinkSave(navigationId: number, linkId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { id?: number; nlink_id?: number; next_lang_id: number }; message: string }>(
      `/admin/navigations/${navigationId}/links/${linkId}`,
      payload,
    );
  },
  navigationLinkLangForm(navigationId: number, linkId: number, langId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/navigations/${navigationId}/links/${linkId}/lang/${langId}`,
    );
  },
  navigationLinkLangSave(navigationId: number, linkId: number, langId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { id?: number; nlink_id?: number; next_lang_id: number }; message: string }>(
      `/admin/navigations/${navigationId}/links/${linkId}/lang/${langId}`,
      payload,
    );
  },
  updateNavigationLinkOrder(navigationId: number, ids: number[]) {
    return adminApiClient.put<{ message: string }>(`/admin/navigations/${navigationId}/pages/order`, { ids });
  },
  updateCountryStatus(countryId: number, active: boolean) {
    return adminApiClient.patch<{ message: string }>(`/admin/countries/${countryId}/status`, {
      active,
    });
  },
  countryShow(countryId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/countries/${countryId}`);
  },
  countryUpdate(countryId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { country_id: number; next_lang_id: number }; message: string }>(
      `/admin/countries/${countryId}`,
      payload,
    );
  },
  countryLangForm(countryId: number, langId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/countries/${countryId}/lang/${langId}`);
  },
  countryLangUpdate(countryId: number, langId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { country_id: number; next_lang_id: number }; message: string }>(
      `/admin/countries/${countryId}/lang/${langId}`,
      payload,
    );
  },
  stateShow(stateId: number, langId = 1) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/states/${stateId}`, {
      params: { lang_id: langId },
    });
  },
  stateSearchForm(langId = 1) {
    return adminApiClient.get<{ data: Record<string, unknown> }>('/admin/states/search-form', {
      params: { lang_id: langId },
    });
  },
  stateUpdate(stateId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { state_id: number; next_lang_id: number }; message: string }>(
      `/admin/states/${stateId}`,
      payload,
    );
  },
  updateStateStatus(stateId: number, active: boolean) {
    return adminApiClient.patch<{ message: string }>(`/admin/states/${stateId}/status`, {
      active,
    });
  },
  stateLangForm(stateId: number, langId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/states/${stateId}/lang/${langId}`);
  },
  stateLangUpdate(stateId: number, langId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { state_id: number; next_lang_id: number }; message: string }>(
      `/admin/states/${stateId}/lang/${langId}`,
      payload,
    );
  },
  videoContentShow(contentId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/video-content/${contentId}`);
  },
  videoContentUpdate(contentId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { biblecontent_id: number; next_lang_id: number }; message: string }>(
      `/admin/video-content/${contentId}`,
      payload,
    );
  },
  updateVideoContentStatus(contentId: number, active: boolean) {
    return adminApiClient.patch<{ message: string }>(`/admin/video-content/${contentId}/status`, {
      active,
    });
  },
  deleteVideoContent(contentId: number) {
    return adminApiClient.delete<{ message: string }>(`/admin/video-content/${contentId}`);
  },
  videoContentLangForm(contentId: number, langId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/video-content/${contentId}/lang/${langId}`);
  },
  videoContentLangUpdate(contentId: number, langId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { biblecontent_id: number; next_lang_id: number }; message: string }>(
      `/admin/video-content/${contentId}/lang/${langId}`,
      payload,
    );
  },
  updateVideoContentOrder(ids: number[]) {
    return adminApiClient.put<{ message: string }>('/admin/video-content/order', { ids });
  },
  testimonialShow(testimonialId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/testimonials/${testimonialId}`);
  },
  testimonialUpdate(testimonialId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { testimonial_id: number; next_lang_id: number }; message: string }>(
      `/admin/testimonials/${testimonialId}`,
      payload,
    );
  },
  updateTestimonialStatus(testimonialId: number, active: boolean) {
    return adminApiClient.patch<{ message: string }>(`/admin/testimonials/${testimonialId}/status`, {
      active,
    });
  },
  deleteTestimonial(testimonialId: number) {
    return adminApiClient.delete<{ message: string }>(`/admin/testimonials/${testimonialId}`);
  },
  testimonialLangForm(testimonialId: number, langId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/testimonials/${testimonialId}/lang/${langId}`);
  },
  testimonialLangUpdate(testimonialId: number, langId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { testimonial_id: number; next_lang_id: number }; message: string }>(
      `/admin/testimonials/${testimonialId}/lang/${langId}`,
      payload,
    );
  },
  testimonialMedia(testimonialId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/testimonials/${testimonialId}/media`);
  },
  uploadTestimonialMedia(testimonialId: number, file: File) {
    const formData = new FormData();
    formData.append('file', file);
    return adminApiClient.post<{ message: string }>(`/admin/testimonials/${testimonialId}/media`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  removeTestimonialMedia(testimonialId: number) {
    return adminApiClient.delete<{ message: string }>(`/admin/testimonials/${testimonialId}/media`);
  },
  faqCategoryShow(categoryId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/faq-categories/${categoryId}`);
  },
  faqCategoryUpdate(categoryId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { faqcat_id: number; next_lang_id: number }; message: string }>(
      `/admin/faq-categories/${categoryId}`,
      payload,
    );
  },
  updateFaqCategoryStatus(categoryId: number, active: boolean) {
    return adminApiClient.patch<{ message: string }>(`/admin/faq-categories/${categoryId}/status`, {
      active,
    });
  },
  deleteFaqCategory(categoryId: number) {
    return adminApiClient.delete<{ message: string }>(`/admin/faq-categories/${categoryId}`);
  },
  faqCategoryLangForm(categoryId: number, langId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/faq-categories/${categoryId}/lang/${langId}`);
  },
  faqCategoryLangUpdate(categoryId: number, langId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { faqcat_id: number; next_lang_id: number }; message: string }>(
      `/admin/faq-categories/${categoryId}/lang/${langId}`,
      payload,
    );
  },
  updateFaqCategoryOrder(ids: number[]) {
    return adminApiClient.put<{ message: string }>('/admin/faq-categories/order', { ids });
  },
  exportFaqCategories(params?: Record<string, unknown>) {
    const token = localStorage.getItem('admin_token');
    return fetch(`${API_URL}/admin/faq-categories/export?${new URLSearchParams(params as Record<string, string>).toString()}`, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
  },
  faqShow(faqId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/faq/${faqId}`);
  },
  faqUpdate(faqId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { faq_id: number; next_lang_id: number }; message: string }>(
      `/admin/faq/${faqId}`,
      payload,
    );
  },
  updateFaqStatus(faqId: number, active: boolean) {
    return adminApiClient.patch<{ message: string }>(`/admin/faq/${faqId}/status`, {
      active,
    });
  },
  deleteFaq(faqId: number) {
    return adminApiClient.delete<{ message: string }>(`/admin/faq/${faqId}`);
  },
  faqLangForm(faqId: number, langId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/faq/${faqId}/lang/${langId}`);
  },
  faqLangUpdate(faqId: number, langId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ data: { faq_id: number; next_lang_id: number }; message: string }>(
      `/admin/faq/${faqId}/lang/${langId}`,
      payload,
    );
  },
  exportFaq(params?: Record<string, unknown>) {
    const token = localStorage.getItem('admin_token');
    return fetch(`${API_URL}/admin/faq/export?${new URLSearchParams(params as Record<string, string>).toString()}`, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
  },
  languageLabelShow(labelId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/label/${labelId}`);
  },
  languageLabelUpdate(labelId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ message: string }>(`/admin/label/${labelId}`, payload);
  },
  exportLanguageLabels(params?: Record<string, unknown>) {
    const token = localStorage.getItem('admin_token');
    return fetch(`${API_URL}/admin/label/export?${new URLSearchParams(params as Record<string, string>).toString()}`, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
  },
  emailTemplateLangForm(code: string, langId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/email-templates/${encodeURIComponent(code)}/lang/${langId}`,
    );
  },
  emailTemplateLangUpdate(code: string, langId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ message: string }>(
      `/admin/email-templates/${encodeURIComponent(code)}/lang/${langId}`,
      payload,
    );
  },
  updateEmailTemplateStatus(code: string, active: boolean) {
    return adminApiClient.patch<{ message: string }>(
      `/admin/email-templates/${encodeURIComponent(code)}/status`,
      { active },
    );
  },
  previewEmailTemplate(code: string, langId: number) {
    return adminApiClient.get<string>(
      `/admin/email-templates/${encodeURIComponent(code)}/preview/${langId}`,
      { responseType: 'text' },
    );
  },
  exportEmailTemplates(params?: Record<string, unknown>) {
    const token = localStorage.getItem('admin_token');
    return fetch(`${API_URL}/admin/email-templates/export?${new URLSearchParams(params as Record<string, string>).toString()}`, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
  },
  abusiveWordShow(wordId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/abusive-words/${wordId}`);
  },
  abusiveWordSave(wordId: number, payload: Record<string, unknown>) {
    const path = wordId > 0 ? `/admin/abusive-words/${wordId}` : '/admin/abusive-words';
    return wordId > 0
      ? adminApiClient.put<{ message: string }>(path, payload)
      : adminApiClient.post<{ message: string }>(path, payload);
  },
  deleteAbusiveWord(wordId: number) {
    return adminApiClient.delete<{ message: string }>(`/admin/abusive-words/${wordId}`);
  },
  certificateLangForm(code: string, langId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/certificates/${encodeURIComponent(code)}/lang/${langId}`,
    );
  },
  certificateLangUpdate(code: string, langId: number, payload: Record<string, unknown>) {
    return adminApiClient.put<{ message: string }>(
      `/admin/certificates/${encodeURIComponent(code)}/lang/${langId}`,
      payload,
    );
  },
  updateCertificateStatus(code: string, active: boolean) {
    return adminApiClient.patch<{ message: string }>(
      `/admin/certificates/${encodeURIComponent(code)}/status`,
      { active },
    );
  },
  uploadCertificateMedia(code: string, langId: number, file: File) {
    const formData = new FormData();
    formData.append('lang_id', String(langId));
    formData.append('certpl_image', file);
    return adminApiClient.post<{ data: Record<string, unknown>; message: string }>(
      `/admin/certificates/${encodeURIComponent(code)}/media`,
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } },
    );
  },
  previewCertificate(code: string, langId: number) {
    return adminApiClient.get<string>(
      `/admin/certificates/${encodeURIComponent(code)}/preview/${langId}`,
      { responseType: 'text' },
    );
  },
  importLanguageLabels(file: File) {
    const formData = new FormData();
    formData.append('import_file', file);
    return adminApiClient.post<{ message: string }>('/admin/label/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  lessonStatsLogs(userId: number, reportType: number, params?: Record<string, unknown>) {
    return adminApiClient.get<{
      data: Record<string, unknown>[];
      meta: PaginatedMeta & { report_type?: number; show_prev_timings?: boolean };
      user?: Record<string, unknown>;
    }>(`/admin/lesson-stats/${userId}/logs`, {
      params: { ...params, report_type: reportType, reportType },
    });
  },
  regenerateSalesReport() {
    return adminApiClient.post<{
      status?: number;
      message: string;
      msg?: string;
      report_generated_at?: string | null;
      regendatedtime?: string | null;
    }>('/admin/sales-report/regenerate', undefined, { timeout: 300000 });
  },
  generateSitemap() {
    return adminApiClient.post<{
      status?: number;
      message: string;
      msg?: string;
    }>('/admin/sitemap/generate');
  },
  exportTeacherRequests(params?: Record<string, string>) {
    const query = new URLSearchParams(params);
    const url = `${import.meta.env.VITE_API_URL || '/api/v1'}/admin/teacher-requests/export?${query.toString()}`;
    const token = localStorage.getItem('admin_token');
    return fetch(url, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
  },
  teacherRequestView(requestId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/teacher-requests/${requestId}`);
  },
  teacherRequestQualifications(userId: number, page = 1) {
    return adminApiClient.get<{ data: Record<string, unknown>[]; meta: PaginatedMeta }>(
      `/admin/teacher-requests/users/${userId}/qualifications`,
      { params: { page } },
    );
  },
  teacherRequestStatusForm(requestId: number) {
    return adminApiClient.get<{
      request_id: number;
      statuses: Array<{ id: number; label_key: string }>;
    }>(`/admin/teacher-requests/${requestId}/status-form`);
  },
  updateTeacherRequestStatus(requestId: number, payload: { status: number; comments?: string }) {
    return adminApiClient.put(`/admin/teacher-requests/${requestId}/status`, payload);
  },
  courseRequestView(requestId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/course-requests/${requestId}`);
  },
  updateCourseRequestStatus(requestId: number, payload: { status: number; remark?: string }) {
    return adminApiClient.put(`/admin/course-requests/${requestId}/status`, payload);
  },
  courseEditRequestView(requestId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/course-edit-requests/${requestId}`);
  },
  updateCourseEditRequestStatus(requestId: number, payload: { status: number; comment?: string }) {
    return adminApiClient.put(`/admin/course-edit-requests/${requestId}/status`, payload);
  },
  courseRefundRequestView(requestId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/course-refund-requests/${requestId}`);
  },
  courseRefundRequestStatusForm(requestId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/course-refund-requests/${requestId}/status-form`,
    );
  },
  updateCourseRefundRequestStatus(requestId: number, payload: { status: number; comment?: string }) {
    return adminApiClient.put(`/admin/course-refund-requests/${requestId}/status`, payload);
  },
  exportWithdrawRequests(filters: Record<string, string>) {
    const params = new URLSearchParams({ ...filters, export: '1' });
    const token = localStorage.getItem('admin_token');
    return fetch(`${API_URL}/admin/withdraw-requests/export?${params.toString()}`, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
  },
  updateWithdrawRequestStatus(withdrawalId: number, status: number) {
    return adminApiClient.put(`/admin/withdraw-requests/${withdrawalId}/status`, { status });
  },
  ratingReviewShow(reviewId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/rating-reviews/${reviewId}`);
  },
  updateRatingReviewStatus(reviewId: number, status: number) {
    return adminApiClient.put(`/admin/rating-reviews/${reviewId}/status`, { status });
  },
  gdprRequestShow(requestId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/gdpr-requests/${requestId}`);
  },
  updateGdprRequestStatus(requestId: number, status: number, comment = '') {
    return adminApiClient.put(`/admin/gdpr-requests/${requestId}/status`, { status, comment });
  },
  exportGdprRequests(filters: Record<string, string>) {
    const params = new URLSearchParams({ ...filters, export: '1' });
    const token = localStorage.getItem('admin_token');
    return fetch(`${API_URL}/admin/gdpr-requests/export?${params.toString()}`, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
  },
  questionShow(questionId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/questions/${questionId}`);
  },
  questionQuizCategories(parentId = 0) {
    return adminApiClient.get<{ data: Array<{ id: number; name: string }> }>('/admin/questions/quiz-categories', {
      params: { parent_id: parentId },
    });
  },
  exportQuestions(filters: Record<string, string>) {
    const params = new URLSearchParams({ ...filters, export: '1' });
    const token = localStorage.getItem('admin_token');
    return fetch(`${API_URL}/admin/questions/export?${params.toString()}`, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
  },
  quizShow(quizId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/quizzes/${quizId}`);
  },
  exportQuizzes(filters: Record<string, string>) {
    const params = new URLSearchParams({ ...filters, export: '1' });
    const token = localStorage.getItem('admin_token');
    return fetch(`${API_URL}/admin/quizzes/export?${params.toString()}`, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
  },
  manageAdminCreateForm() {
    return adminApiClient.get<{
      default_timezone: string;
      timezones: Array<{ id: string; label: string }>;
    }>('/admin/admin-users/create');
  },
  createManageAdmin(payload: Record<string, unknown>) {
    return adminApiClient.post('/admin/admin-users', payload);
  },
  manageAdminShow(adminId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/admin-users/${adminId}`);
  },
  updateManageAdmin(adminId: number, payload: Record<string, unknown>) {
    return adminApiClient.put(`/admin/admin-users/${adminId}`, payload);
  },
  updateManageAdminStatus(adminId: number, active: boolean) {
    return adminApiClient.put(`/admin/admin-users/${adminId}/status`, { active: active ? 1 : 0 });
  },
  changeManageAdminPassword(adminId: number, payload: Record<string, unknown>) {
    return adminApiClient.put(`/admin/admin-users/${adminId}/change-password`, payload);
  },
  exportManageAdmins(filters: Record<string, string>) {
    const params = new URLSearchParams({ ...filters, export: '1' });
    const token = localStorage.getItem('admin_token');
    return fetch(`${API_URL}/admin/admin-users/export?${params.toString()}`, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
  },
  adminPermissionsShow(adminId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/admin-users/${adminId}/permissions`);
  },
  updateAdminPermission(adminId: number, sectionId: number, permission: number) {
    return adminApiClient.put(`/admin/admin-users/${adminId}/permissions`, {
      section_id: sectionId,
      permission,
    });
  },
  exportGroupClasses(filters: Record<string, string>, module: 'group-classes' | 'package-classes' = 'group-classes') {
    const params = new URLSearchParams({ ...filters, export: '1', module });
    const token = localStorage.getItem('admin_token');
    return fetch(`${API_URL}/admin/group-classes/export?${params.toString()}`, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
  },
  groupClassLearners(classId: number, page = 1) {
    return adminApiClient.get<{ data: Record<string, unknown>[]; meta: PaginatedMeta }>(
      `/admin/group-classes/${classId}/learners`,
      { params: { page } },
    );
  },
  groupClassCreateForm(langId = 1) {
    return adminApiClient.get<{
      data: {
        teach_languages: { id: number; name: string }[];
        site_languages: { id: number; name: string }[];
        durations: Record<string, string>;
        default_duration: number;
        max_learners: number;
        offline_enabled: boolean;
        currency_code: string;
        service_types: { value: number; label: string }[];
      };
    }>('/admin/group-classes/create-form', { params: { lang_id: langId } });
  },
  groupClassTeacherAutocomplete(keyword: string) {
    return adminApiClient.get<{ data: { id: number; full_name: string; email: string }[] }>(
      '/admin/group-classes/teacher-autocomplete',
      { params: { keyword } },
    );
  },
  groupClassDetails(classId: number, langId = 1) {
    return adminApiClient.get<{
      data: {
        id: number;
        title: string;
        description: string;
        teacher_label: string;
        language_label: string;
        service_type_label: string;
        class_address: string;
      };
    }>(`/admin/group-classes/${classId}/details`, { params: { lang_id: langId } });
  },
  groupClassShow(classId: number, langId = 1) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/group-classes/${classId}`, {
      params: { lang_id: langId },
    });
  },
  createGroupClass(payload: Record<string, unknown>) {
    return adminApiClient.post('/admin/group-classes', payload);
  },
  updateGroupClass(classId: number, payload: Record<string, unknown>) {
    return adminApiClient.put(`/admin/group-classes/${classId}`, payload);
  },
  groupClassLangForm(classId: number, langId: number) {
    return adminApiClient.get<{
      data: { title: string; description: string; package_classes?: Array<{ title?: string }> };
    }>(
      `/admin/group-classes/${classId}/lang/${langId}`,
    );
  },
  saveGroupClassLang(classId: number, langId: number, payload: Record<string, string>) {
    return adminApiClient.put(`/admin/group-classes/${classId}/lang/${langId}`, payload);
  },
  groupClassMediaForm(classId: number) {
    return adminApiClient.get<{ data: { has_banner: boolean } }>(`/admin/group-classes/${classId}/media`);
  },
  uploadGroupClassBanner(classId: number, file: File) {
    const formData = new FormData();
    formData.append('banner', file);
    return adminApiClient.post(`/admin/group-classes/${classId}/banner`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  courseLanguageCreateForm() {
    return adminApiClient.get<{ data: { site_languages: Array<{ id: number; name: string }> } }>(
      '/admin/course-languages/create-form',
    );
  },
  courseLanguageShow(clangId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/course-languages/${clangId}`);
  },
  createCourseLanguage(payload: Record<string, unknown>) {
    return adminApiClient.post<{ id?: number }>('/admin/course-languages', payload);
  },
  updateCourseLanguage(clangId: number, payload: Record<string, unknown>) {
    return adminApiClient.put(`/admin/course-languages/${clangId}`, payload);
  },
  courseLanguageLangForm(clangId: number, langId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/course-languages/${clangId}/lang/${langId}`,
    );
  },
  storeCourseLanguageLang(clangId: number, langId: number, payload: Record<string, unknown>) {
    return adminApiClient.put(`/admin/course-languages/${clangId}/lang/${langId}`, payload);
  },
  updateCourseLanguageStatus(clangId: number, status: number) {
    return adminApiClient.patch(`/admin/course-languages/${clangId}/status`, { status });
  },
  deleteCourseLanguage(clangId: number) {
    return adminApiClient.delete(`/admin/course-languages/${clangId}`);
  },
  updateCourseLanguageOrder(ids: number[]) {
    return adminApiClient.put('/admin/course-languages/order', { courseLanguages: ids });
  },
  blogPostCategoryCreateForm(parentId = 0, excludeId = 0, langId = 1) {
    return adminApiClient.get<{
      data: {
        site_languages: Array<{ id: number; name: string }>;
        parent_categories: Array<{ id: number; name: string }>;
        default_parent_id: number;
      };
    }>('/admin/blog-post-categories/create-form', {
      params: { parent_id: parentId, exclude_id: excludeId, lang_id: langId },
    });
  },
  blogPostCategoryShow(categoryId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/blog-post-categories/${categoryId}`);
  },
  createBlogPostCategory(payload: Record<string, unknown>) {
    return adminApiClient.post<{ id?: number }>('/admin/blog-post-categories', payload);
  },
  updateBlogPostCategory(categoryId: number, payload: Record<string, unknown>) {
    return adminApiClient.put(`/admin/blog-post-categories/${categoryId}`, payload);
  },
  blogPostCategoryLangForm(categoryId: number, langId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/blog-post-categories/${categoryId}/lang/${langId}`,
    );
  },
  storeBlogPostCategoryLang(categoryId: number, langId: number, payload: Record<string, unknown>) {
    return adminApiClient.put(`/admin/blog-post-categories/${categoryId}/lang/${langId}`, payload);
  },
  updateBlogPostCategoryStatus(categoryId: number, status: number) {
    return adminApiClient.patch(`/admin/blog-post-categories/${categoryId}/status`, { status });
  },
  deleteBlogPostCategory(categoryId: number) {
    return adminApiClient.delete(`/admin/blog-post-categories/${categoryId}`);
  },
  updateBlogPostCategoryOrder(ids: number[]) {
    return adminApiClient.put('/admin/blog-post-categories/order', { bpcategory: ids });
  },
  blogPostCreateForm(postId = 0, langId = 1) {
    return adminApiClient.get<{
      data: {
        site_languages: Array<{ id: number; name: string }>;
        categories: Array<{ id: number; name: string }>;
        selected_categories: number[];
      };
    }>('/admin/blog-posts/create-form', { params: { post_id: postId, lang_id: langId } });
  },
  blogPostShow(postId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/blog-posts/${postId}`);
  },
  createBlogPost(payload: Record<string, unknown>) {
    return adminApiClient.post<{ id?: number }>('/admin/blog-posts', payload);
  },
  updateBlogPost(postId: number, payload: Record<string, unknown>) {
    return adminApiClient.put(`/admin/blog-posts/${postId}`, payload);
  },
  blogPostLangForm(postId: number, langId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/blog-posts/${postId}/lang/${langId}`);
  },
  storeBlogPostLang(postId: number, langId: number, payload: Record<string, unknown>) {
    return adminApiClient.put(`/admin/blog-posts/${postId}/lang/${langId}`, payload);
  },
  deleteBlogPost(postId: number) {
    return adminApiClient.delete(`/admin/blog-posts/${postId}`);
  },
  blogPostImagesForm(postId: number) {
    return adminApiClient.get<{
      data: { post_id: number; language_options: Array<{ id: number; name: string }> };
    }>(`/admin/blog-posts/${postId}/images-form`);
  },
  blogPostImages(postId: number, langId = 0) {
    return adminApiClient.get<{
      data: Array<{
        file_id: number;
        file_name: string;
        file_lang_id: number;
        language_label: string;
        image_url: string;
      }>;
    }>(`/admin/blog-posts/${postId}/images`, { params: { lang_id: langId } });
  },
  uploadBlogPostImage(postId: number, langId: number, file: File) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('lang_id', String(langId));
    return adminApiClient.post(`/admin/blog-posts/${postId}/images`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  deleteBlogPostImage(postId: number, fileId: number) {
    return adminApiClient.delete(`/admin/blog-posts/${postId}/images/${fileId}`);
  },
  deleteBlogComment(commentId: number) {
    return adminApiClient.delete(`/admin/blog-comments/${commentId}`);
  },
  blogCommentShow(commentId: number, langId = 1) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/blog-comments/${commentId}`, {
      params: { lang_id: langId },
    });
  },
  updateBlogCommentStatus(commentId: number, payload: Record<string, unknown>) {
    return adminApiClient.patch(`/admin/blog-comments/${commentId}/status`, payload);
  },
  blogContributionShow(contributionId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/blog-contributions/${contributionId}`);
  },
  updateBlogContributionStatus(contributionId: number, payload: Record<string, unknown>) {
    return adminApiClient.patch(`/admin/blog-contributions/${contributionId}/status`, payload);
  },
  deleteBlogContribution(contributionId: number) {
    return adminApiClient.delete(`/admin/blog-contributions/${contributionId}`);
  },
  affiliateCommissionShow(commissionId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/affiliate-commissions/${commissionId}`);
  },
  setupAffiliateCommission(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data: { afcomm_id: number }; message: string }>('/admin/affiliate-commissions', payload);
  },
  affiliateCommissionHistory(userId: number) {
    return adminApiClient.get<{ data: Array<Record<string, unknown>> }>('/admin/affiliate-commissions/history', {
      params: { user_id: userId },
    });
  },
  affiliateCommissionAutocomplete(keyword: string) {
    return adminApiClient.get<{ data: Array<Record<string, unknown>> }>('/admin/affiliate-commissions/autocomplete', {
      params: { keyword },
    });
  },
  deleteAffiliateCommission(commissionId: number) {
    return adminApiClient.delete(`/admin/affiliate-commissions/${commissionId}`);
  },
  updateCourseStatus(courseId: number, status: number) {
    return adminApiClient.patch(`/admin/courses/${courseId}/status`, { status });
  },
  coursesSearchForm() {
    return adminApiClient.get<{ data: { categories: Array<{ id: number; name: string }> } }>(
      '/admin/courses/search-form',
    );
  },
  courseSubcategories(parentId: number, selectedId = 0) {
    return adminApiClient.get<{ data: Array<{ id: number; name: string }> }>('/admin/courses/subcategories', {
      params: { parent_id: parentId, selected_id: selectedId },
    });
  },
  courseLanguageAutocomplete(keyword: string) {
    return adminApiClient.get<{ data: Array<{ id: number; name: string }> }>(
      '/admin/courses/course-languages/autocomplete',
      { params: { keyword } },
    );
  },
  teachLanguageAutocomplete(keyword: string) {
    return adminApiClient.get<{ data: Array<{ id: number; name: string }> }>(
      '/admin/teach-languages/autocomplete',
      { params: { keyword } },
    );
  },
  courseShow(courseId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/courses/${courseId}`);
  },
  coursePreview(courseId: number) {
    return adminApiClient.get<{
      data: {
        course: Record<string, unknown>;
        sections: Array<Record<string, unknown>>;
        quiz: { id: number; title: string } | null;
        first_lecture_id: number | null;
      };
    }>(`/admin/courses/${courseId}/preview`);
  },
  coursePreviewLecture(courseId: number, lectureId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/courses/${courseId}/preview/lectures/${lectureId}`,
    );
  },
  coursePreviewNotes(courseId: number, params?: { keyword?: string; page?: number }) {
    return adminApiClient.get<{
      data: {
        data: Array<{
          id: number;
          notes: string;
          lecture_title: string;
          lecture_order: number;
        }>;
        meta: { current_page: number; last_page: number; per_page: number; total: number };
      };
    }>(`/admin/courses/${courseId}/preview/notes`, { params });
  },
  coursePreviewReviews(courseId: number, params?: { sort?: 'ASC' | 'DESC'; page?: number }) {
    return adminApiClient.get<{
      data: {
        course: { ratings: number; reviews: number };
        stats: Array<{ rating: number; count: number; percent: number }>;
        reviews: Array<{
          id: number;
          user_id: number;
          first_name: string;
          last_name: string;
          title: string;
          detail: string;
          rating: number;
          created_at: string;
        }>;
        meta: { current_page: number; last_page: number; per_page: number; total: number };
      };
    }>(`/admin/courses/${courseId}/preview/reviews`, { params });
  },
  coursePreviewBridge(courseId: number, teacherId: number) {
    return adminApiClient.post<{ url: string }>(`/admin/courses/${courseId}/preview-bridge`, {
      teacher_id: teacherId,
    });
  },
  exportCourses(filters: Record<string, string>) {
    const params = new URLSearchParams({ ...filters, export: '1' });
    const token = localStorage.getItem('admin_token');
    return fetch(`${API_URL}/admin/courses/export?${params.toString()}`, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
  },
  exportCourseLanguages() {
    const API_URL = import.meta.env.VITE_API_URL || '/api/v1';
    const token = localStorage.getItem('admin_token');
    return fetch(`${API_URL}/admin/course-languages/export`, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    }).then((res) => res.blob());
  },
  updateCategoryOrder(ids: number[], cateType = 1) {
    return adminApiClient.put('/admin/categories/order', { categoriesList: ids, cate_type: cateType });
  },
  categoryCreateForm(cateType: number, parentId = 0, langId = 1) {
    return adminApiClient.get<{ data: Record<string, unknown> }>('/admin/categories/create-form', {
      params: { cate_type: cateType, parent_id: parentId, lang_id: langId },
    });
  },
  categoryShow(cateId: number, cateType: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/categories/${cateId}`, {
      params: { cate_type: cateType },
    });
  },
  createCategory(payload: Record<string, unknown>) {
    return adminApiClient.post<{ id?: number }>('/admin/categories', payload);
  },
  updateCategory(cateId: number, payload: Record<string, unknown>) {
    return adminApiClient.put(`/admin/categories/${cateId}`, payload);
  },
  categoryLangForm(cateId: number, langId: number, cateType: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/categories/${cateId}/lang/${langId}`,
      { params: { cate_type: cateType } },
    );
  },
  storeCategoryLang(cateId: number, langId: number, payload: Record<string, unknown>) {
    return adminApiClient.put(`/admin/categories/${cateId}/lang/${langId}`, payload);
  },
  updateCategoryStatus(cateId: number, status: number, cateType: number) {
    return adminApiClient.patch(`/admin/categories/${cateId}/status`, { status, cate_type: cateType });
  },
  deleteCategory(cateId: number, cateType: number) {
    return adminApiClient.delete(`/admin/categories/${cateId}`, { params: { cate_type: cateType } });
  },
  categoryMediaForm(cateId: number, cateType: number) {
    return adminApiClient.get<{ data: { has_image: boolean } }>(`/admin/categories/${cateId}/media`, {
      params: { cate_type: cateType },
    });
  },
  uploadCategoryImage(cateId: number, file: File, cateType: number) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('cate_type', String(cateType));
    return adminApiClient.post(`/admin/categories/${cateId}/image`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  removeCategoryImage(cateId: number, cateType: number) {
    return adminApiClient.delete(`/admin/categories/${cateId}/image`, { params: { cate_type: cateType } });
  },
  pageText(pageKey: string, langId = 1) {
    return adminApiClient.get<{
      data: {
        plang_id?: number;
        title?: string;
        summary?: string;
        warning?: string;
        recommendations?: string;
        helping_text?: string;
      };
    }>(`/admin/page-text/${pageKey}`, { params: { lang_id: langId } });
  },
  pageLangDataLangForm(plangId: number, langId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/page-lang-data/${plangId}/lang/${langId}`,
    );
  },
  pageLangDataLangSetup(payload: Record<string, unknown>) {
    return adminApiClient.post<{
      data: { plang_id: number; next_lang_id: number };
      message: string;
    }>('/admin/page-lang-data/lang-setup', payload);
  },
  themeForm(themeId: number, action: 'update' | 'clone' = 'update') {
    return adminApiClient.get<{
      data: {
        theme: Record<string, unknown>;
        border_styles: Array<{ value: number; label: string }>;
        action: string;
      };
    }>(`/admin/themes/${themeId}/form`, { params: { action } });
  },
  themeSetup(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data: { theme_id: number } }>('/admin/themes/setup', payload);
  },
  activateTheme(themeId: number) {
    return adminApiClient.post<{ message: string }>(`/admin/themes/${themeId}/activate`);
  },
  deleteTheme(themeId: number) {
    return adminApiClient.delete<{ message: string }>(`/admin/themes/${themeId}`);
  },
  currencyForm(currencyId = 0) {
    const path = currencyId > 0 ? `/admin/currencies/form/${currencyId}` : '/admin/currencies/form';
    return adminApiClient.get<{ data: Record<string, unknown> }>(path);
  },
  currencyLangForm(currencyId: number, langId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/currencies/${currencyId}/lang/${langId}`,
    );
  },
  currencySetup(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data: { currency_id: number } }>('/admin/currencies/setup', payload);
  },
  currencyLangSetup(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data: { currency_id: number } }>('/admin/currencies/lang-setup', payload);
  },
  updateCurrencyStatus(currencyId: number, active: boolean) {
    return adminApiClient.patch(`/admin/currencies/${currencyId}/status`, { active });
  },
  updateCurrencyOrder(ids: number[]) {
    return adminApiClient.put('/admin/currencies/order', { currencyList: ids });
  },
  currencyFixerConfig() {
    return adminApiClient.get<{ data: Record<string, unknown> }>('/admin/currencies/fixer-config');
  },
  currencyFixerSetup(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data: Record<string, unknown> }>('/admin/currencies/fixer-config', payload);
  },
  currencySyncRates() {
    return adminApiClient.post<{ data: Record<string, unknown> }>('/admin/currencies/sync-rates');
  },
  commissionShow(commissionId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/commissions/${commissionId}`);
  },
  commissionSetup(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data: { comm_id: number }; message: string }>('/admin/commissions/setup', payload);
  },
  commissionHistory(userId: number, page = 1) {
    return adminApiClient.get<{
      data: Array<Record<string, unknown>>;
      meta: Record<string, number | boolean>;
    }>('/admin/commissions/history', {
      params: { user_id: userId, page },
    });
  },
  commissionAutocomplete(keyword: string) {
    return adminApiClient.get<{ data: Array<Record<string, unknown>> }>('/admin/commissions/autocomplete', {
      params: { keyword },
    });
  },
  couponForm(couponId = 0) {
    const path = couponId > 0 ? `/admin/coupons/form/${couponId}` : '/admin/coupons/form';
    return adminApiClient.get<{ data: Record<string, unknown> }>(path);
  },
  couponLangForm(couponId: number, langId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/coupons/${couponId}/lang/${langId}`,
    );
  },
  couponSetup(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data: { coupon_id: number }; message: string }>(
      '/admin/coupons/setup',
      payload,
    );
  },
  couponLangSetup(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data: { coupon_id: number }; message: string }>(
      '/admin/coupons/lang-setup',
      payload,
    );
  },
  couponUses(couponId: number, page = 1) {
    return adminApiClient.get<{
      data: Array<Record<string, unknown>>;
      meta: Record<string, number>;
    }>('/admin/coupons/uses', {
      params: { coupon_id: couponId, page },
    });
  },
  socialPlatformForm(platformId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/social-platforms/form/${platformId}`,
    );
  },
  socialPlatformSetup(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data: { splatform_id: number }; message: string }>(
      '/admin/social-platforms/setup',
      payload,
    );
  },
  updateSocialPlatformStatus(platformId: number, active: boolean) {
    return adminApiClient.patch<{ message: string }>(`/admin/social-platforms/${platformId}/status`, {
      active,
    });
  },
  slideForm(slideId = 0) {
    return adminApiClient.get<{ data: { slide?: Record<string, unknown> } }>(
      `/admin/slides/form/${slideId}`,
    );
  },
  slideSetup(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data: { slide_id: number }; message: string }>(
      '/admin/slides/setup',
      payload,
    );
  },
  slideMediaForm(slideId: number, langId: number) {
    return adminApiClient.get<{
      data: {
        slide_id: number;
        lang_id: number;
        display_types: Record<string, string>;
        images: Record<string, Record<string, unknown>>;
        site_languages: Array<{ id: number; name: string }>;
      };
    }>(`/admin/slides/${slideId}/media/${langId}`);
  },
  slideMediaSetup(slideId: number, langId: number, payload: FormData) {
    return adminApiClient.post<{ data: { slide_id: number; lang_id: number }; message: string }>(
      `/admin/slides/${slideId}/media/${langId}`,
      payload,
      { headers: { 'Content-Type': 'multipart/form-data' } },
    );
  },
  updateSlideStatus(slideId: number, active: boolean) {
    return adminApiClient.patch<{ message: string }>(`/admin/slides/${slideId}/status`, {
      active,
    });
  },
  updateSlidesOrder(ids: number[]) {
    return adminApiClient.put<{ message: string }>('/admin/slides/order', { ids });
  },
  deleteSlide(slideId: number) {
    return adminApiClient.delete<{ message: string }>(`/admin/slides/${slideId}`);
  },
  paymentMethodSettingsForm(methodId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/payment-methods/settings/${methodId}`,
    );
  },
  paymentMethodSettingsSetup(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data: { pmethod_id: number }; message: string }>(
      '/admin/payment-methods/settings',
      payload,
    );
  },
  paymentMethodTxnFeeForm(methodId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/payment-methods/${methodId}/txnfee`,
    );
  },
  paymentMethodTxnFeeSetup(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data: { pmethod_id: number }; message: string }>(
      '/admin/payment-methods/txnfee',
      payload,
    );
  },
  updatePaymentMethodStatus(methodId: number, active: boolean) {
    return adminApiClient.patch<{ message: string }>(`/admin/payment-methods/${methodId}/status`, {
      active,
    });
  },
  updatePaymentMethodOrder(ids: number[]) {
    return adminApiClient.put<{ message: string }>('/admin/payment-methods/order', {
      paymentMethod: ids,
    });
  },
  meetingToolForm(toolId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/meeting-tools/form/${toolId}`);
  },
  meetingToolSetup(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data: { metool_id: number }; message: string }>(
      '/admin/meeting-tools/setup',
      payload,
    );
  },
  updateMeetingToolStatus(toolId: number, active: boolean) {
    return adminApiClient.patch<{ message: string }>(`/admin/meeting-tools/${toolId}/status`, {
      active,
    });
  },
  deleteCoupon(couponId: number) {
    return adminApiClient.delete(`/admin/coupons/${couponId}`);
  },
  updateUserStatus(userId: number, active: boolean) {
    return adminApiClient.patch(`/admin/users/${userId}/status`, { active });
  },
  userAutocomplete(keyword: string) {
    return adminApiClient.get('/admin/users/autocomplete', { params: { keyword } });
  },
  userCreateForm() {
    return adminApiClient.get<{
      default_country_id: number;
      default_timezone: string;
      countries: Array<{ id: number; name: string; dial_code: string; phone_label: string }>;
      timezones: Array<{ id: string; label: string }>;
      user_types: Array<{ id: number; label_key: string }>;
    }>('/admin/users/create');
  },
  createUser(payload: Record<string, unknown>) {
    return adminApiClient.post('/admin/users', payload);
  },
  userView(userId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/users/${userId}`);
  },
  userEditForm(userId: number) {
    return adminApiClient.get<{
      user: {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
        username: string;
        email_username: string;
        country_id: number;
        phone_code: number;
        phone_number: string;
        featured: boolean;
        is_teacher: boolean;
        user_type: number;
        timezone: string;
        timezone_locked: boolean;
        is_parent_account: boolean;
      };
      countries: Array<{ id: number; name: string; dial_code: string; phone_label: string }>;
      timezones: Array<{ id: string; label: string }>;
      user_types: Array<{ id: number; label_key: string }>;
    }>(`/admin/users/${userId}/edit`);
  },
  updateUser(userId: number, payload: Record<string, unknown>) {
    return adminApiClient.put(`/admin/users/${userId}`, payload);
  },
  userLogin(userId: number) {
    return adminApiClient.post<{ token: string; redirect_url: string }>(`/admin/users/${userId}/login`);
  },
  userTransactions(userId: number, page = 1) {
    return adminApiClient.get<{ data: Record<string, unknown>[]; meta: PaginatedMeta }>(
      `/admin/users/${userId}/transactions`,
      { params: { page } }
    );
  },
  createUserTransaction(userId: number, payload: Record<string, unknown>) {
    return adminApiClient.post(`/admin/users/${userId}/transactions`, payload);
  },
  userAddresses(userId: number) {
    return adminApiClient.get<{ data: Record<string, unknown>[] }>(`/admin/users/${userId}/addresses`);
  },
  changeUserPassword(userId: number, payload: { new_password: string; conf_new_password: string }) {
    return adminApiClient.post(`/admin/users/${userId}/change-password`, payload);
  },
  generalSettingsForm(langId = 1) {
    return adminApiClient.get<{
      values: Record<string, unknown>;
      options: Record<string, unknown>;
    }>('/admin/configurations/general-settings', { params: { lang_id: langId } });
  },
  updateGeneralSettings(payload: Record<string, unknown>, langId = 1) {
    return adminApiClient.patch<{
      message: string;
      form: { values: Record<string, unknown>; options: Record<string, unknown> };
    }>('/admin/configurations/general-settings', payload, { params: { lang_id: langId } });
  },
  generalSettingsLangForm(formLangId: number) {
    return adminApiClient.get<{
      lang_id: number;
      layout_direction: string;
      values: Record<string, unknown>;
    }>(`/admin/configurations/general-settings/lang/${formLangId}`);
  },
  updateGeneralSettingsLangForm(formLangId: number, payload: Record<string, unknown>) {
    return adminApiClient.patch<{
      message: string;
      form: { lang_id: number; layout_direction: string; values: Record<string, unknown> };
    }>(`/admin/configurations/general-settings/lang/${formLangId}`, payload);
  },
  thirdPartyApiSettings() {
    return adminApiClient.get<Record<string, unknown>>('/admin/configurations/third-party-apis');
  },
  updateThirdPartyApiSettings(payload: Record<string, unknown>) {
    return adminApiClient.patch<{
      message: string;
      settings: Record<string, unknown>;
    }>('/admin/configurations/third-party-apis', payload);
  },
  googleAnalyticsSettings() {
    return adminApiClient.get<{
      property_id: string;
      client_json: string;
      client_json_configured: boolean;
    }>('/admin/configurations/google-analytics');
  },
  updateGoogleAnalyticsSettings(payload: { property_id: string; client_json?: string }) {
    return adminApiClient.patch<{
      message: string;
      settings: { property_id: string; client_json_configured: boolean };
    }>('/admin/configurations/google-analytics', payload);
  },
  configurationForm(formType: number, langId = 0, siteLangId = 1) {
    return adminApiClient.get<Record<string, unknown>>(`/admin/configurations/forms/${formType}`, {
      params: { lang_id: langId, site_lang_id: siteLangId },
    });
  },
  updateConfigurationForm(formType: number, payload: { values: Record<string, unknown>; lang_id?: number }, siteLangId = 1) {
    return adminApiClient.patch<{
      message: string;
      form: Record<string, unknown>;
    }>(`/admin/configurations/forms/${formType}`, payload, { params: { site_lang_id: siteLangId } });
  },
  configurationMedia(langId: number) {
    return adminApiClient.get<{ lang_id: number; slots: Record<string, unknown>[] }>('/admin/configurations/media', {
      params: { lang_id: langId },
    });
  },
  uploadConfigurationMedia(fileType: number, langId: number, file: File) {
    const formData = new FormData();
    formData.append('file_type', String(fileType));
    formData.append('lang_id', String(langId));
    formData.append('file', file);
    return adminApiClient.post<{ message: string; slot?: Record<string, unknown> }>(
      '/admin/configurations/media',
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } },
    );
  },
  removeConfigurationMedia(fileType: number, langId: number) {
    return adminApiClient.delete<{ message: string }>('/admin/configurations/media', {
      data: { file_type: fileType, lang_id: langId },
    });
  },
  uploadPwaIcon(file: File) {
    const formData = new FormData();
    formData.append('file', file);
    return adminApiClient.post<{ message: string; icon_url?: string }>('/admin/configurations/pwa-icon', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  testConfigurationEmail(siteLangId = 1) {
    return adminApiClient.post<{ message: string }>('/admin/configurations/test-email', {}, {
      params: { site_lang_id: siteLangId },
    });
  },
  orderShow(orderId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/orders/${orderId}`);
  },
  cancelOrder(orderId: number) {
    return adminApiClient.post(`/admin/orders/${orderId}/cancel`);
  },
  addOrderPayment(orderId: number, payload: Record<string, unknown>) {
    return adminApiClient.post(`/admin/orders/${orderId}/payment`, payload);
  },
  updateBankTransferStatus(payId: number, status: number) {
    return adminApiClient.put(`/admin/bank-transfers/${payId}/status`, { status });
  },
  lessonOrderShow(lessonId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/lesson-orders/${lessonId}`);
  },
  classOrderShow(classId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/class-orders/${classId}`);
  },
  packageOrderShow(packageId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/package-orders/${packageId}`);
  },
  courseOrderShow(courseOrderId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/course-orders/${courseOrderId}`);
  },
  giftcardOrderShow(orderId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/giftcard-orders/${orderId}`);
  },
  orderSubscriptionPlanShow(planOrderId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/order-subscription-plan-orders/${planOrderId}`,
    );
  },
  exportOrders(module: string, filters: Record<string, string>) {
    const params = new URLSearchParams({ ...filters, export: '1' });
    const token = localStorage.getItem('admin_token');
    return fetch(`${API_URL}/admin/modules/${module}?${params.toString()}`, {
      headers: token ? { Authorization: `Bearer ${token}` } : {},
    });
  },
  preferenceShow(preferId: number, langId?: number) {
    const params = langId ? { lang_id: langId } : {};
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/preferences/${preferId}`, { params });
  },
  createPreference(payload: {
    prefer_identifier: string;
    prefer_title?: string;
    prefer_type: number;
    lang_id?: number;
    update_langs_data?: number;
  }) {
    return adminApiClient.post<{ data?: { prefer_id?: number } }>('/admin/preferences', payload);
  },
  updatePreference(
    preferId: number,
    payload: {
      prefer_identifier: string;
      prefer_title?: string;
      prefer_type: number;
      lang_id?: number;
      update_langs_data?: number;
    },
  ) {
    return adminApiClient.put(`/admin/preferences/${preferId}`, payload);
  },
  deletePreference(preferId: number) {
    return adminApiClient.delete(`/admin/preferences/${preferId}`);
  },
  updatePreferenceOrder(ids: number[]) {
    return adminApiClient.put('/admin/preferences/order', { preferences: ids });
  },
  speakLanguageLevelShow(slanglvlId: number, langId?: number) {
    const params = langId ? { lang_id: langId } : {};
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/speak-language-levels/${slanglvlId}`,
      { params },
    );
  },
  createSpeakLanguageLevel(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data?: { slanglvl_id?: number } }>('/admin/speak-language-levels', payload);
  },
  updateSpeakLanguageLevel(slanglvlId: number, payload: Record<string, unknown>) {
    return adminApiClient.put(`/admin/speak-language-levels/${slanglvlId}`, payload);
  },
  updateSpeakLanguageLevelStatus(slanglvlId: number, active: boolean) {
    return adminApiClient.patch(`/admin/speak-language-levels/${slanglvlId}/status`, { active });
  },
  deleteSpeakLanguageLevel(slanglvlId: number) {
    return adminApiClient.delete(`/admin/speak-language-levels/${slanglvlId}`);
  },
  updateSpeakLanguageLevelOrder(ids: number[]) {
    return adminApiClient.put('/admin/speak-language-levels/order', { spokenLanguageLevels: ids });
  },
  speakLanguageShow(slangId: number, langId?: number) {
    const params = langId ? { lang_id: langId } : {};
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/speak-languages/${slangId}`, { params });
  },
  createSpeakLanguage(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data?: { slang_id?: number } }>('/admin/speak-languages', payload);
  },
  updateSpeakLanguage(slangId: number, payload: Record<string, unknown>) {
    return adminApiClient.put(`/admin/speak-languages/${slangId}`, payload);
  },
  updateSpeakLanguageStatus(slangId: number, active: boolean) {
    return adminApiClient.patch(`/admin/speak-languages/${slangId}/status`, { active });
  },
  deleteSpeakLanguage(slangId: number) {
    return adminApiClient.delete(`/admin/speak-languages/${slangId}`);
  },
  updateSpeakLanguageOrder(ids: number[]) {
    return adminApiClient.put('/admin/speak-languages/order', { spokenLanguages: ids });
  },
  issueReportOptionShow(optId: number, langId?: number) {
    const params = langId ? { lang_id: langId } : {};
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/issue-report-options/${optId}`,
      { params },
    );
  },
  createIssueReportOption(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data?: { tissueopt_id?: number; optId?: number } }>(
      '/admin/issue-report-options',
      payload,
    );
  },
  updateIssueReportOption(optId: number, payload: Record<string, unknown>) {
    return adminApiClient.put(`/admin/issue-report-options/${optId}`, payload);
  },
  updateIssueReportOptionStatus(optId: number, active: boolean) {
    return adminApiClient.patch(`/admin/issue-report-options/${optId}/status`, { active });
  },
  deleteIssueReportOption(optId: number) {
    return adminApiClient.delete(`/admin/issue-report-options/${optId}`);
  },
  updateIssueReportOptionOrder(ids: number[]) {
    return adminApiClient.put('/admin/issue-report-options/order', { IssueReportOptions: ids });
  },
  forumReportReasonShow(id: number, langId?: number) {
    const params = langId ? { lang_id: langId } : {};
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/forum-report-reasons/${id}`, { params });
  },
  createForumReportReason(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data?: { frireason_id?: number; id?: number } }>('/admin/forum-report-reasons', payload);
  },
  updateForumReportReason(id: number, payload: Record<string, unknown>) {
    return adminApiClient.put(`/admin/forum-report-reasons/${id}`, payload);
  },
  updateForumReportReasonStatus(id: number, active: boolean) {
    return adminApiClient.patch(`/admin/forum-report-reasons/${id}/status`, { active });
  },
  updateForumReportReasonOrder(ids: number[]) {
    return adminApiClient.put('/admin/forum-report-reasons/order', { ForumReportIssueReasons: ids });
  },
  forumTagShow(id: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/forum-tags/${id}`);
  },
  createForumTag(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data?: { ftag_id?: number } }>('/admin/forum-tags', payload);
  },
  updateForumTag(id: number, payload: Record<string, unknown>) {
    return adminApiClient.put(`/admin/forum-tags/${id}`, payload);
  },
  updateForumTagStatus(id: number, active: boolean) {
    return adminApiClient.patch(`/admin/forum-tags/${id}/status`, { active });
  },
  deleteForumTag(id: number) {
    return adminApiClient.delete(`/admin/forum-tags/${id}`);
  },
  restoreForumTag(id: number) {
    return adminApiClient.patch(`/admin/forum-tags/${id}/restore`);
  },
  forumTagRequestShow(id: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/forum-tag-requests/${id}`);
  },
  updateForumTagRequestStatus(id: number, status: number) {
    return adminApiClient.patch(`/admin/forum-tag-requests/${id}/status`, { ftagreq_status: status });
  },
  forumReportedQuestionShow(id: number, langId?: number) {
    const params = langId ? { lang_id: langId } : {};
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/forum-reported-questions/${id}`, { params });
  },
  forumReportedQuestionAction(id: number, payload: Record<string, unknown>) {
    return adminApiClient.post(`/admin/forum-reported-questions/${id}/action`, payload);
  },
  forumQuestionShow(id: number, langId?: number) {
    const params = langId ? { lang_id: langId } : {};
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/forum-questions/${id}`, { params });
  },
  deleteForumQuestion(id: number) {
    return adminApiClient.delete(`/admin/forum-questions/${id}`);
  },
  forumQuestionComments(id: number, page = 1, perPage = 10) {
    return adminApiClient.get<{ data: Record<string, unknown>[]; meta: Record<string, number> }>(
      `/admin/forum-questions/${id}/comments`,
      { params: { page, per_page: perPage } },
    );
  },
  deleteForumQuestionComment(questionId: number, commentId: number) {
    return adminApiClient.delete(`/admin/forum-questions/${questionId}/comments/${commentId}`);
  },
  teachLanguageContext(parentId: number, langId?: number, excludeId?: number) {
    const params: Record<string, number> = { parent_id: parentId };
    if (langId) params.lang_id = langId;
    if (excludeId) params.exclude_id = excludeId;
    return adminApiClient.get<{ data: Record<string, unknown> }>('/admin/teach-languages/context', { params });
  },
  teachLanguageShow(tlangId: number, langId?: number) {
    const params = langId ? { lang_id: langId } : {};
    return adminApiClient.get<{ data: Record<string, unknown> }>(`/admin/teach-languages/${tlangId}`, { params });
  },
  createTeachLanguage(payload: Record<string, unknown>) {
    return adminApiClient.post<{ data?: { tlang_id?: number } }>('/admin/teach-languages', payload);
  },
  updateTeachLanguage(tlangId: number, payload: Record<string, unknown>) {
    return adminApiClient.put(`/admin/teach-languages/${tlangId}`, payload);
  },
  updateTeachLanguageStatus(tlangId: number, active: boolean) {
    return adminApiClient.patch(`/admin/teach-languages/${tlangId}/status`, { active });
  },
  deleteTeachLanguage(tlangId: number) {
    return adminApiClient.delete(`/admin/teach-languages/${tlangId}`);
  },
  updateTeachLanguageOrder(ids: number[]) {
    return adminApiClient.put('/admin/teach-languages/order', { teachingLanguages: ids });
  },
  teachLanguageMediaForm(tlangId: number) {
    return adminApiClient.get<{ data: { has_image?: boolean; dimensions?: string; allowed_extensions?: string } }>(
      `/admin/teach-languages/${tlangId}/media`,
    );
  },
  uploadTeachLanguageImage(tlangId: number, file: File) {
    const formData = new FormData();
    formData.append('file', file);
    return adminApiClient.post(`/admin/teach-languages/${tlangId}/media`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
  },
  removeTeachLanguageImage(tlangId: number) {
    return adminApiClient.delete(`/admin/teach-languages/${tlangId}/media`);
  },
  metaTagForm(metaId: number, metaType: number, recordId: string) {
    return adminApiClient.get<{ data: Record<string, unknown> }>('/admin/meta-tags/form', {
      params: { meta_id: metaId, meta_type: metaType, record_id: recordId },
    });
  },
  metaTagLangForm(metaId: number, langId: number) {
    return adminApiClient.get<{ data: Record<string, unknown> }>(
      `/admin/meta-tags/${metaId}/lang/${langId}`,
    );
  },
  saveMetaTag(payload: {
    meta_id: number;
    meta_type: number;
    meta_record_id: string;
    meta_identifier: string;
    meta_slug?: string;
  }) {
    return adminApiClient.post<{ data?: { meta_id?: number; lang_id?: number } }>(
      '/admin/meta-tags/setup',
      payload,
    );
  },
  saveMetaTagLang(metaId: number, langId: number, payload: Record<string, string | boolean>) {
    return adminApiClient.put<{ data?: { meta_id?: number; lang_id?: number } }>(
      `/admin/meta-tags/${metaId}/lang/${langId}`,
      payload,
    );
  },
  deleteMetaTag(metaId: number) {
    return adminApiClient.delete(`/admin/meta-tags/${metaId}`);
  },
  urlRewritingForm(seourlId = 0) {
    return adminApiClient.get<{ data: Record<string, unknown> }>('/admin/url-rewriting/form', {
      params: { seourlId },
    });
  },
  saveUrlRewriting(payload: Record<string, unknown>) {
    return adminApiClient.post('/admin/url-rewriting/setup', payload);
  },
  deleteUrlRewriting(id: number) {
    return adminApiClient.delete(`/admin/url-rewriting/${id}`);
  },
  botsShow() {
    return adminApiClient.get<{ data: { bots_txt: string } }>('/admin/bots');
  },
  botsSetup(botsTxt: string) {
    return adminApiClient.post<{ message?: string }>('/admin/bots/setup', { botsTxt });
  },
  sitemapXml() {
    return adminApiClient.get<{
      data: { content: string; files: Array<{ name: string; url: string }>; public_url: string };
    }>('/admin/sitemap/xml');
  },
  sitemapHtml(langId = 1) {
    return adminApiClient.get<{ data: import('../../api/client').SitemapHtmlData }>('/admin/sitemap/html', {
      params: { lang_id: langId },
    });
  },
};
