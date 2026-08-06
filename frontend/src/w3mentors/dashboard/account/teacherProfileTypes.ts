export type ProfileOption = { id: number; name: string; label_key?: string; phone_label?: string };
export type TimezoneOption = { id: string; label: string };
export type BookBeforeOption = { id: number; label_key: string };

export type ProfileGeneralForm = {
  values: {
    username: string;
    first_name: string;
    last_name: string;
    gender: number;
    country_id: number;
    phone_code: number;
    phone_number: string;
    timezone: string;
    lang_id: number;
    book_before: number;
    offline_sessions: boolean;
    trial_enabled: boolean;
  };
  options: {
    genders: { id: number; label_key: string }[];
    countries: ProfileOption[];
    timezones: TimezoneOption[];
    notification_languages: ProfileOption[];
    book_before: BookBeforeOption[];
  };
  meta: {
    is_teacher: boolean;
    profile_languages: { id: number; name: string }[];
    offline_sessions_enabled: boolean;
    free_trial_enabled: boolean;
    google_calendar_configured: boolean;
    google_calendar_auth_ready: boolean;
    google_calendar_synced: boolean;
    google_calendar_authorize_url: string | null;
    teacher_profile_url: string | null;
  };
};

export type ProfileGeneralResponse = { data: ProfileGeneralForm };

export type ProfilePhotosForm = {
  has_image: boolean;
  video_link: string;
  is_teacher: boolean;
  max_upload_mb: number;
  allowed_extensions: string[];
  image_urls: {
    xlarge: string;
    large: string;
    medium: string;
    small: string;
  };
};

export type ProfilePhotosResponse = { data: ProfilePhotosForm };

export type ProfileLanguageForm = {
  values: {
    lang_id: number;
    biography: string;
  };
  meta: {
    language_name: string;
    direction: string;
    is_last_language: boolean;
    is_teacher: boolean;
  };
};

export type ProfileLanguageResponse = { data: ProfileLanguageForm };
