import { Link } from 'react-router-dom';
import { DashboardSpriteIcon } from '../../components/DashboardSpriteIcon';
import { AFILE, formatMoney, imageUrl } from '../../utils/assets';
import { useSite } from '../../context/SiteContext';
import { useDashboardMoney } from '../hooks/useDashboardMoney';

export type TeacherCourseRow = {
  id: number;
  slug: string;
  title: string;
  category_name: string;
  subcategory_name: string;
  price: number;
  type: number;
  sections: number;
  lectures: number;
  students: number;
  ratings: number;
  reviews: number;
  status: number;
  status_label: string;
  status_class: string;
  active: boolean;
  can_preview: boolean;
};

type Props = {
  course: TeacherCourseRow;
};

export function DashboardCourseCard({ course }: Props) {
  const { lbl } = useSite();
  const moneySymbol = useDashboardMoney();
  const categoryLine = [course.category_name, course.subcategory_name].filter(Boolean).join(' / ');

  return (
    <div className="card-course">
      <div className="card-course__colum card-course__colum--first">
        <div className="card-course__media">
          <div className="ratio ratio--16by9">
            <img src={imageUrl(AFILE.COURSE_IMAGE, course.id, 'MEDIUM')} alt={course.title} />
          </div>
        </div>
      </div>
      <div className="card-course__colum card-course__colum--second">
        <div className="card-course__head">
          {categoryLine && (
            <small className="card-course__subtitle uppercase color-gray-900">{categoryLine}</small>
          )}
          <span className="card-course__title">{course.title}</span>
        </div>
        <div className="card-course__body">
          <div className="course-stats">
            <span className="course-stats__item">
              <strong>{formatMoney(course.price, moneySymbol)}</strong>
            </span>
            <span className="course-stats__item">
              {lbl('LBL_LECTURES', 'Lectures')}
              <strong> {course.lectures}</strong>
            </span>
            <span className="course-stats__item">
              {lbl('LBL_STUDENTS', 'Students')}
              <strong> {course.students}</strong>
            </span>
            <div className="course-stats__item">
              <div className="ratings">
                <DashboardSpriteIcon id="rating" className="icon icon--rating" width={16} height={16} />
                <span className="value">{course.ratings}</span>
                <span className="count">({course.reviews})</span>
              </div>
            </div>
          </div>
          <span className={`card-landscape__status badge ${course.status_class} badge--curve badge--small ms-0`}>
            {course.status_label}
          </span>
          {!course.active && (
            <span className="card-landscape__status badge color-danger badge--curve badge--small ms-0">
              {lbl('LBL_INACTIVE', 'Inactive')}
            </span>
          )}
        </div>
      </div>
      <div className="card-course__colum card-course__colum--third">
        <div className="actions-group">
          {course.can_preview && (
            <Link
              to={`/courses/${course.slug}`}
              className="btn btn--bordered btn--shadow btn--equal m-1 is-hover"
              title={lbl('LBL_PREVIEW', 'Preview')}
            >
              <DashboardSpriteIcon id="view-icon" className="icon icon--enter icon--18" width={18} height={18} />
              <div className="tooltip tooltip--top bg-black">{lbl('LBL_PREVIEW', 'Preview')}</div>
            </Link>
          )}
          <Link
            to={`/courses/${course.slug}`}
            className="btn btn--equal btn--shadow btn--bordered is-hover m-1"
            title={lbl('LBL_VIEW', 'View')}
          >
            <DashboardSpriteIcon id="view-icon" className="icon icon--enter icon--18" width={18} height={18} />
            <div className="tooltip tooltip--top bg-black">{lbl('LBL_VIEW', 'View')}</div>
          </Link>
        </div>
      </div>
    </div>
  );
}
