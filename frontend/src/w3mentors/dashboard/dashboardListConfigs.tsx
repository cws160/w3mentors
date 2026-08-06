import type { DashboardListConfig } from './DashboardListPage';

const col = (
  key: string,
  labelKey: string,
  labelFallback: string
): DashboardListConfig['columns'][number] => ({ key, labelKey, labelFallback });

export const dashboardListConfigs: Record<string, DashboardListConfig> = {
  notifications: {
    endpoint: 'notifications',
    titleKey: 'LBL_NOTIFICATIONS',
    titleFallback: 'Notifications',
    columns: [
      col('title', 'LBL_TITLE', 'Title'),
      col('description', 'LBL_DESCRIPTION', 'Description'),
      col('created_at', 'LBL_DATE', 'Date'),
    ],
  },
  availability: {
    endpoint: 'availability',
    titleKey: 'LBL_AVAILABILITY_CALENDAR',
    titleFallback: 'Availability Calendar',
    teacherOnly: true,
    columns: [
      col('starts_at', 'LBL_START_TIME', 'Start'),
      col('ends_at', 'LBL_END_TIME', 'End'),
    ],
  },
  resources: {
    endpoint: 'resources',
    titleKey: 'LBL_COURSE_RESOURCES',
    titleFallback: 'Course Resources',
    teacherOnly: true,
    columns: [
      col('title', 'LBL_TITLE', 'Title'),
      col('created_at', 'LBL_DATE', 'Date'),
    ],
  },
  'course-edit-requests': {
    endpoint: 'course-edit-requests',
    titleKey: 'LBL_COURSE_EDIT_REQUESTS',
    titleFallback: 'Course Edit Requests',
    teacherOnly: true,
    columns: [
      col('course_title', 'LBL_COURSE', 'Course'),
      col('status', 'LBL_STATUS', 'Status'),
      col('created_at', 'LBL_DATE', 'Date'),
    ],
  },
  plans: {
    endpoint: '',
    titleKey: 'LBL_LESSON_PLAN',
    titleFallback: 'Lesson Plan',
    columns: [],
  },
  packages: {
    endpoint: 'packages',
    titleKey: 'LBL_CLASS_PACKAGES',
    titleFallback: 'Class Packages',
    columns: [
      col('title', 'LBL_TITLE', 'Title'),
      col('starts_at', 'LBL_START_TIME', 'Start'),
      col('status', 'LBL_STATUS', 'Status'),
    ],
  },
  classes: {
    endpoint: 'classes',
    titleKey: 'LBL_GROUP_CLASSES',
    titleFallback: 'Group Classes',
    columns: [
      col('title', 'LBL_TITLE', 'Title'),
      col('starts_at', 'LBL_START_TIME', 'Start'),
      col('ends_at', 'LBL_END_TIME', 'End'),
      col('status', 'LBL_STATUS', 'Status'),
    ],
  },
  issues: {
    endpoint: 'issues',
    titleKey: 'LBL_REPORTED_ISSUES',
    titleFallback: 'Reported Issues',
    columns: [
      col('id', 'LBL_ID', 'ID'),
      col('reporter_name', 'LBL_USER', 'User'),
      col('status', 'LBL_STATUS', 'Status'),
      col('reported_at', 'LBL_DATE', 'Date'),
    ],
  },
  'subscription-plans': {
    endpoint: 'subscription-plans',
    titleKey: 'LBL_SUBSCRIPTIONS',
    titleFallback: 'Subscriptions',
    columns: [
      col('title', 'LBL_PLAN', 'Plan'),
      col('learner_name', 'LBL_LEARNER', 'Learner'),
      col('teacher_name', 'LBL_TEACHER', 'Teacher'),
      col('status', 'LBL_STATUS', 'Status'),
      col('starts_at', 'LBL_START', 'Start'),
      col('ends_at', 'LBL_END', 'End'),
    ],
  },
  subscriptions: {
    endpoint: 'subscriptions',
    titleKey: 'LBL_RECURRING_LESSONS',
    titleFallback: 'Recurring Lessons',
    columns: [
      col('id', 'LBL_ID', 'ID'),
      col('learner_name', 'LBL_LEARNER', 'Learner'),
      col('teacher_name', 'LBL_TEACHER', 'Teacher'),
      col('status', 'LBL_STATUS', 'Status'),
    ],
  },
  questions: {
    endpoint: 'questions',
    titleKey: 'LBL_QUESTION_BANK',
    titleFallback: 'Question Bank',
    teacherOnly: true,
    columns: [
      col('title', 'LBL_TITLE', 'Title'),
      col('type', 'LBL_TYPE', 'Type'),
      col('created_at', 'LBL_DATE', 'Date'),
    ],
  },
  quizzes: {
    endpoint: 'quizzes',
    titleKey: 'LBL_Quizzes',
    titleFallback: 'Quizzes',
    teacherOnly: true,
    columns: [
      col('title', 'LBL_TITLE', 'Title'),
      col('type', 'LBL_TYPE', 'Type'),
      col('created_at', 'LBL_DATE', 'Date'),
    ],
  },
  'wallet/withdraw-requests': {
    endpoint: 'withdrawals',
    titleKey: 'LBL_WITHDRAWS',
    titleFallback: 'Withdrawals',
    columns: [
      col('amount', 'LBL_AMOUNT', 'Amount'),
      col('status', 'LBL_STATUS', 'Status'),
      col('requested_at', 'LBL_DATE', 'Date'),
    ],
  },
  flashcards: {
    endpoint: 'flashcards',
    titleKey: 'LBL_FLASH_CARDS',
    titleFallback: 'Flash Cards',
    columns: [
      col('title', 'LBL_TITLE', 'Title'),
      col('created_at', 'LBL_DATE', 'Date'),
    ],
  },
  giftcard: {
    endpoint: 'giftcards',
    titleKey: 'LBL_GIFT_CARDS',
    titleFallback: 'Gift Cards',
    columns: [
      col('code', 'LBL_CODE', 'Code'),
      col('receiver_name', 'LBL_RECEIVER', 'Receiver'),
      col('status', 'LBL_STATUS', 'Status'),
      col('expires_at', 'LBL_EXPIRY', 'Expiry'),
    ],
  },
  refer: {
    endpoint: 'refer',
    titleKey: 'LBL_REFER_AND_EARN',
    titleFallback: 'Refer and Earn',
    columns: [
      col('points', 'LBL_POINTS', 'Points'),
      col('comment', 'LBL_COMMENT', 'Comment'),
      col('created_at', 'LBL_DATE', 'Date'),
    ],
  },
  forum: {
    endpoint: 'forum',
    titleKey: 'LBL_MY_QUESTIONS',
    titleFallback: 'My Questions',
    columns: [
      col('title', 'LBL_TITLE', 'Title'),
      col('status', 'LBL_STATUS', 'Status'),
      col('created_at', 'LBL_DATE', 'Date'),
    ],
  },
  'forum-tag-requests': {
    endpoint: 'forum-tag-requests',
    titleKey: 'LBL_TAG_REQUESTS',
    titleFallback: 'Tag Requests',
    columns: [
      col('name', 'LBL_TAG', 'Tag'),
      col('status', 'LBL_STATUS', 'Status'),
      col('created_at', 'LBL_DATE', 'Date'),
    ],
  },
  chats: {
    endpoint: 'chats',
    titleKey: 'LBL_MESSAGES',
    titleFallback: 'Messages',
    columns: [
      col('id', 'LBL_THREAD', 'Thread'),
      col('type', 'LBL_TYPE', 'Type'),
      col('updated_at', 'LBL_UPDATED', 'Updated'),
    ],
  },
  'favorite-courses': {
    endpoint: 'favorite-courses',
    titleKey: 'LBL_FAVORITE_COURSES',
    titleFallback: 'Favorite Courses',
    learnerOnly: true,
    columns: [
      col('title', 'LBL_COURSE', 'Course'),
      col('slug', 'LBL_SLUG', 'Slug'),
    ],
  },
  certificates: {
    endpoint: 'certificates',
    titleKey: 'LBL_CERTIFICATES',
    titleFallback: 'Certificates',
    learnerOnly: true,
    columns: [
      col('title', 'LBL_COURSE', 'Course'),
      col('progress', 'LBL_PROGRESS', 'Progress'),
      col('completed_at', 'LBL_COMPLETED_ON', 'Completed'),
    ],
  },
};
