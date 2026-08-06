import { useEffect, useMemo, useState } from 'react';
import { api } from '../../../api/client';
import { useSite } from '../../context/SiteContext';
import {
  TeacherExperienceSection,
  TeacherLanguagesSection,
  TeacherPriceSection,
  TeacherSkillsSection,
  useTeacherAccountProfile,
} from '../account/TeacherAccountSections';
import { AccountCookieConsentSection } from '../account/AccountCookieConsentSection';
import { AccountDeleteSection } from '../account/AccountDeleteSection';
import { AccountPaymentsSection } from '../account/AccountPaymentsSection';
import { TeacherAddressesSection } from '../account/TeacherAddressesSection';
import { TeacherPasswordEmailSection } from '../account/TeacherPasswordEmailSection';
import { TeacherProfileInfoSection } from '../account/TeacherProfileInfoSection';
import { TeacherSiteLanguageSection } from '../account/TeacherSiteLanguageSection';
import { DashboardPanelLayout, type DashboardPanelTab } from '../components/DashboardPanelLayout';
import { ProfileProgressBar } from '../components/ProfileProgressBar';
import { useDashboardRole } from '../DashboardShell';

export function DashboardAccountPage() {
  const role = useDashboardRole();
  const { lbl } = useSite();
  const [activeTab, setActiveTab] = useState('personal');

  const isTeacher = role === 'teacher';
  const { profile, experienceTypes, loading: teacherLoading, setProfile } =
    useTeacherAccountProfile(isTeacher);

  const progress = profile?.progress;
  const sections = progress?.sections ?? {};
  const [payoutsAvailable, setPayoutsAvailable] = useState(false);

  useEffect(() => {
    if (!isTeacher) {
      setPayoutsAvailable(false);
      return;
    }
    api
      .get('/users/me/payments')
      .then(() => setPayoutsAvailable(true))
      .catch((err: { response?: { status?: number } }) => {
        setPayoutsAvailable(err.response?.status !== 403);
      });
  }, [isTeacher]);

  useEffect(() => {
    if (!payoutsAvailable && activeTab === 'payments') {
      setActiveTab('password');
    }
  }, [payoutsAvailable, activeTab]);

  const tabs = useMemo((): DashboardPanelTab[] => {
    const base: DashboardPanelTab[] = [
      {
        id: 'personal',
        label: lbl('LBL_PERSONAL_INFO', 'Personal info'),
        completed: Boolean(sections.general_profile),
      },
    ];
    if (isTeacher) {
      base.push(
        {
          id: 'languages',
          label: lbl('LBL_TEACHER_LANGUAGES', 'Subjects'),
          progress: true,
          completed: Boolean(sections.languages),
        },
        {
          id: 'price',
          label: lbl('LBL_PRICE', 'Price'),
          progress: true,
          completed: Boolean(sections.price),
        },
        {
          id: 'experience',
          label: lbl('LBL_EXPERIENCE', 'Experience'),
          progress: true,
          completed: Boolean(sections.qualification),
        },
        {
          id: 'skills',
          label: lbl('LBL_SKILLS', 'Skills'),
          progress: true,
          completed: Boolean(sections.preference),
        },
        { id: 'addresses', label: lbl('LBL_ADDRESSES', 'Addresses') }
      );
      if (payoutsAvailable) {
        base.push({ id: 'payments', label: lbl('LBL_PAYMENTS', 'Payments') });
      }
    }
    base.push({ id: 'password', label: lbl('LBL_PASSWORD_/_EMAIL', 'Password / Email') });
    base.push({ id: 'cookie', label: lbl('LBL_COOKIE_CONSENT', 'Cookie consent') });
    base.push({ id: 'delete', label: lbl('LBL_DELETE_MY_ACCOUNT', 'Delete my account') });
    if (!isTeacher) {
      base.push({ id: 'language', label: lbl('LBL_LANGUAGE', 'Language') });
    }
    return base;
  }, [isTeacher, lbl, payoutsAvailable, sections]);

  const teacherHeader =
    isTeacher && progress ? (
      <div className="infobar mb-4">
        <div className="row justify-content-between align-items-start">
          <div className="col-lg-8 col-sm-8">
            <div className="d-flex">
              <div className="infobar__media me-4">
                <div
                  className={`infobar__media-icon${
                    progress.is_completed
                      ? ' infobar__media-icon--tick'
                      : ' infobar__media-icon--alert'
                  }`}
                >
                  {!progress.is_completed && '!'}
                </div>
              </div>
              <div className="infobar__content">
                <h6 className="mb-1">{lbl('LBL_COMPLETE_YOUR_PROFILE', 'Complete your profile')}</h6>
                <p className="m-0">
                  {lbl('LBL_PROFILE_INFO_HEADING', 'Finish your profile to start getting bookings.')}
                </p>
              </div>
            </div>
          </div>
          <div className="col-lg-3 col-sm-4">
            <ProfileProgressBar
              totalFilled={progress.total_filled}
              totalFields={progress.total_fields}
            />
          </div>
        </div>
      </div>
    ) : null;

  const teacherSectionBase = {
    profile,
    experienceTypes,
    loading: teacherLoading,
    onSaved: setProfile,
  };

  let panelContent: React.ReactNode;
  switch (activeTab) {
    case 'personal':
      panelContent = (
        <TeacherProfileInfoSection
          onNextTab={isTeacher ? () => setActiveTab('languages') : undefined}
        />
      );
      break;
    case 'addresses':
      panelContent = <TeacherAddressesSection />;
      break;
    case 'payments':
      panelContent = <AccountPaymentsSection />;
      break;
    case 'cookie':
      panelContent = <AccountCookieConsentSection />;
      break;
    case 'delete':
      panelContent = <AccountDeleteSection />;
      break;
    case 'password':
      panelContent = <TeacherPasswordEmailSection />;
      break;
    case 'language':
      panelContent = <TeacherSiteLanguageSection />;
      break;
    case 'languages':
      panelContent = (
        <TeacherLanguagesSection {...teacherSectionBase} onNextTab={() => setActiveTab('price')} />
      );
      break;
    case 'price':
      panelContent = (
        <TeacherPriceSection {...teacherSectionBase} onNextTab={() => setActiveTab('experience')} />
      );
      break;
    case 'experience':
      panelContent = (
        <TeacherExperienceSection {...teacherSectionBase} onNextTab={() => setActiveTab('skills')} />
      );
      break;
    case 'skills':
      panelContent = <TeacherSkillsSection {...teacherSectionBase} />;
      break;
    default:
      panelContent = null;
  }

  return (
    <DashboardPanelLayout
      title={lbl('LBL_ACCOUNT_SETTINGS', 'Account Settings')}
      tabs={tabs}
      activeTab={activeTab}
      onTabChange={setActiveTab}
      header={teacherHeader}
    >
      {panelContent}
    </DashboardPanelLayout>
  );
}
