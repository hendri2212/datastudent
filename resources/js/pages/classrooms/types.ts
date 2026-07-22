export type LevelType = 'X' | 'XI' | 'XII' | 'XIII';

export interface Major {
    id: number;
    name: string;
    code: string;
}

export interface Classroom {
    id: number;
    name: string;
    level: LevelType;
    major: Major;
    studentCount: number;
    status: 'Aktif' | 'Nonaktif';
    maleCount: number;
    femaleCount: number;
    religion: {
        islam: number;
        kristen: number;
        katolik: number;
        hindu: number;
        buddha: number;
        khonghucu: number;
    };
}

export interface ClassroomFormData {
    major_id: number;
    name: string;
    level: LevelType;
}