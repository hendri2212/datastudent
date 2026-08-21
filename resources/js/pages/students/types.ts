export type NullableId = number | null;
export type NumericInput = number | string | null;

export interface School {
    id: number;
    name: string;
}

export interface Major {
    id: number;
    school_id: number;
    code: string;
    name: string;
}

export interface Classroom {
    id: number;
    major_id: number;
    name: string;
}

export interface AcademicYear {
    id: number;
    name: string;
    is_active?: boolean;
}

export interface MasterOption {
    id: number;
    name: string;
}

export interface MasterOptionCode extends MasterOption {
    code?: string;
}

export interface EducationLevel extends MasterOption {
    sort_order?: number;
}

export interface SocialPlatform extends MasterOption {
    icon?: string;
    base_url?: string;
}

export type DocumentType = MasterOption;

export interface StudentEnrollment {
    id: number;
    academic_year_id: number;
    classroom_id: number;
    student_status_id: number;
    enrolled_at: string;
    ended_at?: string | null;
    classroom?: Classroom & { major?: Major & { school?: School } };
    academic_year?: AcademicYear;
    status?: MasterOption;
}

export interface StudentSocial {
    id?: number;
    student_id?: number;
    social_platform_id: number;
    username?: string;
    url?: string;
    is_public?: boolean;
    is_primary?: boolean;
    social_platform?: SocialPlatform;
}

export interface StudentEducationHistory {
    id?: number;
    student_id?: number;
    education_level_id: number;
    school_name: string;
    npsn?: string;
    address?: string;
    entry_year?: number;
    graduation_year?: number;
    final_score?: number;
    is_graduated?: boolean;
    notes?: string;
    education_level?: EducationLevel;
}

export interface StudentHealth {
    id?: number;
    student_id?: number;
    height?: number;
    weight?: number;
    blood_type_id?: number;
    allergies?: string;
    medical_history?: string;
    disabilities?: string;
    medications?: string;
    hospital?: string;
    doctor?: string;
    notes?: string;
    blood_type?: MasterOption;
}

export interface StudentAchievement {
    id?: number;
    student_id?: number;
    title: string;
    organizer?: string;
    level?: string;
    category?: string;
    rank?: number;
    achievement_date?: string;
    certificate?: string;
    description?: string;
}

export interface StudentViolation {
    id?: number;
    student_id?: number;
    title: string;
    point?: number;
    violation_date?: string;
    description?: string;
}

export interface StudentDocument {
    id: number;
    student_id?: number;
    document_type_id?: number;
    original_name?: string;
    stored_name?: string;
    file_name?: string;
    file_path?: string;
    url?: string;
    mime_type?: string;
    file_size?: number;
    extension?: string;
    notes?: string;
    is_verified?: boolean;
    verified_at?: string | null;
    verified_by?: number | null;
    verifier?: { id: number; name?: string };
    document_type?: DocumentType;
}

export interface StudentFamily {
    id?: number;
    student_id?: number;
    father_name?: string;
    father_occupation_id?: number | null;
    father_income_category_id?: number | null;
    father_phone?: string;
    mother_name?: string;
    mother_occupation_id?: number | null;
    mother_income_category_id?: number | null;
    mother_phone?: string;
    guardian_name?: string;
    guardian_occupation_id?: number | null;
    guardian_income_category_id?: number | null;
    guardian_phone?: string;
    emergency_contact_name?: string;
    emergency_contact_phone?: string;
    relationship_type_id?: number | null;
    notes?: string;
    father_occupation?: MasterOption;
    father_income_category?: MasterOption;
    mother_occupation?: MasterOption;
    mother_income_category?: MasterOption;
    guardian_occupation?: MasterOption;
    guardian_income_category?: MasterOption;
    relationship_type?: MasterOption;
}

export interface Student {
    id: number;
    school_id?: number | null;
    major_id?: number | null;
    classroom_id?: number | null;
    academic_year_id?: number | null;
    citizenship_id?: number | null;
    gender_id: number;
    religion_id: number;
    student_status_id?: number | null;
    nisn: string;
    nis: string;
    full_name: string;
    nickname?: string;
    birth_place?: string;
    birth_date?: string;
    phone?: string;
    email?: string;
    address?: string;
    postal_code?: string;
    is_locked?: boolean;
    verified_at?: string | null;
    verified_by?: number | null;
    deleted_at?: string | null;
    verifier?: { id: number; name?: string };
    school?: School;
    major?: Major;
    classroom?: Classroom;
    academic_year?: AcademicYear;
    current_enrollment?: StudentEnrollment;
    gender?: MasterOptionCode;
    religion?: MasterOption;
    student_status?: MasterOption;
    citizenship?: MasterOption;
    family?: StudentFamily;
    health?: StudentHealth;
    education_histories?: StudentEducationHistory[];
    achievements?: StudentAchievement[];
    socials?: StudentSocial[];
    violations?: StudentViolation[];
    documents?: StudentDocument[];
    photo?: string | null;
    photo_url?: string | null;
}

export interface StudentStatistics {
    total: number;
    verified: number;
    unverified: number;
    achievements: number;
    violation_points: number;
    genders: Record<string, number>;
}

export interface StudentFilters {
    search?: string;
    classroom_id?: string | number;
    major_id?: string | number;
    academic_year_id?: string | number;
    citizenship_id?: string | number;
    gender_id?: string | number;
    religion_id?: string | number;
    student_status_id?: string | number;
    blood_type_id?: string | number;
    tab?: 'active' | 'trashed';
}

export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

export interface EducationHistoryForm extends Omit<
    StudentEducationHistory,
    'education_level_id' | 'entry_year' | 'graduation_year' | 'final_score'
> {
    education_level_id: number | null;
    entry_year: number | string;
    graduation_year: number | string;
    final_score: number | string;
}

export interface AchievementForm extends Omit<StudentAchievement, 'rank'> {
    rank: number | string;
}

export interface ViolationForm extends Omit<StudentViolation, 'point'> {
    point: number | string;
}

export interface StudentSocialForm extends Omit<
    StudentSocial,
    'social_platform_id'
> {
    social_platform_id: number | null;
}

export const exportData = {
    url: () => '/students/export', 
}; 
