import type { DashboardRole } from './dashboardPaths';
import { dashboardPath } from './dashboardPaths';

const DASHBOARD_SPRITE = '/dashboard/images/sprite.svg';
const FORUM_SPRITE = '/images/forum/sprite.svg';

export type DashboardNavItem = {
  /** Symbol id in sprite.svg */
  spriteId: string;
  /** Legacy `icon icon--*` classes on the svg element */
  iconClass: string;
  spritePath?: string;
  labelKey: string;
  labelFallback: string;
  path: string;
  end?: boolean;
  external?: string;
  target?: string;
};

export type DashboardNavGroup = {
  titleKey: string;
  titleFallback: string;
  items: DashboardNavItem[];
};

type NavOptions = {
  courses?: boolean;
  groupClasses?: boolean;
  subscriptionPlans?: boolean;
  flashcards?: boolean;
  referral?: boolean;
};

/** @deprecated Use spriteId — kept for gradual migration */
export type LegacyNavIcon = string;

function item(
  spriteId: string,
  iconClass: string,
  labelKey: string,
  labelFallback: string,
  path: string,
  extra?: Partial<DashboardNavItem>
): DashboardNavItem {
  return {
    spriteId,
    iconClass: `icon ${iconClass}`,
    spritePath: DASHBOARD_SPRITE,
    labelKey,
    labelFallback,
    path,
    ...extra,
  };
}

function forumItem(
  spriteId: string,
  iconClass: string,
  labelKey: string,
  labelFallback: string,
  path: string
): DashboardNavItem {
  return {
    spriteId,
    iconClass: `icon ${iconClass}`,
    spritePath: FORUM_SPRITE,
    labelKey,
    labelFallback,
    path,
  };
}

export function getDashboardNav(
  role: DashboardRole,
  options: NavOptions = {}
): DashboardNavGroup[] {
  const courses = options.courses !== false;
  const groupClasses = options.groupClasses !== false;
  const subPlans = options.subscriptionPlans === true;
  const flashcards = options.flashcards === true;
  const referral = options.referral === true;

  if (role === 'teacher') {
    return [
      {
        titleKey: 'LBL_PROFILE',
        titleFallback: 'Profile',
        items: [
          item('dashboard', 'icon--dashboard me-2', 'LBL_DASHBOARD', 'Dashboard', dashboardPath('teacher'), { end: true }),
          item('settings', 'icon--settings me-2', 'LBL_ACCOUNT_SETTINGS', 'Account Settings', dashboardPath('teacher', 'account')),
          item('calendar', 'icon--settings me-2', 'LBL_AVAILABILITY_CALENDAR', 'Availability Calendar', dashboardPath('teacher', 'availability')),
        ],
      },
      ...(courses
        ? [
            {
              titleKey: 'LBL_COURSES',
              titleFallback: 'Courses',
              items: [
                item('all-courses', 'icon--lesson me-2', 'LBL_ALL_COURSES', 'All Courses', dashboardPath('teacher', 'courses')),
                item('resources', 'icon--lesson me-2', 'LBL_COURSE_RESOURCES', 'Course Resources', dashboardPath('teacher', 'resources')),
                item('editcourse', 'icon--lesson me-2', 'LBL_COURSE_EDIT_REQUESTS', 'Course Edit Requests', dashboardPath('teacher', 'course-edit-requests')),
              ],
            } satisfies DashboardNavGroup,
          ]
        : []),
      {
        titleKey: 'LBL_BOOKING',
        titleFallback: 'Booking',
        items: [
          item('lessons', 'icon--lesson me-2', 'LBL_LESSONS', 'Lessons', dashboardPath('teacher', 'lessons')),
          item('lessons-plan', 'icon--lessons me-2', 'LBL_LESSON_PLAN', 'Lesson Plan', dashboardPath('teacher', 'plans')),
          ...(groupClasses
            ? [
                item('group-classes', 'icon--group-classes me-2', 'LBL_GROUP_CLASSES', 'Group Classes', dashboardPath('teacher', 'classes')),
                item('class-packages', 'icon--group-classes me-2', 'LBL_CLASS_PACKAGES', 'Class Packages', dashboardPath('teacher', 'packages')),
              ]
            : []),
          item('report-issue', 'icon--group-classes me-2 p-1', 'LBL_REPORTED_ISSUES', 'Reported Issues', dashboardPath('teacher', 'issues')),
          ...(subPlans
            ? [item('subscription', 'icon--lesson me-2', 'LBL_SUBSCRIPTIONS', 'Subscriptions', dashboardPath('teacher', 'subscription-plans'))]
            : []),
          item('recurring', 'icon--lesson me-2', 'LBL_RECURRING_LESSONS', 'Recurring Lessons', dashboardPath('teacher', 'subscriptions')),
          item('students', 'icon--students me-2', 'LBL_MY_STUDENTS', 'My Students', dashboardPath('teacher', 'students')),
        ],
      },
      {
        titleKey: 'LBL_QUIZ',
        titleFallback: 'Quiz',
        items: [
          item('question-bank', 'icon--lesson me-2', 'LBL_QUESTION_BANK', 'Question Bank', dashboardPath('teacher', 'questions')),
          item('quiz', 'icon--lesson me-2', 'LBL_Quizzes', 'Quizzes', dashboardPath('teacher', 'quizzes')),
        ],
      },
      {
        titleKey: 'LBL_HISTORY',
        titleFallback: 'History',
        items: [
          item('orders', 'icon--orders me-2', 'LBL_MY_ORDERS', 'My Orders', dashboardPath('teacher', 'orders')),
          item('wallet', 'icon--wallet me-2', 'LBL_WALLET', 'Wallet', dashboardPath('teacher', 'wallet')),
          item('withdrawal-request', 'icon--wallet me-2', 'LBL_WITHDRAWS', 'Withdrawals', dashboardPath('teacher', 'wallet/withdraw-requests')),
        ],
      },
      {
        titleKey: 'LBL_OTHERS',
        titleFallback: 'Others',
        items: [
          ...(flashcards
            ? [item('flashcards', 'icon--flash-cards me-2', 'LBL_FLASH_CARDS', 'Flash Cards', dashboardPath('teacher', 'flashcards'))]
            : []),
          item('giftcards', 'icon--gifts-cards me-2', 'LBL_GIFT_CARDS', 'Gift Cards', dashboardPath('teacher', 'giftcard')),
          ...(referral
            ? [item('refer-earn', 'icon--refer-earn me-2', 'LBL_REFER_AND_EARN', 'Refer and Earn', dashboardPath('teacher', 'refer'))]
            : []),
          item('user-search', 'icon--small icon--user-search me-3', 'LBL_FIND_A_TEACHER', 'Find a Teacher', '/teachers', {
            external: '/teachers',
            target: '_blank',
          }),
        ],
      },
      {
        titleKey: 'LBL_FORUM',
        titleFallback: 'Forum',
        items: [
          forumItem('icon-frm-question', 'icon--frm-question me-3 icon--small', 'LBL_MY_QUESTIONS', 'My Questions', dashboardPath('teacher', 'forum')),
          forumItem('icon-subsc_ftag', 'icon--subsc_ftag me-3 icon--small', 'LBL_SUBSCRIBED_TAGS', 'Subscribed tags', dashboardPath('teacher', 'forum-subscribed-tags')),
          forumItem('icon-add_ftag', 'icon--icon-add_ftag me-3 icon--small', 'LBL_TAG_REQUESTS', 'Tag Requests', dashboardPath('teacher', 'forum-tag-requests')),
        ],
      },
    ];
  }

  return [
    {
      titleKey: 'LBL_PROFILE',
      titleFallback: 'Profile',
      items: [
        item('dashboard', 'icon--dashboard me-2', 'LBL_DASHBOARD', 'Dashboard', dashboardPath('learner'), { end: true }),
        item('settings', 'icon--settings me-2', 'LBL_ACCOUNT_SETTINGS', 'Account Settings', dashboardPath('learner', 'account')),
      ],
    },
    {
      titleKey: 'LBL_BOOKING',
      titleFallback: 'Booking',
      items: [
        item('lessons', 'icon--lesson me-2', 'LBL_LESSONS', 'Lessons', dashboardPath('learner', 'lessons')),
        ...(groupClasses
          ? [
              item('group-classes', 'icon--group-classes me-2', 'LBL_GROUP_CLASSES', 'Group Classes', dashboardPath('learner', 'classes')),
              item('class-packages', 'icon--group-classes me-2', 'LBL_CLASS_PACKAGES', 'Class Packages', dashboardPath('learner', 'packages')),
            ]
          : []),
        ...(courses
          ? [item('all-courses', 'icon--group-classes me-2', 'LBL_COURSES', 'Courses', dashboardPath('learner', 'courses'))]
          : []),
        item('recurring', 'icon--lesson me-2', 'LBL_RECURRING_LESSONS', 'Recurring Lessons', dashboardPath('learner', 'subscriptions')),
        ...(subPlans
          ? [item('subscription', 'icon--lesson me-2', 'LBL_MY_SUBSCRIPTIONS', 'My Subscriptions', dashboardPath('learner', 'subscription-plans'))]
          : []),
        item('report-issue', 'icon--group-classes me-2 p-1', 'LBL_REPORTED_ISSUES', 'Reported Issues', dashboardPath('learner', 'issues')),
        item('students', 'icon--students me-2', 'LBL_MY_TEACHERS', 'My Teachers', dashboardPath('learner', 'teachers')),
      ],
    },
    {
      titleKey: 'LBL_HISTORY',
      titleFallback: 'History',
      items: [
        item('orders', 'icon--orders me-2', 'LBL_ORDERS', 'Orders', dashboardPath('learner', 'orders')),
        item('wallet', 'icon--wallet me-2', 'LBL_WALLET', 'Wallet', dashboardPath('learner', 'wallet')),
        item('withdrawal-request', 'icon--wallet me-2', 'LBL_WITHDRAWS', 'Withdrawals', dashboardPath('learner', 'wallet/withdraw-requests')),
      ],
    },
    {
      titleKey: 'LBL_OTHERS',
      titleFallback: 'Others',
      items: [
        ...(flashcards
          ? [item('flashcards', 'icon--flash-cards me-2', 'LBL_Flash_Cards', 'Flash Cards', dashboardPath('learner', 'flashcards'))]
          : []),
        item('giftcards', 'icon--gifts-cards me-2', 'LBL_Gift_Cards', 'Gift Cards', dashboardPath('learner', 'giftcard')),
        ...(referral
          ? [item('refer-earn', 'icon--refer-earn me-2', 'LBL_REFER_AND_EARN', 'Refer and Earn', dashboardPath('learner', 'refer'))]
          : []),
        item('user-search', 'icon--small icon--user-search me-3', 'LBL_FIND_A_TEACHER', 'Find a Teacher', '/teachers', {
          external: '/teachers',
          target: '_blank',
        }),
        item('apply-to-teach', 'icon--small icon--user-search me-3', 'LBL_APPLY_TO_TEACH', 'Apply to Teach', '/teacher-request', {
          external: '/teacher-request',
          target: '_blank',
        }),
        ...(courses
          ? [
              item('favorite', 'icon--small icon--favorites icon--user-search me-3', 'LBL_FAVORITE_COURSES', 'Favorite Courses', dashboardPath('learner', 'favorite-courses')),
            ]
          : []),
      ],
    },
    {
      titleKey: 'LBL_FORUM',
      titleFallback: 'Forum',
      items: [
        forumItem('icon-frm-question', 'icon--frm-question me-3 icon--small', 'LBL_MY_QUESTIONS', 'My Questions', dashboardPath('learner', 'forum')),
        forumItem('icon-subsc_ftag', 'icon--subsc_ftag me-3 icon--small', 'LBL_SUBSCRIBED_TAGS', 'Subscribed tags', dashboardPath('learner', 'forum-subscribed-tags')),
      ],
    },
  ];
}
