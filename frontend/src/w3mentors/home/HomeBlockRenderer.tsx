import AOS from 'aos';
import { useEffect, useState } from 'react';
import { refreshW3MentorsSliders } from '../lib/w3mentors-sliders';
import { Link } from 'react-router-dom';
import type { HomeData } from '../../api/client';
import { AFILE, firstChar, formatMoney, imageUrl, truncate } from '../utils/assets';
import { normalizeLegacyHtml } from '../utils/legacyHtml';
import { ShortCourseCard } from '../components/ShortCourseCard';
import { SpriteIcon } from '../components/SpriteIcon';

const BLOCK = {
  WHY_US: 2,
  BROWSE_TUTOR: 3,
  SERVICES: 10,
  CLASSES: 12,
  ONLINE_COURSES: 13,
  TOP_TEACHERS: 14,
  TESTIMONIALS: 15,
  BLOGS: 16,
  CATEGORIES: 17,
  HOW_TO_START: 18,
  FEATURED_LANGUAGES: 20,
  BROWSE_COURSES: 21,
  CREATING_COMMUNITY: 22,
  COURSE_WITH_CATEGORIES: 23,
  JOIN_NOW: 24,
  SUBSCRIPTION: 25,
} as const;

type Props = {
  data: HomeData;
  lbl: (key: string, fallback?: string) => string;
};

export function HomeBlockRenderer({ data, lbl }: Props) {
  const heroStyle: React.CSSProperties | undefined = data.hero?.banner_background
    ? {
        backgroundImage: `url(${data.hero.banner_background})`,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
      }
    : undefined;

  const communityBlock = (data.content_blocks ?? []).find(
    (block) => Number(block.block_type) === BLOCK.CREATING_COMMUNITY
  );
  const otherBlocks = (data.content_blocks ?? []).filter(
    (block) => Number(block.block_type) !== BLOCK.CREATING_COMMUNITY
  );

  return (
    <>
      <section className="section section--hero" style={heroStyle}>
        <div className="container container--xxl">
          <div className="hero-panel">
            {data.hero?.slide_image && (
              <div className="hero-panel__media" data-aos="fade-up" data-aos-duration="2000">
                <figure>
                  <img src={data.hero.slide_image} alt={data.hero.slide_identifier ?? ''} loading="lazy" />
                </figure>
              </div>
            )}
            <div className="hero-panel__content" data-aos="fade-up" data-aos-duration="1000">
              <div className="content">
                <h2>{lbl('LBL_SLIDER_TITLE_TEXT', 'Learn from the best online')}</h2>
                <p>{lbl('LBL_SLIDER_DESCRIPTION_TEXT', 'Expert tutors and courses in one place')}</p>
                <Link to="/teachers" className="btn btn--primary-bordered">
                  {lbl('LBL_FIND_YOUR_TUTORS', 'Find your tutors')}
                </Link>
              </div>
            </div>
          </div>
        </div>
      </section>

      {communityBlock ? <CreatingCommunitySection content={communityBlock.content} /> : null}

      {otherBlocks.map((block) => (
        <HomeBlock key={block.id} block={block} data={data} lbl={lbl} />
      ))}
    </>
  );
}

function CreatingCommunitySection({ content }: { content: string | null }) {
  useEffect(() => {
    const id = window.setTimeout(() => AOS.refresh(), 80);
    return () => window.clearTimeout(id);
  }, [content]);

  if (!content?.trim()) {
    return null;
  }

  return (
    <div
      className="section--community-stats"
      dangerouslySetInnerHTML={{ __html: normalizeLegacyHtml(content) }}
    />
  );
}

function HomeBlock({
  block,
  data,
  lbl,
}: {
  block: HomeData['content_blocks'][number];
  data: HomeData;
  lbl: Props['lbl'];
}) {
  const blockType = Number(block.block_type);

  switch (blockType) {
    case BLOCK.CATEGORIES:
      return data.categories?.length ? <CategoriesSection categories={data.categories} lbl={lbl} /> : null;
    case BLOCK.FEATURED_LANGUAGES:
      return data.featured_languages?.length ? (
        <FeaturedLanguagesSection languages={data.featured_languages} lbl={lbl} />
      ) : null;
    case BLOCK.ONLINE_COURSES:
      return data.courses?.length ? <PopularCoursesSection courses={data.courses} lbl={lbl} /> : null;
    case BLOCK.TOP_TEACHERS:
      return data.teachers?.length ? (
        <TopTeachersSection teachers={data.teachers} isCourseAvailable={data.is_course_available ?? true} lbl={lbl} />
      ) : null;
    case BLOCK.TESTIMONIALS:
      return data.testimonials?.length ? <TestimonialsSection testimonials={data.testimonials} lbl={lbl} /> : null;
    case BLOCK.BLOGS:
      return data.blogs?.length ? <LatestBlogsSection blogs={data.blogs} lbl={lbl} /> : null;
    case BLOCK.CLASSES:
      return data.classes?.length ? <UpcomingClassesSection classes={data.classes} lbl={lbl} /> : null;
    case BLOCK.COURSE_WITH_CATEGORIES:
      return data.courses_by_category ? (
        <CoursesWithCategoriesSection data={data.courses_by_category} lbl={lbl} />
      ) : null;
    case BLOCK.WHY_US:
      return block.content ? (
        <section className="section">
          <div dangerouslySetInnerHTML={{ __html: normalizeLegacyHtml(block.content) }} />
        </section>
      ) : null;
    default:
      return block.content ? (
        <div dangerouslySetInnerHTML={{ __html: normalizeLegacyHtml(block.content) }} />
      ) : null;
  }
}

function CategoriesSection({
  categories,
  lbl,
}: {
  categories: HomeData['categories'];
  lbl: Props['lbl'];
}) {
  useEffect(() => {
    const id = window.setTimeout(() => AOS.refresh(), 80);
    return () => window.clearTimeout(id);
  }, [categories.length]);

  return (
    <section className="section section--categories" data-aos="fade-up">
      <div className="container container--xxl">
        <div className="section__header">
          <h2>{lbl('LBL_MOST_DEMANDING_CATEGORIES', 'Most demanding categories')}</h2>
        </div>
        <div className="section__body">
          <div className="colum-grid">
            {categories.map((cat) => (
              <div key={cat.id} className="colum-grid__item">
                <Link to={`/courses?catg=${cat.id}`} className="colum-tile">
                  <figure className="colum-tile__media">
                    <img src={imageUrl(AFILE.CATEGORY_IMAGE, cat.id, 'LARGE')} alt={cat.name} loading="lazy" />
                  </figure>
                  <span className="colum-tile__content">
                    <h6>{truncate(cat.name)}</h6>
                  </span>
                </Link>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}

function FeaturedLanguagesSection({
  languages,
  lbl,
}: {
  languages: HomeData['featured_languages'];
  lbl: Props['lbl'];
}) {
  return (
    <section className="section">
      <div className="container container--xl">
        <div className="section__header" data-aos="fade-up" data-aos-duration="1000">
          <h2>{lbl('LBL_POPULAR_LANGUAGES', 'Popular languages')}</h2>
        </div>
        <div className="section__body" data-aos="fade-up" data-aos-duration="1000">
          <div className="flag-wrapper">
            {languages.map((lang) => (
              <div key={lang.id} className="flag__box">
                <div className="flag__media">
                  <img src={imageUrl(AFILE.TEACHING_LANGUAGE, lang.id, 'LARGE')} alt={lang.name} loading="lazy" />
                </div>
                <div className="flag__name">
                  <span>{lang.name}</span>
                </div>
                <span className="link__arrow" />
                <Link
                  className="flag__action"
                  aria-label={lang.name}
                  to={lang.slug ? `/teachers/languages/${lang.slug}` : '/teachers'}
                />
              </div>
            ))}
          </div>
        </div>
        <div className="section__footer text-center" data-aos="fade-up" data-aos-duration="1000">
          <Link to="/teachers" className="text-button">
            {lbl('LBL_EXPLORE_ALL_SUBJECTS', 'Explore all subjects')}
          </Link>
        </div>
      </div>
    </section>
  );
}

function PopularCoursesSection({ courses, lbl }: { courses: HomeData['courses']; lbl: Props['lbl'] }) {
  return (
    <section className="section section--cardslider">
      <div className="container container--xxl">
        <div className="section__header mb-xl-3" data-aos="fade-up" data-aos-duration="1000">
          <h2>{lbl('LBL_ONLINE_COURSES_AT_ONE_PLACE', 'Online courses at one place')}</h2>
        </div>
        <div className="section__body" data-aos="fade-up" data-aos-duration="1000">
          <div className="slider slider-oneforth slider-oneforth-js">
            {courses.map((course) => (
              <ShortCourseCard key={course.id} course={course} lbl={lbl} />
            ))}
          </div>
        </div>
        <div className="section__footer text-center" data-aos="fade-up" data-aos-duration="1000">
          <Link to="/courses" className="text-button">
            {lbl('LBL_SHOW_ALL_COURSES', 'Show all courses')}
          </Link>
        </div>
      </div>
    </section>
  );
}

function TopTeachersSection({
  teachers,
  isCourseAvailable,
  lbl,
}: {
  teachers: HomeData['teachers'];
  isCourseAvailable: boolean;
  lbl: Props['lbl'];
}) {
  return (
    <section className="section">
      <div className="container container--xxl">
        <div className="section__header" data-aos="fade-up" data-aos-duration="1000">
          <h2>{lbl('LBL_HOME_TOP_RATED_TEACHERS_TITLE', 'Top rated teachers')}</h2>
        </div>
        <div className="section__body" data-aos="fade-up" data-aos-duration="1000">
          <div className="teacher-wrapper">
            <div className="slider slider-onefifth slider-onefifth-js">
              {teachers.map((teacher) => (
                <div key={teacher.id} className="slider__item">
                  <div className="tile-cover">
                    <div className="tile">
                      <div className="tile__head">
                        <div className="tile__media ratio ratio--3by4">
                          <Link to={teacher.username ? `/teachers/${teacher.username}` : `/teachers/${teacher.id}`}>
                            <img
                              src={imageUrl(AFILE.USER_PROFILE, teacher.id, 'MEDIUM')}
                              alt={teacher.full_name}
                              loading="lazy"
                            />
                          </Link>
                        </div>
                        <div className="rating">
                          <SpriteIcon id="rating" className="rating__media" />
                          <span className="rating__value">{teacher.ratings ?? 0}</span>
                        </div>
                      </div>
                      <div className="tile__body">
                        <Link
                          to={teacher.username ? `/teachers/${teacher.username}` : `/teachers/${teacher.id}`}
                          className="tile__action-btn"
                        >
                          {lbl('LBL_VIEW_DETAIL', 'View detail')}
                        </Link>
                        <Link
                          className="tile__title"
                          to={teacher.username ? `/teachers/${teacher.username}` : `/teachers/${teacher.id}`}
                        >
                          <h4>{teacher.full_name}</h4>
                        </Link>
                        <div className="card-element justify-content-center">
                          <span className="card-element__item">
                            {teacher.students ?? 0} {lbl('LBL_STUDENTS', 'Students')}
                          </span>
                          <span className="card-element__item">
                            {(teacher.lessons ?? 0) + (teacher.classes ?? 0)} {lbl('LBL_SESSIONS', 'Sessions')}
                          </span>
                          {isCourseAvailable && (
                            <span className="card-element__item">
                              {teacher.courses ?? 0} {lbl('LBL_COURSES', 'Courses')}
                            </span>
                          )}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
        <div className="section__footer text-center" data-aos="fade-up" data-aos-duration="1000">
          <Link to="/teachers" className="text-button">
            {lbl('LBL_EXPLORE_ALL_TUTORS', 'Explore all tutors')}
          </Link>
        </div>
      </div>
    </section>
  );
}

function TestimonialsSection({
  testimonials,
  lbl,
}: {
  testimonials: HomeData['testimonials'];
  lbl: Props['lbl'];
}) {
  return (
    <section className="section bg-grey">
      <div className="container container--xxl">
        <div className="section__header" data-aos="fade-up" data-aos-duration="1000">
          <h2>{lbl('LBL_WHAT_OUR_CLIENTS_SAY', 'What our clients say')}</h2>
        </div>
        <div className="section__body" data-aos="fade-up" data-aos-duration="1000">
          <div className="testimonial-wrapper">
            <div className="testimonials-thumb js--testimonials-thumb">
              {testimonials.map((t) => (
                <div key={t.id} className="testimonial">
                  <div className="testimonial-user">
                    <div className="testimonial-user__pic">
                      <img
                        src={imageUrl(AFILE.TESTIMONIAL, t.id, 'LARGE')}
                        alt={t.user_name}
                        loading="lazy"
                      />
                    </div>
                    <div className="testimonial-user__details">
                      <div className="name">{t.user_name}</div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
            <div className="testimonials-main js--testimonials-main">
              {testimonials.map((t) => (
                <div key={t.id} className="testimonial">
                  <div className="testimonial-content">{t.text}</div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

function LatestBlogsSection({ blogs, lbl }: { blogs: HomeData['blogs']; lbl: Props['lbl'] }) {
  return (
    <section className="section section--cardslider">
      <div className="container container--xxl">
        <div className="section__header" data-aos="fade-up" data-aos-duration="1000">
          <h2>{lbl('LBL_LATEST_BLOGS', 'Latest blogs')}</h2>
        </div>
        <div className="section__body" data-aos="fade-up" data-aos-duration="1000">
          <div className="blog-wrapper">
            <div className="slider slider--onehalf slider-onethird-js">
              {blogs.map((post) => (
                <div key={post.id} className="slider__item">
                  <div className="blog-card">
                    <div className="blog-card__head">
                      <div className="blog-card-media ratio ratio--3by2">
                        <Link to={`/blog/${post.id}`}>
                          <img src={imageUrl(AFILE.BLOG_POST, post.id, 'MEDIUM')} alt={post.title} loading="lazy" />
                        </Link>
                      </div>
                    </div>
                    <div className="blog-card__body">
                      <div className="blog-card-details">
                        <div className="blog-card-tags">
                          {post.category_name && <div className="blog-card-category">{post.category_name}</div>}
                          <div className="blog-card-date">
                            <span>{new Date(post.published_at).toLocaleDateString()}</span>
                          </div>
                        </div>
                        <div className="blog-card-title">
                          <h3>{post.title}</h3>
                        </div>
                        <Link to={`/blog/${post.id}`} className="btn btn--primary-bordered">
                          {lbl('LBL_VIEW_BLOG', 'View blog')}
                        </Link>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
        <div className="section__footer text-center" data-aos="fade-up" data-aos-duration="1000">
          <Link to="/blog" className="text-button">
            {lbl('LBL_VIEW_ALL', 'View all')}
          </Link>
        </div>
      </div>
    </section>
  );
}

function UpcomingClassesSection({
  classes,
  lbl,
}: {
  classes: HomeData['classes'];
  lbl: Props['lbl'];
}) {
  return (
    <section className="section section--cardslider">
      <div className="container container--xxl">
        <div className="section__header mb-xl-3" data-aos="fade-up" data-aos-duration="1000">
          <h2>{lbl('LBL_UPCOMING_GROUP_CLASSES', 'Upcoming group classes')}</h2>
        </div>
        <div className="section__body" data-aos="fade-up" data-aos-duration="1000">
          <div className="slider slider-oneforth slider-oneforth-js">
            {classes.map((cls) => (
              <div key={cls.id} className="slider__item">
                <div className="card-cover">
                  <div className="card-class">
                    <div className="card-class__head">
                      <div className="card-class__media ratio ratio--16by9">
                        <Link to={`/group-classes/${cls.slug}`}>
                          <img
                            src={imageUrl(AFILE.GROUP_CLASS_BANNER, cls.id, 'MEDIUM')}
                            alt={cls.title}
                            loading="lazy"
                          />
                        </Link>
                      </div>
                      <div className="card-class__action">
                        <Link to={`/group-classes/${cls.slug}`} className="btn btn--white">
                          {lbl('LBL_BOOK_NOW', 'Book now')}
                        </Link>
                      </div>
                    </div>
                    <div className="card-class__body">
                      <span className="card-class__date">{new Date(cls.start_at).toLocaleDateString()}</span>
                      <div className="card-class__title">
                        <Link to={`/group-classes/${cls.slug}`}>{cls.title}</Link>
                      </div>
                      <div className="card-element">
                        <div className="card-element__item">
                          <span>
                            {cls.duration} {lbl('LBL_Minutes', 'Minutes')}
                          </span>
                        </div>
                        <div className="card-element__item">
                          <span>
                            {cls.total_seats} {lbl('LBL_SEATS', 'Seats')}
                          </span>
                        </div>
                      </div>
                      <h4 className="bold-700 price-value">{formatMoney(cls.entry_fee)}</h4>
                    </div>
                    <div className="card-class__footer">
                      <Link
                        to={cls.teacher_username ? `/teachers/${cls.teacher_username}` : '#'}
                        className="profile-meta d-flex align-items-center"
                      >
                        <div className="profile-meta__media">
                          <span className="avtar avtar--medium avtar--round" data-title={firstChar(cls.teacher_name)}>
                            <img
                              src={imageUrl(AFILE.USER_PROFILE, cls.teacher_id, 'SMALL')}
                              alt={cls.teacher_name}
                              loading="lazy"
                            />
                          </span>
                        </div>
                        <div className="profile-meta__details">
                          <h6 className="profile-meta__title">{cls.teacher_name}</h6>
                          <div className="rating">
                            <SpriteIcon id="rating" className="rating__media" />
                            <span className="rating__value">{cls.teacher_ratings ?? 0}</span>
                            <span className="rating__count">({cls.teacher_reviews ?? 0})</span>
                          </div>
                        </div>
                      </Link>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
        <div className="section__footer text-center" data-aos="fade-up" data-aos-duration="1000">
          <Link to="/group-classes" className="text-button">
            {lbl('LBL_VIEW_ALL', 'View all')}
          </Link>
        </div>
      </div>
    </section>
  );
}

function CoursesWithCategoriesSection({
  data: grouped,
  lbl,
}: {
  data: NonNullable<HomeData['courses_by_category']>;
  lbl: Props['lbl'];
}) {
  const categoryIds = Object.keys(grouped.courses ?? {});
  const [activeId, setActiveId] = useState(categoryIds[0] ?? '');

  useEffect(() => {
    if (!activeId) return;
    const cancel = window.setTimeout(() => refreshW3MentorsSliders(), 80);
    return () => window.clearTimeout(cancel);
  }, [activeId]);

  if (!categoryIds.length) return null;

  return (
    <section className="section">
      <div className="container container--xxl">
        <div className="section__header" data-aos="fade-up" data-aos-duration="1000">
          <h2>{lbl('LBL_HOME_POPULAR_COURSES_TITLE', 'Popular courses')}</h2>
        </div>
        <div className="section__body" data-aos="fade-up" data-aos-duration="1000">
          <nav className="inline-tabs">
            <ul>
              {categoryIds.map((id) => (
                <li key={id}>
                  <button
                    type="button"
                    className={`inline-tabs-link ${activeId === id ? 'is-active' : ''}`}
                    onClick={() => setActiveId(id)}
                  >
                    {truncate(grouped.categories[id] ?? id, 50)}
                  </button>
                </li>
              ))}
            </ul>
          </nav>
          <div className="inline-content-container">
            {categoryIds.map((id) => (
              <div
                key={id}
                id={`inline-content-c${id}`}
                className={`inline-content ${activeId === id ? 'visible' : ''}`}
              >
                <div className="slider slider-oneforth slider-oneforth-js">
                  {(grouped.courses[id] ?? []).map((course) => (
                    <ShortCourseCard key={course.id} course={course} lbl={lbl} />
                  ))}
                </div>
                <div className="align-center inline-cta" data-aos="fade-up" data-aos-duration="1000">
                  <Link to={`/courses?catg=${id}`} className="text-button">
                    {lbl('LBL_EXPLORE_{category}', 'Explore category').replace(
                      '{category}',
                      grouped.categories[id] ?? ''
                    )}
                  </Link>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
