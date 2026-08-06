import type { Course } from '../../../api/client';
import type { TeacherCourseRow } from '../components/DashboardCourseCard';

function statusMeta(status: number): { label: string; className: string } {
  if (status === 3) {
    return { label: 'Published', className: 'color-success' };
  }
  if (status === 2) {
    return { label: 'Submitted for approval', className: 'color-info' };
  }
  return { label: 'Drafted', className: 'color-warning' };
}

/** Map legacy /dashboard/courses payload when search shape is unavailable. */
export function courseToTeacherRow(course: Course & Record<string, unknown>): TeacherCourseRow {
  const status = Number(course.status ?? 1);
  const meta = statusMeta(status);

  return {
    id: course.id,
    slug: course.slug,
    title: (course.title as string) ?? course.slug,
    category_name: String(course.category_name ?? ''),
    subcategory_name: String(course.subcategory_name ?? ''),
    price: Number(course.price ?? 0),
    type: Number(course.type ?? 0),
    lectures: Number(course.lectures ?? 0),
    students: Number(course.students ?? 0),
    ratings: Number(course.ratings ?? 0),
    reviews: Number(course.reviews ?? 0),
    sections: Number(course.sections ?? 0),
    status,
    status_label: String(course.status_label ?? meta.label),
    status_class: String(course.status_class ?? meta.className),
    active: course.active !== false && course.active !== 0,
    can_preview: Number(course.sections ?? 0) > 0 && Number(course.lectures ?? 0) > 0,
  };
}
