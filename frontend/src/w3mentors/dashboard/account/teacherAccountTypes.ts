import type { TeachLanguageNode } from './TeachLanguageTree';

export type TeacherProfileProgress = {
  percentage: number;
  total_fields: number;
  total_filled: number;
  is_completed: boolean;
  sections: {
    general_profile?: number;
    languages?: number;
    price?: number;
    qualification?: number;
    preference?: number;
    availability?: number;
  };
};

export type TeacherAccountProfile = {
  progress: TeacherProfileProgress;
  languages: {
    teach_language_tree: TeachLanguageNode[];
    selected_teach_lang_ids: number[];
    speak_languages: { id: number; name: string }[];
    selected_speak: { slang_id: number; proficiency: number; proficiency_name: string }[];
    proficiency_levels: { id: number; name: string }[];
  };
  prices: {
    manage_prices: boolean;
    user_languages: {
      utlang_id: number;
      tlang_id: number;
      name: string;
      price: number | null;
      min_price: number;
      max_price: number;
    }[];
    slot_options: number[];
    selected_slots: number[];
    currency_code: string;
  };
  qualifications: TeacherQualification[];
  preferences: {
    type: number;
    options: { id: number; title: string }[];
    selected_ids: number[];
  }[];
};

export type TeacherQualification = {
  id: number;
  experience_type: number;
  title: string;
  institute_name: string;
  institute_address: string;
  description: string;
  start_year: number;
  end_year: number;
  file_name?: string | null;
};

export type ExperienceType = { id: number; key: string };

export type TeacherAccountResponse = {
  data: TeacherAccountProfile;
  experience_types: ExperienceType[];
};
