export type MajorStatus = 'Aktif' | 'Nonaktif';

export type Major = {
    id: number;
    school_id?: number;
    code: string;
    name: string;
    studentCount: number;
    status: MajorStatus;
};

export type MajorFormData = {
    code: string;
    name: string;
    status: MajorStatus;
    school_id?: number;
};