import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || '/api/v1';

export const api = axios.create({
  baseURL: API_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      const url = String(error.config?.url ?? '');
      const isAuthAttempt =
        url.includes('/auth/login') || url.includes('/auth/register');
      if (!isAuthAttempt && localStorage.getItem('auth_token')) {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_user');
        window.dispatchEvent(new Event('auth:cleared'));
      }
    }
    return Promise.reject(error);
  }
);

export type User = {
  id: number;
  first_name: string;
  last_name?: string;
  full_name: string;
  email: string;
  is_teacher: boolean;
};

export type Course = {
  id: number;
  slug: string;
  title?: string;
  subtitle?: string;
  description?: string;
  price: number;
  duration: number;
  sections: number;
  lectures: number;
  students: number;
  ratings: number;
  reviews?: number;
  certificate?: boolean;
  is_free?: boolean;
  level?: number;
  preview_video?: string;
  teacher?: CourseTeacher;
  category_id?: number;
  subcategory_id?: number;
  category_name?: string;
  subcategory_name?: string;
  language_name?: string;
  level_name?: string;
  tags?: string[];
  resources_count?: number;
  has_quiz?: boolean;
  review_stats?: CourseReviewStat[];
  more_courses?: CourseCardSummary[];
};

export type CourseCardSummary = {
  id: number;
  slug: string;
  title?: string;
  price: number;
  ratings: number;
  reviews: number;
  duration: number;
  lectures: number;
  is_free: boolean;
};

export type CourseTeacher = {
  id: number;
  full_name: string;
  first_name?: string;
  last_name?: string;
  username?: string;
  biography?: string;
  ratings?: number;
  reviews?: number;
  courses?: number;
  profile_complete?: boolean;
  is_featured?: boolean;
};

export type CourseReviewStat = {
  rating: number;
  count: number;
  percent: number;
};

export type CourseReview = {
  id: number;
  user_id: number;
  first_name: string;
  last_name: string;
  title: string;
  detail: string;
  rating: number;
  created_at: string;
};

export type CurriculumLecture = {
  id: number;
  title: string;
  details?: string;
  duration: number;
  order: number;
  is_trial: boolean;
  is_accessible: boolean;
  is_completed: boolean;
};

export type CurriculumSection = {
  id: number;
  title: string;
  details?: string;
  order: number;
  lectures_count: number;
  duration: number;
  lectures: CurriculumLecture[];
};

export type IntendedLearners = {
  learning_outcomes: { id: number; text: string; order: number }[];
  requirements: { id: number; text: string; order: number }[];
  target_audience: { id: number; text: string; order: number }[];
};

export type EnrollmentInfo = {
  is_enrolled: boolean;
  order_course_id?: number;
  status?: number;
  progress_percent?: number;
};

export type EnrolledCourse = {
  enrollment_id: number;
  status: number;
  amount: number;
  progress: {
    percent: number;
    status: number;
    current_lecture_id: number;
    covered_lectures: number[];
    started_at?: string;
    completed_at?: string;
  };
  course: Course | null;
};

export type LectureDetail = {
  id: number;
  title: string;
  details?: string;
  duration: number;
  order: number;
  is_trial: boolean;
  section?: { id: number; title: string };
  resources?: {
    id: number;
    type: number;
    link: string;
    name: string;
    duration: number;
  }[];
};

export type Lesson = {
  id: number;
  status: number;
  duration: number;
  amount: number;
  start_time?: string;
  end_time?: string;
  teacher?: { id: number; full_name: string; first_name?: string };
  learner?: { id: number; full_name: string; first_name?: string } | null;
};

export type Paginated<T> = {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
};

export function formatDuration(seconds: number): string {
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  if (h > 0) return `${h}h ${m}m`;
  return `${m} min`;
}

export const authApi = {
  login: (email: string, password: string) =>
    api.post<{ token: string; user: User }>('/auth/login', { email, password }),
  register: (payload: {
    first_name: string;
    last_name?: string;
    email: string;
    password: string;
  }) => api.post<{ token: string; user: User }>('/auth/register', payload),
  affiliateRegister: (payload: {
    first_name: string;
    last_name?: string;
    email: string;
    password: string;
    agree: boolean;
  }) =>
    api.post<{ token: string; user: User; message: string }>('/auth/affiliate-register', {
      ...payload,
      agree: payload.agree ? 1 : 0,
    }),
  forgotPassword: (email: string) =>
    api.post<{ message: string }>('/auth/forgot-password', { email }),
  validateResetPassword: (userId: number, token: string) =>
    api.get<{ valid: boolean; message?: string }>(
      `/auth/reset-password/${userId}/${encodeURIComponent(token)}`
    ),
  resetPassword: (payload: {
    user_id: number;
    token: string;
    new_password: string;
    confirm_password: string;
  }) => api.post<{ message: string }>('/auth/reset-password', payload),
  me: () => api.get<{ user: User }>('/auth/me'),
  logout: () => api.post('/auth/logout'),
};

export const coursesApi = {
  filters: () => api.get<CourseFilters>('/courses/filters'),
  list: (params?: {
    search?: string;
    page?: number;
    sort?: string;
    type?: number;
    category?: number[];
    level?: number[];
    language?: number[];
    ratings?: number;
    price_from?: number;
    price_till?: number;
  }) => api.get<Paginated<Course>>('/courses', { params }),
  get: (idOrSlug: string | number) => {
    const key = String(idOrSlug);
    if (/^\d+$/.test(key)) {
      return api.get<{ data: Course; enrollment: EnrollmentInfo }>(`/courses/${key}`);
    }
    return api.get<{ data: Course; enrollment: EnrollmentInfo }>(
      `/courses/slug/${encodeURIComponent(key)}`
    );
  },
  reviews: (id: number, params?: { page?: number; sort?: 'ASC' | 'DESC' }) =>
    api.get<Paginated<CourseReview>>(`/courses/${id}/reviews`, { params }),
  enroll: (id: number) =>
    api.post<{
      enrolled: boolean;
      payment_required?: boolean;
      message?: string;
      enrollment?: EnrollmentInfo;
    }>(`/courses/${id}/enroll`),
  curriculum: (id: number) =>
    api.get<{
      data: CurriculumSection[];
      meta: {
        sections_count: number;
        lectures_count: number;
        duration: number;
        is_enrolled: boolean;
      };
    }>(`/courses/${id}/curriculum`),
  intendedLearners: (id: number) =>
    api.get<{ data: IntendedLearners }>(`/courses/${id}/intended-learners`),
  getLecture: (courseId: number, lectureId: number) =>
    api.get<{
      data: LectureDetail;
      progress: { percent: number; covered_lectures: number[]; is_completed: boolean } | null;
    }>(`/courses/${courseId}/lectures/${lectureId}`),
};

export const myCoursesApi = {
  list: (params?: { page?: number }) =>
    api.get<Paginated<EnrolledCourse>>('/my/courses', { params }),
  get: (courseId: number) =>
    api.get<{ data: EnrolledCourse; curriculum: CurriculumSection[] }>(
      `/my/courses/${courseId}`
    ),
  start: (courseId: number) => api.post(`/my/courses/${courseId}/start`),
  markComplete: (courseId: number, lectureId: number) =>
    api.post(`/my/courses/${courseId}/progress`, { lecture_id: lectureId }),
};

export type TeacherQualification = {
  id: number;
  title: string;
  institute_name: string;
  institute_address: string;
  start_year: number;
  end_year: number;
  type: number;
};

export type TeacherPricingLanguage = {
  id: number;
  name: string;
  hourly_price: number;
  slots: number[];
  prices: Record<number, number>;
};

export type TeacherProfile = TeacherListing & {
  country?: string;
  reviews_count?: number;
  qualifications?: TeacherQualification[];
  more_courses?: { id: number; slug: string; title: string; price: number; ratings: number; reviews: number }[];
  user_slots?: number[];
  pricing_languages?: TeacherPricingLanguage[];
};

export type TeacherBookingAddress = {
  id: number;
  formatted: string;
};

export type TeacherBookingOptions = {
  teacher_id: number;
  languages: TeacherPricingLanguage[];
  defaults: {
    ordles_tlang_id: number;
    ordles_duration: number;
    ordles_quantity: number;
    ordles_type: number;
    ordles_offline: number;
    ordles_address_id: number;
  };
  trial_enabled: boolean;
  subscription_weeks: number;
  offline_sessions_enabled: boolean;
  default_address: TeacherBookingAddress | null;
};

export type TeacherAvailabilityMeta = {
  teacher_id: number;
  teacher_name: string;
  min_date: string;
  timezone: string;
  timezone_label: string;
};

export type TeacherAvailabilitySlotEntry = {
  start: string;
  label: string;
};

export type TeacherAvailabilitySlots = {
  date_heading: string;
  entries: TeacherAvailabilitySlotEntry[];
  empty: boolean;
};

export type FaqData = {
  categories: { id: number; name: string }[];
  faqs_by_category: Record<number, { id: number; title: string; description: string }[]>;
};

export type BlogCategory = {
  id: number;
  name: string;
  post_count?: number;
  children_post_count?: number;
  children?: BlogCategory[];
};

export type BlogPostSummary = {
  id: number;
  title: string;
  excerpt: string;
  published_at: string;
  categories?: { id: number; name: string }[];
};

export type BlogComment = {
  id: number;
  author_name: string;
  user_id: number;
  content: string;
  added_on: string;
};

export type BlogPostDetail = BlogPostSummary & {
  description?: string;
  author?: string;
  comment_opened?: boolean;
  comments_count?: number;
  images?: { id: number; url: string }[];
};

export type BlogListResponse = {
  categories: BlogCategory[];
  category_name?: string;
  data: BlogPostSummary[];
  meta: Paginated<BlogPostSummary>['meta'];
};

export type BlogPostResponse = {
  data: BlogPostDetail;
  categories: BlogCategory[];
  comments: BlogComment[];
  comments_meta: Paginated<BlogComment>['meta'];
};

export type GroupClassItem = {
  id: number;
  slug: string;
  title: string;
  start_at: string;
  duration: number;
  total_seats: number;
  booked_seats?: number;
  entry_fee: number;
  offline?: number;
  type?: number;
  teacher_id: number;
  teacher_username?: string;
  teacher_name: string;
  teacher_ratings?: number;
  teacher_reviews?: number;
  description?: string;
};

export const teachersApi = {
  list: (params?: { search?: string; page?: number }) =>
    api.get<Paginated<TeacherListing>>('/teachers', { params }),
  get: (slugOrId: string | number) =>
    api.get<{ data: TeacherProfile }>(`/teachers/${slugOrId}`),
  availabilityMeta: (slugOrId: string | number) =>
    api.get<{ data: TeacherAvailabilityMeta }>(`/teachers/${slugOrId}/availability`),
  availabilitySlots: (
    slugOrId: string | number,
    params: { date: string; duration?: number }
  ) =>
    api.get<{ data: TeacherAvailabilitySlots }>(
      `/teachers/${slugOrId}/availability/slots`,
      { params }
    ),
  bookingOptions: (slugOrId: string | number, params?: Record<string, number>) =>
    api.get<{ data: TeacherBookingOptions }>(`/teachers/${slugOrId}/booking-options`, {
      params,
    }),
};

export const chatsApi = {
  privateThread: (receiverId: number, message?: string) =>
    api.post<{
      data: { thread_id?: number; exists?: boolean; needs_message?: boolean };
    }>('/chats/private-thread', { receiver_id: receiverId, message }),
};

export const faqApi = {
  get: () => api.get<FaqData>('/faq'),
};

export type SitemapLink = { label: string; url: string };
export type SitemapGroup = { title: string; links: SitemapLink[] };
export type SitemapSection = { language: string | null; groups: SitemapGroup[] };
export type SitemapHtmlData = { sections: SitemapSection[]; public_url: string };

export const sitemapApi = {
  html: (langId = 1) => api.get<{ data: SitemapHtmlData }>('/sitemap/html', { params: { lang_id: langId } }),
};

export const FORUM_SEARCH_TYPES = {
  ALL: 0,
  ACTIVE: 1,
  ANSWERED: 2,
  POPULAR: 3,
} as const;

export type ForumTag = { id: number; name: string };

export type ForumAuthor = {
  id: number;
  full_name: string;
  first_name?: string;
  username?: string;
};

export type ForumQuestion = {
  id: number;
  title: string;
  slug: string;
  description: string;
  status: number;
  comments_allowed: boolean;
  updated_on: string;
  time_ago: string;
  author: ForumAuthor;
  comments: number;
  likes: number;
  dislikes: number;
  views: number;
  vote_score: number;
  vote_tone: '' | 'success' | 'danger';
  tags?: ForumTag[];
};

export type ForumRecommended = {
  id: number;
  title: string;
  slug: string;
  likes: number;
  comments: number;
  views: number;
};

export type ForumMeta = {
  total_questions: number;
  total_comments: number;
  total_tutors: number;
  popular_tags: ForumTag[];
  top_teachers: TeacherListing[];
  recommended_posts: ForumRecommended[];
};

export type ForumComment = {
  id: number;
  comment: string;
  is_accepted: boolean;
  added_on: string;
  time_ago: string;
  author: ForumAuthor;
  likes: number;
  dislikes: number;
  vote_score: number;
  vote_tone?: '' | 'success' | 'danger';
};

export const forumApi = {
  meta: (params?: { exclude_question_id?: number; tag_ids?: string }) =>
    api.get<ForumMeta>('/forum/meta', { params }),
  questions: (params?: {
    keyword?: string;
    tag_id?: number;
    search_type?: number;
    page?: number;
    per_page?: number;
  }) =>
    api.get<Paginated<ForumQuestion>>('/forum/questions', { params }),
  show: (slug: string, params?: { sort?: 'latest' | 'most_liked' }) =>
    api.get<{
      data: ForumQuestion;
      comments: ForumComment[];
      sidebar: Pick<ForumMeta, 'popular_tags' | 'top_teachers' | 'recommended_posts'>;
    }>(`/forum/questions/${slug}`, { params }),
};

export type VideoContentItem = {
  id: number;
  title: string;
  url: string;
  youtube_id: string | null;
  embed_url: string | null;
};

export type VideoContentListResponse = {
  data: VideoContentItem[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    start_record: number;
    end_record: number;
  };
};

export const videosApi = {
  list: (params?: { page?: number; lang_id?: number; per_page?: number }) =>
    api.get<VideoContentListResponse>('/videos', { params }),
};

export const blogApi = {
  list: (params?: { search?: string; page?: number; category?: number; lang_id?: number }) =>
    api.get<BlogListResponse>('/blog', { params }),
  get: (id: number, params?: { lang_id?: number; comments_page?: number }) =>
    api.get<BlogPostResponse>(`/blog/${id}`, { params }),
  postComment: (id: number, content: string) =>
    api.post<{ message: string }>(`/blog/${id}/comments`, { content }),
};

export const groupClassesApi = {
  list: (params?: { search?: string; page?: number }) =>
    api.get<Paginated<GroupClassItem>>('/group-classes', { params }),
  get: (slug: string) => api.get<{ data: GroupClassItem }>(`/group-classes/${slug}`),
};

export type ApplyToTeachBlock = { block_type: number; html: string };
export type ApplyToTeachFaq = { id: number; title: string; description: string };
export type ApplyToTeachContent = {
  blocks: ApplyToTeachBlock[];
  faqs: ApplyToTeachFaq[];
  contact_html: string;
  terms_page_id: number;
  privacy_page_id: number;
};

export type AffiliateSignupContent = {
  enabled: boolean;
  banner_html: string;
  banner_image: string;
  terms_page_id: number;
  privacy_page_id: number;
  default_timezone: string;
  timezones: { id: string; label: string }[];
};

/** Legacy ExtraPage block types for apply-to-teach */
export const APPLY_TO_TEACH_BLOCKS = {
  BENEFITS: 6,
  FEATURES: 7,
  BECOME_A_TUTOR: 8,
  STATIC_BANNER: 9,
} as const;

export type CmsBlock = {
  block_id: number;
  html: string;
};

export type CmsPageData = {
  id: number;
  identifier: string;
  title: string;
  content: string;
  image_title: string;
  layout: number;
  hero_image: string;
  blocks: CmsBlock[];
};

export const contentApi = {
  contact: () => api.get<{ banner_html: string; left_html: string }>('/content/contact'),
  contactSubmit: (payload: { name: string; email: string; phone: string; message: string }) =>
    api.post<{ message: string }>('/content/contact', payload),
  applyToTeach: () => api.get<ApplyToTeachContent>('/content/apply-to-teach'),
  affiliateSignup: () => api.get<AffiliateSignupContent>('/content/affiliate-signup'),
  cms: (identifier: string, langId?: number) =>
    api.get<{ data: CmsPageData }>(`/content/cms/${identifier}`, {
      params: langId ? { lang_id: langId } : undefined,
    }),
};

export type LessonListingGroup = {
  key: string;
  lessons: Array<Lesson & Record<string, unknown>>;
};

export const lessonsApi = {
  list: (params?: {
    status?: number | string;
    page?: number;
    per_page?: number;
    keyword?: string;
  }) =>
    api.get<{
      data: Array<Lesson & Record<string, unknown>>;
      groups: LessonListingGroup[];
      meta: Paginated<Lesson>['meta'];
    }>('/lessons', { params }),
};

export type TeacherDashboardUpcomingLesson = {
  id: number;
  start_time: string | null;
  offline: boolean;
  lesson_title: string;
  counterparty: {
    id: number;
    first_name: string;
    last_name?: string;
    full_name: string;
  };
};

export type TeacherDashboardUpcomingLessonGroup = {
  key: string;
  lessons: TeacherDashboardUpcomingLesson[];
};

export type TeacherDashboardData = {
  scheduled_lessons: number;
  scheduled_classes: number;
  courses_sold: number;
  total_earnings: number;
  wallet_balance: number;
  upcoming_lesson_groups: TeacherDashboardUpcomingLessonGroup[];
  modules: {
    courses: boolean;
    group_classes: boolean;
  };
};

export const teacherDashboardApi = {
  get: () => api.get<{ data: TeacherDashboardData }>('/teacher/dashboard'),
};

export type LearnerDashboardData = {
  scheduled_lessons: number;
  total_lessons: number;
  total_classes: number;
  total_courses: number;
  wallet_balance: number;
  modules: { courses: boolean; group_classes: boolean };
};

export type DashboardStudent = {
  id: number;
  offpri_id?: number;
  first_name: string;
  last_name?: string;
  full_name: string;
  lessons_offered?: number;
  classes_offered?: number;
  lesson_price_json?: string;
  class_price_json?: string;
  package_price?: number | null;
  learner_deleted?: boolean;
};

export type DashboardTeacherContact = {
  id: number;
  first_name: string;
  last_name?: string;
  full_name: string;
  username?: string;
  lessons_count: number;
};

export type WalletData = {
  balance: number;
  transactions: {
    id: number;
    txn_id_formatted?: string;
    amount: number;
    type: number;
    type_label?: string;
    comment?: string;
    created_at: string | null;
  }[];
};

export type DashboardOrder = {
  id: number;
  amount: number;
  status: number;
  type: number;
  created_at: string;
};

export const learnerDashboardApi = {
  get: () => api.get<{ data: LearnerDashboardData }>('/learner/dashboard'),
};

export const dashboardApi = {
  students: (params?: { keyword?: string; page?: number }) =>
    api.get<Paginated<DashboardStudent>>('/dashboard/students', { params }),
  teachers: (params?: { keyword?: string; page?: number }) =>
    api.get<Paginated<DashboardTeacherContact>>('/dashboard/teachers', { params }),
  wallet: (params?: {
    page?: number;
    keyword?: string;
    date_from?: string;
    date_to?: string;
  }) => api.get<{ data: WalletData; meta: Paginated<unknown>['meta'] }>('/dashboard/wallet', { params }),
  orders: (params?: { page?: number }) =>
    api.get<Paginated<DashboardOrder>>('/dashboard/orders', { params }),
  courses: (params?: { page?: number }) => api.get<Paginated<Course>>('/dashboard/courses', { params }),
  list: (
    endpoint: string,
    params?: { page?: number; per_page?: number; keyword?: string; level?: string; status?: string | number; class_type?: string | number }
  ) => api.get<Paginated<Record<string, unknown>>>(`/dashboard/${endpoint}`, { params }),
};

export type NavLink = {
  id: number;
  caption: string;
  url: string;
  target: string;
  login_protected: number;
};

export type NavGroup = {
  parent: string;
  pages: NavLink[];
};

export type SiteBootstrap = {
  site: {
    name: string;
    email: string;
    phone: string;
    address: string;
    theme: string;
    theme_id?: number;
    logo_url: string;
    currency_code?: string;
    auth?: {
      legacy_origin?: string;
      facebook_login: string | null;
      google_login: string | null;
      apple_login: string | null;
    };
  };
  theme_css: string;
  languages: { id: number; name: string; code: string }[];
  currencies: { id: number; code: string; symbol: string }[];
  social: Record<string, string>;
  navigation: {
    header: NavGroup[];
    footer: { one: NavGroup[]; two: NavGroup[]; three: NavGroup[] };
  };
  labels: Record<string, string>;
  legal_pages?: {
    about: number;
    terms: number;
    privacy: number;
    terms_url: string;
    privacy_url: string;
  };
  demo_login?: {
    default_role: string;
    default: { email: string; password: string };
    accounts: { role: string; label: string; email: string; password: string }[];
  } | null;
  modules?: { courses: boolean; group_classes: boolean };
  search_filters?: Record<number, string>;
  locale?: { lang_id: number; currency_id: number | null };
};

export type SearchSuggestItem = {
  id: number;
  name: string;
  slug: string;
  url: string;
};

export type SearchAutocompleteResult = {
  keyword: string;
  courses: SearchSuggestItem[];
  teachers: SearchSuggestItem[];
  classes: SearchSuggestItem[];
  languages: SearchSuggestItem[];
};

export type HomeTeacher = {
  id: number;
  username?: string;
  full_name: string;
  first_name?: string;
  is_featured: boolean;
  ratings?: number;
  students?: number;
  lessons?: number;
  classes?: number;
  courses?: number;
};

export type HomeData = {
  hero: {
    banner_background: string | null;
    slide_id: number | null;
    slide_image: string | null;
    slide_identifier: string | null;
  };
  content_blocks: {
    id: number;
    identifier: string;
    block_type: number;
    content: string | null;
  }[];
  categories: { id: number; name: string; slug: string }[];
  featured_languages: { id: number; name: string; slug?: string }[];
  courses: Course[];
  courses_by_category?: {
    categories: Record<string, string>;
    courses: Record<string, Course[]>;
  };
  teachers: HomeTeacher[];
  testimonials: { id: number; text: string; user_name: string }[];
  blogs: {
    id: number;
    title: string;
    excerpt: string;
    published_at: string;
    category_name?: string;
  }[];
  classes: {
    id: number;
    slug: string;
    title: string;
    start_at: string;
    duration: number;
    total_seats: number;
    entry_fee: number;
    teacher_id: number;
    teacher_username?: string;
    teacher_name: string;
    teacher_ratings?: number;
    teacher_reviews?: number;
  }[];
  is_course_available?: boolean;
};

export type CourseFilters = {
  categories: {
    id: number;
    name: string;
    sub_categories: { id: number; parent_id: number; name: string }[];
  }[];
  price_range: { min: number; max: number };
  levels: { id: number; name: string }[];
  ratings: { id: number; name: string }[];
  languages: { id: number; name: string }[];
  sort_options: Record<string, string>;
};

export type TeacherListing = {
  id: number;
  username?: string;
  full_name: string;
  first_name?: string;
  last_name?: string;
  biography?: string;
  is_featured: boolean;
  video_link?: string;
  ratings: number;
  reviews: number;
  students: number;
  lessons: number;
  classes: number;
  courses: number;
  min_price: number;
  max_price: number;
  teach_languages: string;
  speak_languages: string;
};

export const siteApi = {
  bootstrap: (langId = 1, currencyId?: number) =>
    api.get<SiteBootstrap>('/site/bootstrap', {
      params: {
        lang_id: langId,
        ...(currencyId ? { currency_id: currencyId } : {}),
      },
    }),
};

export const searchApi = {
  autocomplete: (keyword: string, type: number, langId: number) =>
    api.get<SearchAutocompleteResult>('/search/autocomplete', {
      params: { keyword, type, lang_id: langId },
    }),
};

export const homeApi = {
  get: () => api.get<HomeData>('/home'),
};
