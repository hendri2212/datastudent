export type MajorStatus = 'Aktif' | 'Nonaktif';

export type Major = {
    id: number;
    code: string;
    name: string;
    studentCount: number;
    status: MajorStatus;
};

export type MajorFormData = Pick<Major, 'code' | 'name' | 'status'>;
