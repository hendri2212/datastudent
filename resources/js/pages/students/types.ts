export interface School {
    id: number;
    name: string;
}

export interface Major {
    id: number;
    code: string;
    name: string;
}

export interface Classroom {
    id: number;
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

export interface EducationLevel {
    id: number;
    name: string;
    sort_order?: number;
}

export interface SocialPlatform {
    id: number;
    name: string;
    icon?: string;
    base_url?: string;
}

export interface DocumentType {
    id: number;
    name: string;
}

export interface StudentSocial {
    id?: number;
    student_id?: number;
    social_platform_id: number;
    username: string;
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
    entry_year?: number;       // Tahun masuk
    graduation_year?: number;  // Tahun lulus
    final_score?: number;      // Nilai akhir / IPK (decimal 5,2)
    is_graduated?: boolean;    // Status kelulusan (boolean)
    notes?: string;            // Catatan
    education_level?: EducationLevel;
}

export interface StudentHealth {
    id?: number;
    student_id?: number;
    height?: number;
    weight?: number;
    blood_type_id?: number;
    allergies?: string;
    medical_history?: string; // <-- Ditambahkan sesuai skema database
    medicalHistory?: string;
    disabilities?: string;
    medications?: string;
    hospital?: string;
    doctor?: string;
    notes?: string;
    blood_type?: MasterOption;
    bloodType?: MasterOption;
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
    file_path?: string;
    url?: string;
    mime_type?: string;
    file_size?: number;
    extension?: string;
    notes?: string;
    is_verified?: boolean;
    verified_at?: string | null;
    verified_by?: number | null;
    verifier?: {
        id: number;
        name?: string;
    };
    document_type?: DocumentType;
}

export interface DocumentType {
    id: number;
    name: string;
}

// Interface Khusus Keluarga (Disesuaikan dengan Migrasi student_family)
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

    // Relasi
    father_occupation?: MasterOption;
    fatherOccupation?: MasterOption;
    father_income_category?: MasterOption;
    fatherIncomeCategory?: MasterOption;
    mother_occupation?: MasterOption;
    motherOccupation?: MasterOption;
    mother_income_category?: MasterOption;
    motherIncomeCategory?: MasterOption;
    guardian_occupation?: MasterOption;
    guardianOccupation?: MasterOption;
    guardian_income_category?: MasterOption;
    guardianIncomeCategory?: MasterOption;
    relationship_type?: MasterOption;
    relationshipType?: MasterOption;
}

export interface Student {
    id: number;
    school_id?: number;
    major_id?: number;
    classroom_id?: number;
    academic_year_id: number;
    citizenship_id?: number;
    gender_id: number;
    religion_id?: number;
    student_status_id?: number;
    nisn: string;
    nis?: string;
    full_name: string;
    nickname?: string;
    birth_place?: string;
    birth_date?: string;
    phone?: string;
    email?: string;
    address?: string;
    postal_code?: string;
    document_type_id?: number;
    is_locked?: boolean;
    verified_at?: string | null;
    verified_by?: number | null;
    verifier?: {
        id: number;
        name?: string;
    };
    
    // Relasi
    school?: School;
    major?: Major;
    classroom?: Classroom;
    academic_year?: AcademicYear;
    gender?: MasterOptionCode;
    religion?: MasterOption;
    status?: MasterOption;
    student_status?: MasterOption;
    citizenship?: MasterOption;
    
    // Sub Relasi Tambahan
    family?: StudentFamily;
    health?: StudentHealth;
    education_histories?: StudentEducationHistory[];
    achievements?: StudentAchievement[];
    socials?: StudentSocial[];
    violations?: StudentViolation[];
    documents?: StudentDocument[];
}