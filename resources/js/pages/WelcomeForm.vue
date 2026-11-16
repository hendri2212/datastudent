<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import {
    Loader2,
    User,
    Users,
    HeartPulse,
    History,
    Share2,
    Trophy,
    Shield,
    FileText,
    Plus,
    Trash2,
    ChevronLeft,
    ChevronRight,
    Check,
    UploadCloud,
    Crop,
    Sparkles,
    Eye,
    Download,
} from 'lucide-vue-next';
import { ref, watch, computed, onMounted, onBeforeUnmount } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type {
    Student,
    School,
    Major,
    Classroom,
    AcademicYear,
    MasterOption,
    MasterOptionCode,
    EducationLevel,
    SocialPlatform,
    DocumentType,
    AchievementForm,
    EducationHistoryForm,
    StudentSocialForm,
    ViolationForm,
} from '@/pages/students/types';
import {
    store as storeStudent,
    update as updateStudent,
} from '@/routes/students';
import { destroy as destroyDocument, download as downloadDocument, preview as previewDocument } from '@/routes/students/documents';

// Import Types

const props = withDefaults(
    defineProps<{
        student?: Student | null;
        schools?: School[];
        majors?: Major[];
        classrooms?: Classroom[];
        academicYears?: AcademicYear[];
        genders?: MasterOptionCode[];
        religions?: MasterOption[];
        studentStatuses?: MasterOption[];
        bloodTypes?: MasterOption[];
        occupations?: MasterOption[];
        incomeCategories?: MasterOption[];
        citizenships?: MasterOption[];
        educationLevels?: EducationLevel[];
        socialPlatforms?: SocialPlatform[];
        relationshipTypes?: MasterOption[];
        documentTypes?: DocumentType[];
    }>(),
    {
        student: null,
    }
);

const emit = defineEmits(['close', 'saved']);

const page = usePage();
const authUser = computed(() => (page.props as any).auth?.user);
const isStudentUser = computed(() => {
    const role = authUser.value?.role;

    return role === 'student' || role === 'siswa';
});
const isStudentVerified = computed(() => Boolean(props.student?.verified_at));
const deletedDocumentIds = ref(new Set<number>());
const visibleDocuments = computed(() =>
    (props.student?.documents ?? []).filter(
        (document) => !deletedDocumentIds.value.has(document.id),
    ),
);
const isReadOnlyForStudent = computed(() => isStudentUser.value && isStudentVerified.value);

// Wizard Steps Configuration
const steps = [
    { id: 'biodata', label: 'Biodata', icon: User },
    { id: 'family', label: 'Keluarga', icon: Users },
    { id: 'health', label: 'Kesehatan', icon: HeartPulse },
    { id: 'education', label: 'Riwayat', icon: History },
    { id: 'socials', label: 'Medsos', icon: Share2 },
    { id: 'achievements', label: 'Prestasi', icon: Trophy },
    { id: 'violations', label: 'Kedisiplinan', icon: Shield },
    { id: 'documents', label: 'Dokumen', icon: FileText },
];

const currentStepIndex = ref(0);
const currentStep = computed(() => steps[currentStepIndex.value]);

const isFirstStep = computed(() => currentStepIndex.value === 0);
const isLastStep = computed(() => currentStepIndex.value === steps.length - 1);

const goToStep = (index: number) => {
    if (index >= 0 && index < steps.length) {
        currentStepIndex.value = index;
    }
};

const nextStep = () => {
    if (!isLastStep.value) {
        currentStepIndex.value++;
    }
};

const prevStep = () => {
    if (!isFirstStep.value) {
        currentStepIndex.value--;
    }
};

// Form State
const studentForm = useForm({
    school_id: null as number | null,
    major_id: null as number | null,
    classroom_id: null as number | null,
    academic_year_id: null as number | null,
    gender_id: null as number | null,
    religion_id: null as number | null,
    student_status_id: null as number | null,
    citizenship_id: null as number | null,
    nisn: '',
    nis: '',
    full_name: '',
    nickname: '',
    birth_place: '',
    birth_date: '' as string,
    phone: '',
    email: '',
    address: '',
    postal_code: '',
    document_type_id: null as number | null,

    family: {
        father_name: '',
        father_occupation_id: null as number | null,
        father_income_category_id: null as number | null,
        father_phone: '',
        mother_name: '',
        mother_occupation_id: null as number | null,
        mother_income_category_id: null as number | null,
        mother_phone: '',
        guardian_name: '',
        guardian_occupation_id: null as number | null,
        guardian_income_category_id: null as number | null,
        guardian_phone: '',
        emergency_contact_name: '',
        emergency_contact_phone: '',
        relationship_type_id: null as number | null,
        notes: '',
    },

    health: {
        blood_type_id: null as number | null,
        height: '' as string | number,
        weight: '' as string | number,
        allergies: '',
        medical_history: '',
        disabilities: '',
        medications: '',
        hospital: '',
        doctor: '',
        notes: '',
    },

    education_histories: [] as EducationHistoryForm[],
    socials: [] as StudentSocialForm[],
    achievements: [] as AchievementForm[],
    violations: [] as ViolationForm[],

    new_document_notes: '',
    new_document_file: null as File | null,
    photo_file: null as File | null,
});
const isFormProcessing = computed(() => studentForm.processing);

// Helpers
const parseNullableId = (val: unknown) =>
    val === '' || val === undefined || val === null ? null : Number(val);

const parseNullableNumber = (val: unknown): number | null => {
    if (val === '' || val === undefined || val === null) {
return null;
}

    const num = Number(val);

    return isNaN(num) ? null : num;
};

const formatNumberToString = (val: unknown): string => {
    if (val === null || val === undefined || val === '') {
return '';
}

    return String(val);
};

const formatDateForInput = (dateStr: unknown): string => {
    if (!dateStr) {
return '';
}

    return String(dateStr).split('T')[0].split(' ')[0];
};

// Photo & Cropper State
const photoPreviewUrl = ref<string | null>(null);
const photoPosition = ref({ x: 50, y: 50 });
const photoZoom = ref(100);
const photoCacheBuster = ref(Date.now());
const isPhotoCropOpen = ref(false);
const shouldShowPhotoCrop = computed(
    () => isPhotoCropOpen.value && studentForm.photo_file !== null,
);
const filePreview = ref<{ url: string; title: string; isPdf: boolean } | null>(null);
const cropDrag = ref<{
    startX: number;
    startY: number;
    x: number;
    y: number;
} | null>(null);

const resetDocumentFields = () => {
    studentForm.new_document_file = null;
    studentForm.new_document_notes = '';
    studentForm.document_type_id = null;
};

const clearPhotoPreviewUrl = () => {
    if (photoPreviewUrl.value?.startsWith('blob:')) {
        URL.revokeObjectURL(photoPreviewUrl.value);
    }

    photoPreviewUrl.value = null;
};

const resetPhotoField = () => {
    studentForm.photo_file = null;
    clearPhotoPreviewUrl();
    photoPosition.value = { x: 50, y: 50 };
    photoZoom.value = 100;
    photoCacheBuster.value = Date.now();
    isPhotoCropOpen.value = false;
};

const handleDocumentFileChange = (event: Event) => {
    studentForm.new_document_file =
        (event.target as HTMLInputElement).files?.[0] ?? null;
};

const createImage = (src: string) =>
    new Promise<HTMLImageElement>((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = src;
    });

const createCroppedPhotoFile = async (
    file: File,
    position: { x: number; y: number },
    zoom: number
): Promise<File | null> => {
    const objectUrl = URL.createObjectURL(file);

    try {
        const img = await createImage(objectUrl);
        const frameSize = 512;
        const baseScale = Math.max(frameSize / img.naturalWidth, frameSize / img.naturalHeight);
        const effectiveScale = baseScale * (zoom / 100);
        const displayedWidth = img.naturalWidth * effectiveScale;
        const displayedHeight = img.naturalHeight * effectiveScale;
        const overflowX = Math.max(displayedWidth - frameSize, 0);
        const overflowY = Math.max(displayedHeight - frameSize, 0);
        const offsetX = (overflowX * position.x) / 100;
        const offsetY = (overflowY * position.y) / 100;
        const srcX = Math.max(0, Math.min(img.naturalWidth, offsetX / effectiveScale));
        const srcY = Math.max(0, Math.min(img.naturalHeight, offsetY / effectiveScale));
        const srcSize = Math.max(1, frameSize / effectiveScale);
        const clampedSrcX = Math.round(Math.max(0, Math.min(img.naturalWidth - srcSize, srcX)));
        const clampedSrcY = Math.round(Math.max(0, Math.min(img.naturalHeight - srcSize, srcY)));

        const canvas = document.createElement('canvas');
        canvas.width = frameSize;
        canvas.height = frameSize;
        const ctx = canvas.getContext('2d');

        if (!ctx) {
return null;
}

        ctx.drawImage(
            img,
            clampedSrcX,
            clampedSrcY,
            srcSize,
            srcSize,
            0,
            0,
            frameSize,
            frameSize
        );

        return await new Promise((resolve) => {
            canvas.toBlob(
                (blob) => {
                    if (!blob) {
                        resolve(null);

                        return;
                    }

                    const croppedFile = new File([blob], file.name, {
                        type: file.type || 'image/png',
                    });
                    resolve(croppedFile);
                },
                file.type || 'image/png',
                0.92
            );
        });
    } finally {
        URL.revokeObjectURL(objectUrl);
    }
};

const prepareCroppedPhoto = async (): Promise<void> => {
    if (!studentForm.photo_file) {
return;
}

    const cropped = await createCroppedPhotoFile(
        studentForm.photo_file,
        photoPosition.value,
        photoZoom.value
    );

    if (cropped) {
        studentForm.photo_file = cropped;
        clearPhotoPreviewUrl();
        photoPreviewUrl.value = URL.createObjectURL(cropped);
    }
};

const handlePhotoFileChange = async (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    studentForm.photo_file = file;
    photoPosition.value = { x: 50, y: 50 };
    clearPhotoPreviewUrl();

    if (file) {
        photoPreviewUrl.value = URL.createObjectURL(file);
    }
};

const handleCropPointerDown = (event: PointerEvent) => {
    cropDrag.value = {
        startX: event.clientX,
        startY: event.clientY,
        x: photoPosition.value.x,
        y: photoPosition.value.y,
    };
    (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
};

const handleCropPointerMove = (event: PointerEvent) => {
    if (!cropDrag.value) {
return;
}

    const target = event.currentTarget as HTMLElement;
    const rect = target.getBoundingClientRect();
    const dx = event.clientX - cropDrag.value.startX;
    const dy = event.clientY - cropDrag.value.startY;
    const deltaX = (dx / rect.width) * 100;
    const deltaY = (dy / rect.height) * 100;
    photoPosition.value.x = Math.min(100, Math.max(0, cropDrag.value.x - deltaX));
    photoPosition.value.y = Math.min(100, Math.max(0, cropDrag.value.y - deltaY));
};

const handleCropPointerEnd = () => {
    cropDrag.value = null;
};

const studentPhotoSrc = computed(() => {
    if (photoPreviewUrl.value) {
return photoPreviewUrl.value;
}

    if (!props.student?.photo_url) {
return '';
}

    return `${props.student.photo_url}?t=${photoCacheBuster.value}`;
});

const documentPreviewUrl = (documentId: number) =>
    props.student
        ? previewDocument.url({
              student: props.student.id,
              document: documentId,
          })
        : '#';

const documentDownloadUrl = (documentId: number) =>
    props.student
        ? downloadDocument.url({ student: props.student.id, document: documentId })
        : '#';

const storedFileName = (path: string) => path.split(/[\\/]/).pop() || path;

const openFilePreview = (url: string, title: string, mimeType = '') => {
    filePreview.value = {
        url,
        title,
        isPdf: mimeType.toLowerCase().includes('pdf') || /\.pdf(?:$|\?)/i.test(url),
    };
};

const openUploadPreview = (file: File, title: string) => {
    openFilePreview(URL.createObjectURL(file), title, file.type);
};

const closeFilePreview = () => {
    if (filePreview.value?.url.startsWith('blob:')) {
        URL.revokeObjectURL(filePreview.value.url);
    }

    filePreview.value = null;
};

const handleFilePreviewEscape = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && filePreview.value) {
        event.preventDefault();
        event.stopPropagation();
        closeFilePreview();
    }
};

onMounted(() => window.addEventListener('keydown', handleFilePreviewEscape, true));
onBeforeUnmount(() => window.removeEventListener('keydown', handleFilePreviewEscape, true));

const isDocumentTypeExists = computed(() => {
    if (!studentForm.document_type_id || !props.student?.documents) {
return false;
}

    return props.student.documents.some(
        (doc) =>
            parseNullableId(doc.document_type_id) ===
            parseNullableId(studentForm.document_type_id)
    );
});

const filteredMajors = computed(
    () =>
        props.majors?.filter(
            (major) => !studentForm.school_id || major.school_id === studentForm.school_id
        ) ?? []
);
const filteredClassrooms = computed(
    () =>
        props.classrooms?.filter(
            (classroom) =>
                !studentForm.major_id || classroom.major_id === studentForm.major_id
        ) ?? []
);

const handleSchoolChange = () => {
    if (!filteredMajors.value.some((major) => major.id === studentForm.major_id)) {
        studentForm.major_id = null;
        studentForm.classroom_id = null;
    }
};

const handleMajorChange = () => {
    if (
        !filteredClassrooms.value.some(
            (classroom) => classroom.id === studentForm.classroom_id
        )
    ) {
        studentForm.classroom_id = null;
    }
};

// Watcher Sync
watch(
    () => props.student?.id,
    () => {
        deletedDocumentIds.value = new Set();
        currentStepIndex.value = 0;
    },
    { immediate: true },
);

watch(
    () => props.student,
    (newStudent) => {
        if (newStudent) {
            photoCacheBuster.value = Date.now();
            studentForm.reset();
            studentForm.clearErrors();
            studentForm.school_id = parseNullableId(newStudent.school_id);
            studentForm.major_id = parseNullableId(newStudent.major_id);
            studentForm.classroom_id = parseNullableId(newStudent.classroom_id);
            studentForm.academic_year_id = parseNullableId(newStudent.academic_year_id);
            studentForm.gender_id = parseNullableId(newStudent.gender_id);
            studentForm.religion_id = parseNullableId(newStudent.religion_id);
            studentForm.student_status_id = parseNullableId(newStudent.student_status_id);
            studentForm.citizenship_id = parseNullableId(newStudent.citizenship_id);

            studentForm.nisn = newStudent.nisn || '';
            studentForm.nis = newStudent.nis || '';
            studentForm.full_name = newStudent.full_name || '';
            studentForm.nickname = newStudent.nickname || '';
            studentForm.birth_place = newStudent.birth_place || '';
            studentForm.birth_date = formatDateForInput(newStudent.birth_date);
            studentForm.phone = newStudent.phone || '';
            studentForm.email = newStudent.email || '';
            studentForm.address = newStudent.address || '';
            studentForm.postal_code = newStudent.postal_code || '';

            resetDocumentFields();
            resetPhotoField();

            if (newStudent.family) {
                studentForm.family.father_name = newStudent.family.father_name || '';
                studentForm.family.father_occupation_id = parseNullableId(newStudent.family.father_occupation_id);
                studentForm.family.father_income_category_id = parseNullableId(newStudent.family.father_income_category_id);
                studentForm.family.father_phone = newStudent.family.father_phone || '';
                studentForm.family.mother_name = newStudent.family.mother_name || '';
                studentForm.family.mother_occupation_id = parseNullableId(newStudent.family.mother_occupation_id);
                studentForm.family.mother_income_category_id = parseNullableId(newStudent.family.mother_income_category_id);
                studentForm.family.mother_phone = newStudent.family.mother_phone || '';
                studentForm.family.guardian_name = newStudent.family.guardian_name || '';
                studentForm.family.guardian_occupation_id = parseNullableId(newStudent.family.guardian_occupation_id);
                studentForm.family.guardian_income_category_id = parseNullableId(newStudent.family.guardian_income_category_id);
                studentForm.family.guardian_phone = newStudent.family.guardian_phone || '';
                studentForm.family.emergency_contact_name = newStudent.family.emergency_contact_name || '';
                studentForm.family.emergency_contact_phone = newStudent.family.emergency_contact_phone || '';
                studentForm.family.relationship_type_id = parseNullableId(newStudent.family.relationship_type_id);
                studentForm.family.notes = newStudent.family.notes || '';
            }

            if (newStudent.health) {
                studentForm.health.blood_type_id = parseNullableId(newStudent.health.blood_type_id);
                studentForm.health.height = formatNumberToString(newStudent.health.height);
                studentForm.health.weight = formatNumberToString(newStudent.health.weight);
                studentForm.health.allergies = newStudent.health.allergies || '';
                studentForm.health.medical_history = newStudent.health.medical_history || '';
                studentForm.health.disabilities = newStudent.health.disabilities || '';
                studentForm.health.medications = newStudent.health.medications || '';
                studentForm.health.hospital = newStudent.health.hospital || '';
                studentForm.health.doctor = newStudent.health.doctor || '';
                studentForm.health.notes = newStudent.health.notes || '';
            }

            studentForm.education_histories = newStudent.education_histories
                ? newStudent.education_histories.map((edu) => ({
                      ...edu,
                      education_level_id: parseNullableId(edu.education_level_id),
                      entry_year: formatNumberToString(edu.entry_year),
                      graduation_year: formatNumberToString(edu.graduation_year),
                      final_score: formatNumberToString(edu.final_score),
                      is_graduated: edu.is_graduated ?? true,
                  }))
                : [];

            studentForm.socials = newStudent.socials
                ? newStudent.socials.map((s) => ({
                      ...s,
                      social_platform_id: parseNullableId(s.social_platform_id),
                  }))
                : [];

            studentForm.achievements = newStudent.achievements
                ? newStudent.achievements.map((ach) => ({
                      ...ach,
                      rank: formatNumberToString(ach.rank),
                      achievement_date: formatDateForInput(ach.achievement_date),
                      certificate: ach.certificate || null,
                  }))
                : [];

            studentForm.violations = newStudent.violations
                ? newStudent.violations.map((vio) => ({
                      ...vio,
                      point: formatNumberToString(vio.point),
                      violation_date: formatDateForInput(vio.violation_date),
                  }))
                : [];
        } else {
            studentForm.reset();
            resetDocumentFields();
        }
    },
    { immediate: true }
);

// Dynamic Handlers
const addEducation = () => {
    studentForm.education_histories.push({
        education_level_id: null,
        school_name: '',
        npsn: '',
        address: '',
        entry_year: '',
        graduation_year: '',
        final_score: '',
        is_graduated: true,
        notes: '',
    });
};
const removeEducation = (index: number) => studentForm.education_histories.splice(index, 1);
const deleteEducationCertificate = (index: number) => {
    const history = studentForm.education_histories[index];

    if (!history?.id || typeof history.certificate !== 'string' || !confirm('Yakin ingin menghapus sertifikat ini?')) {
return;
}

    router.delete(`/student-education-histories/${history.id}/certificate`, {
        preserveScroll: true,
        onSuccess: () => {
            const current = studentForm.education_histories.find((item) => item.id === history.id);

            if (current) {
current.certificate = null;
}
        },
    });
};
const handleEducationCertificateChange = (event: Event, index: number) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    if (studentForm.education_histories[index]) {
studentForm.education_histories[index].certificate = file;
}
};
const isEducationCertificateFile = (value: unknown): value is File => value instanceof File;
const previewEducationCertificate = (value: unknown, historyId?: number) => {
    if (isEducationCertificateFile(value)) {
        openUploadPreview(value, 'Ijazah / dokumen pendidikan');
    } else if (typeof value === 'string' && historyId) {
        openFilePreview(`/student-education-histories/${historyId}/certificate`, 'Ijazah / dokumen pendidikan', value.toLowerCase().endsWith('.pdf') ? 'application/pdf' : 'image/*');
    }
};

const addSocial = () => {
    studentForm.socials.push({ social_platform_id: null, username: '', url: '' });
};
const removeSocial = (index: number) => studentForm.socials.splice(index, 1);

const addAchievement = () => {
    studentForm.achievements.push({
        title: '',
        organizer: '',
        level: '',
        category: '',
        rank: '',
        achievement_date: '',
        description: '',
        certificate: null,
    });
};
const removeAchievement = (index: number) => studentForm.achievements.splice(index, 1);
const deleteAchievementCertificate = (index: number) => {
    const achievement = studentForm.achievements[index];

    if (!achievement?.id || typeof achievement.certificate !== 'string' || !confirm('Yakin ingin menghapus sertifikat ini?')) {
return;
}

    router.delete(`/student-achievements/${achievement.id}/certificate`, {
        preserveScroll: true,
        onSuccess: () => {
            const current = studentForm.achievements.find((item) => item.id === achievement.id);

            if (current) {
current.certificate = null;
}
        },
    });
};

const handleCertificateFileChange = (event: Event, index: number) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    if (studentForm.achievements[index]) {
        studentForm.achievements[index].certificate = file;
    }
};

const isCertificateFile = (value: unknown): value is File => value instanceof File;

const previewCertificate = (value: unknown) => {
    if (isCertificateFile(value)) {
        openUploadPreview(value, 'Sertifikat prestasi');
    }
};

const normalizeScoreInput = (edu: EducationHistoryForm) => {
    if (!edu.final_score) {
        edu.final_score = '';

        return;
    }

    const numericOnly = String(edu.final_score).replace(/[^0-9]/g, '');

    if (!numericOnly) {
        edu.final_score = '';

        return;
    }

    if (numericOnly.length <= 2) {
        edu.final_score = Number(numericOnly).toString();

        return;
    }

    const integerPart = numericOnly.slice(0, numericOnly.length - 2);
    const decimalPart = numericOnly.slice(-2);
    edu.final_score = Number(`${integerPart}.${decimalPart}`).toFixed(2);
};

const addViolation = () => {
    studentForm.violations.push({ title: '', point: '', violation_date: '', description: '' });
};
const removeViolation = (index: number) => studentForm.violations.splice(index, 1);

const deleteDocument = (documentId: number) => {
    if (!props.student || !confirm('Apakah Anda yakin ingin menghapus dokumen ini?')) {
return;
}

    router.delete(
        destroyDocument.url({
            student: props.student.id,
            document: documentId,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                deletedDocumentIds.value = new Set([
                    ...deletedDocumentIds.value,
                    documentId,
                ]);
            },
        }
    );
};

const handleCancel = () => {
    if (studentForm.processing) {
return;
}

    studentForm.clearErrors();
    currentStepIndex.value = 0;
    resetDocumentFields();
    resetPhotoField();
    emit('close');
};

const handleSubmit = async () => {
    if (isReadOnlyForStudent.value) {
        return;
    }

    const requiredAcademicIds = {
        school_id: parseNullableId(studentForm.school_id),
        major_id: parseNullableId(studentForm.major_id),
        classroom_id: parseNullableId(studentForm.classroom_id),
        academic_year_id: parseNullableId(studentForm.academic_year_id),
        student_status_id: parseNullableId(studentForm.student_status_id),
    };

    const missingRequired = Object.entries(requiredAcademicIds).find(
        ([, value]) => value === null,
    );

    if (missingRequired) {
        const [missingField] = missingRequired;
        alert(
            `Field ${missingField.replace(/_/g, ' ')} wajib dipilih pada langkah Biodata sebelum menyimpan.`,
        );
        goToStep(steps.findIndex((s) => s.id === 'biodata'));

        return;
    }

    if (isDocumentTypeExists.value && studentForm.new_document_file) {
        if (!confirm('Jenis dokumen ini sudah ada. Lanjutkan untuk mengganti dokumen lama?')) {
            currentStepIndex.value = steps.findIndex((s) => s.id === 'documents');

            return;
        }
    }

    studentForm.transform((data) => ({
        ...data,
        school_id: parseNullableId(data.school_id),
        major_id: parseNullableId(data.major_id),
        classroom_id: parseNullableId(data.classroom_id),
        academic_year_id: parseNullableId(data.academic_year_id),
        gender_id: parseNullableId(data.gender_id),
        religion_id: parseNullableId(data.religion_id),
        student_status_id: parseNullableId(data.student_status_id),
        citizenship_id: parseNullableId(data.citizenship_id),
        document_type_id: parseNullableId(data.document_type_id),
        birth_date: data.birth_date || null,

        family: {
            ...data.family,
            father_occupation_id: parseNullableId(data.family.father_occupation_id),
            father_income_category_id: parseNullableId(data.family.father_income_category_id),
            mother_occupation_id: parseNullableId(data.family.mother_occupation_id),
            mother_income_category_id: parseNullableId(data.family.mother_income_category_id),
            guardian_occupation_id: parseNullableId(data.family.guardian_occupation_id),
            guardian_income_category_id: parseNullableId(data.family.guardian_income_category_id),
            relationship_type_id: parseNullableId(data.family.relationship_type_id),
        },

        health: {
            ...data.health,
            blood_type_id: parseNullableId(data.health.blood_type_id),
            height: parseNullableNumber(data.health.height),
            weight: parseNullableNumber(data.health.weight),
        },

        education_histories: data.education_histories.map((edu) => ({
            ...edu,
            education_level_id: parseNullableId(edu.education_level_id),
            entry_year: parseNullableNumber(edu.entry_year),
            graduation_year: parseNullableNumber(edu.graduation_year),
            final_score: edu.final_score !== '' ? edu.final_score : null,
            certificate: edu.certificate instanceof File ? edu.certificate : null,
        })),

        socials: data.socials.map((soc) => ({
            ...soc,
            social_platform_id: parseNullableId(soc.social_platform_id),
        })),

        achievements: data.achievements.map((ach) => ({
            ...ach,
            rank: parseNullableNumber(ach.rank),
            achievement_date: ach.achievement_date || null,
            certificate: ach.certificate, 
        })),

        violations: data.violations.map((vio) => ({
            ...vio,
            point: parseNullableNumber(vio.point),
            violation_date: vio.violation_date || null,
        })),
    }));

    if (studentForm.photo_file) {
        await prepareCroppedPhoto();
    }

    const onSuccessHandler = () => {
        resetDocumentFields();
        resetPhotoField();
        emit('saved');
    };

    const handleFormErrors = (errors: Record<string, string>) => {
        console.error('Form errors:', errors);
        const errorKeys = Object.keys(errors);

        if (errorKeys.length > 0) {
            const firstError = errorKeys[0];

            if (firstError.startsWith('family.')) {
                goToStep(steps.findIndex((s) => s.id === 'family'));
            } else if (firstError.startsWith('health.')) {
                goToStep(steps.findIndex((s) => s.id === 'health'));
            } else if (firstError.startsWith('education_histories.')) {
                goToStep(steps.findIndex((s) => s.id === 'education'));
            } else if (firstError.startsWith('socials.')) {
                goToStep(steps.findIndex((s) => s.id === 'socials'));
            } else if (firstError.startsWith('achievements.')) {
                goToStep(steps.findIndex((s) => s.id === 'achievements'));
            } else if (firstError.startsWith('violations.')) {
                goToStep(steps.findIndex((s) => s.id === 'violations'));
            } else if (firstError.includes('document')) {
                goToStep(steps.findIndex((s) => s.id === 'documents'));
            } else {
                goToStep(steps.findIndex((s) => s.id === 'biodata'));
            }

            const errorMessages = Object.values(errors).flat().join('\n');
            alert('Gagal menyimpan data:\n' + errorMessages);
        }
    };

    if (props.student && props.student.id) {
        studentForm.post(updateStudent.url(props.student.id), {
            onSuccess: onSuccessHandler,
            onError: handleFormErrors,
        });
    } else {
        studentForm.post(storeStudent.url(), {
            onSuccess: onSuccessHandler,
            onError: handleFormErrors,
        });
    }
};

const inputClass =
    'h-10 rounded-lg border-neutral-200 bg-neutral-50/50 text-xs focus:bg-white focus:border-neutral-900 focus:ring-0 dark:border-neutral-800 dark:bg-neutral-900/50 dark:focus:border-white dark:focus:bg-black transition-all';
const selectClass =
    'h-10 w-full rounded-lg border border-neutral-200 bg-neutral-50/50 px-3 text-xs focus:bg-white focus:border-neutral-900 focus:ring-0 dark:border-neutral-800 dark:bg-neutral-900/50 dark:focus:border-white dark:focus:bg-black transition-all outline-none';
const labelClass = 'text-[11px] font-semibold text-neutral-600 dark:text-neutral-400 uppercase tracking-wider mb-1 block';
</script>

    <template>
    <div class="h-full min-h-0 bg-neutral-50/60 text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100 font-sans flex flex-col antialiased">
        <header class="shrink-0 border-b border-neutral-200/80 bg-white/80 backdrop-blur-md dark:border-neutral-800/80 dark:bg-neutral-950/80">
            <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-neutral-900 text-white dark:bg-white dark:text-black">
                        <Sparkles class="h-4 w-4" />
                    </div>
                    <div>
                        <h1 class="text-base font-bold tracking-tight">
                            {{ props.student ? 'Profil & Data Siswa' : 'Pendaftaran Siswa Baru' }}
                        </h1>
                        <p class="text-[11px] text-neutral-500 dark:text-neutral-400">
                            {{ props.student ? 'Periksa dan lengkapi data profil siswa Anda.' : 'Lengkapi seluruh data sesuai dokumen resmi.' }}
                        </p>
                    </div>
                </div>
                <div class="hidden sm:block text-right">
                    <span class="text-xs font-semibold text-neutral-400">Step {{ currentStepIndex + 1 }} of {{ steps.length }}</span>
                    <p class="text-xs font-bold text-neutral-900 dark:text-white">{{ currentStep.label }}</p>
                </div>
            </div>
        </header>

        <main class="mx-auto flex min-h-0 w-full max-w-5xl flex-1 flex-col gap-4 overflow-hidden px-4 py-4 sm:px-6">
            <div class="shrink-0 rounded-2xl border border-neutral-200/80 bg-white p-2 shadow-sm dark:border-neutral-800/80 dark:bg-neutral-900">
                <div class="flex items-center justify-between overflow-x-auto gap-1 no-scrollbar p-1">
                    <button
                        v-for="(step, idx) in steps"
                        :key="step.id"
                        type="button"
                        @click="goToStep(idx)"
                        class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-medium transition-all shrink-0"
                        :class="[
                            idx === currentStepIndex
                                ? 'bg-neutral-900 text-white shadow-sm dark:bg-white dark:text-black font-semibold'
                                : idx < currentStepIndex
                                ? 'text-neutral-700 hover:bg-neutral-100 dark:text-neutral-300 dark:hover:bg-neutral-800'
                                : 'text-neutral-400 hover:bg-neutral-50 dark:text-neutral-600 dark:hover:bg-neutral-800/50'
                        ]"
                    >
                        <component
                            :is="idx < currentStepIndex ? Check : step.icon"
                            class="h-3.5 w-3.5"
                            :class="idx === currentStepIndex ? 'text-white dark:text-black' : ''"
                        />
                        <span>{{ step.label }}</span>
                    </button>
                </div>
                <div class="mt-2 h-1 w-full rounded-full bg-neutral-100 dark:bg-neutral-800 overflow-hidden">
                    <div
                        class="h-full bg-neutral-900 dark:bg-white transition-all duration-300"
                        :style="{ width: `${((currentStepIndex + 1) / steps.length) * 100}%` }"
                    ></div>
                </div>
            </div>

            <form @submit.prevent="handleSubmit" class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-neutral-200/80 bg-white shadow-sm dark:border-neutral-800/80 dark:bg-neutral-900">
                <div class="min-h-0 flex-1 overflow-y-auto p-6 sm:p-8">
                    
                    <div v-if="isReadOnlyForStudent" class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200">
                        <div class="flex items-center gap-2 font-bold">
                            <Shield class="h-4 w-4 text-amber-600" />
                            <span>Data Siswa Telah Terverifikasi</span>
                        </div>
                        <p class="mt-1 text-neutral-600 dark:text-neutral-400">
                            Data siswa ini sudah diverifikasi oleh pihak sekolah/admin sehingga tidak dapat diubah secara mandiri oleh akun siswa. Silakan hubungi admin sekolah jika ada pembaruan data resmi.
                        </p>
                    </div>

                    <div v-show="currentStep.id === 'biodata'" class="space-y-6">
                        <div class="border-b border-neutral-100 dark:border-neutral-800 pb-4">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-white flex items-center gap-2">
                                <User class="h-4 w-4 text-neutral-500" /> Informasi Pribadi
                            </h2>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center gap-6 rounded-xl border border-neutral-100 bg-neutral-50/50 p-4 dark:border-neutral-800 dark:bg-neutral-950/40">
                            <div class="relative h-24 w-24 shrink-0 overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                                <img v-if="studentPhotoSrc" :src="studentPhotoSrc" class="h-full w-full object-cover" />
                                <div v-else class="flex h-full w-full flex-col items-center justify-center text-neutral-400">
                                    <User class="h-8 w-8 stroke-[1.5]" />
                                </div>
                            </div>
                            <div class="flex-1 space-y-2 text-center sm:text-left">
                                <h3 class="text-xs font-bold uppercase text-neutral-700 dark:text-neutral-300">Foto Pas Siswa</h3>
                                <p class="text-[11px] text-neutral-500">Format PNG/JPG, Maksimal 2MB. Disarankan rasio 3:4 atau 1:1.</p>
                                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 pt-1">
                                    <label class="cursor-pointer inline-flex items-center gap-1.5 rounded-lg border border-neutral-200 bg-white px-3 py-1.5 text-xs font-medium text-neutral-700 shadow-sm hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-200">
                                        <UploadCloud class="h-3.5 w-3.5" />
                                        <span>Pilih Foto</span>
                                        <input type="file" accept="image/*" class="hidden" @change="handlePhotoFileChange" />
                                    </label>
                                    <Button v-if="studentForm.photo_file" type="button" variant="outline" size="sm" class="h-8 text-xs rounded-lg" @click="isPhotoCropOpen = true">
                                        <Crop class="mr-1 h-3.5 w-3.5" /> Atur Posisi
                                    </Button>
                                    <Button v-if="studentPhotoSrc" type="button" variant="outline" size="sm" class="h-8 text-xs rounded-lg" @click="openFilePreview(studentPhotoSrc, 'Foto siswa', 'image/*')">
                                        <Eye class="mr-1 h-3.5 w-3.5" /> Lihat Foto
                                    </Button>
                                    <Button v-if="studentForm.photo_file" type="button" variant="ghost" size="sm" class="h-8 text-xs text-red-500 hover:bg-red-50 hover:text-red-600 rounded-lg" @click="resetPhotoField">
                                        Reset
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label :class="labelClass">Nama Lengkap <span class="text-red-500">*</span></label>
                                <Input v-model="studentForm.full_name" placeholder="Nama Lengkap Siswa" :class="inputClass" required />
                                <span v-if="studentForm.full_name" class="text-[11px] text-red-500 mt-1 block">{{ studentForm.errors.full_name }}</span>
                            </div>
                            <div>
                                <label :class="labelClass">Nama Panggilan</label>
                                <Input v-model="studentForm.nickname" placeholder="Nama Panggilan" :class="inputClass" />
                            </div>
                            <div>
                                <label :class="labelClass">NISN <span class="text-red-500">*</span></label>
                                <Input type="text" inputmode="numeric" maxlength="10" v-model="studentForm.nisn" placeholder="00xxxxxxxx" :class="inputClass" required />
                                <span v-if="studentForm.nisn" class="text-[11px] text-red-500 mt-1 block">{{ studentForm.errors.nisn }}</span>
                            </div>
                            <div>
                                <label :class="labelClass">NIS</label>
                                <Input type="text" inputmode="numeric" maxlength="5" v-model="studentForm.nis" placeholder="Nomor Induk Sekolah" :class="inputClass" />
                            </div>
                            <div>
                                <label :class="labelClass">Jenis Kelamin <span class="text-red-500">*</span></label>
                                <select v-model="studentForm.gender_id" :class="selectClass" required>
                                    <option :value="null">Pilih Jenis Kelamin</option>
                                    <option v-for="g in props.genders" :key="g.id" :value="g.id">{{ g.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label :class="labelClass">Kewarganegaraan</label>
                                <select v-model="studentForm.citizenship_id" :class="selectClass">
                                    <option :value="null">Pilih Kewarganegaraan</option>
                                    <option v-for="c in props.citizenships" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label :class="labelClass">Agama</label>
                                <select v-model="studentForm.religion_id" :class="selectClass">
                                    <option :value="null">Pilih Agama</option>
                                    <option v-for="r in props.religions" :key="r.id" :value="r.id">{{ r.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label :class="labelClass">Tempat Lahir</label>
                                <Input v-model="studentForm.birth_place" placeholder="Kota Lahir" :class="inputClass" />
                            </div>
                            <div>
                                <label :class="labelClass">Tanggal Lahir</label>
                                <Input type="date" v-model="studentForm.birth_date" :class="inputClass" />
                            </div>
                            <div>
                                <label :class="labelClass">Sekolah <span class="text-red-500">*</span></label>
                                <select v-model="studentForm.school_id" :class="selectClass" required @change="handleSchoolChange">
                                    <option :value="null">Pilih Sekolah</option>
                                    <option v-for="s in props.schools" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label :class="labelClass">Jurusan</label>
                                <select v-model="studentForm.major_id" :class="selectClass" @change="handleMajorChange">
                                    <option :value="null">Pilih Jurusan</option>
                                    <option v-for="m in filteredMajors" :key="m.id" :value="m.id">{{ m.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label :class="labelClass">Kelas</label>
                                <select v-model="studentForm.classroom_id" :class="selectClass">
                                    <option :value="null">Pilih Kelas</option>
                                    <option v-for="c in filteredClassrooms" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label :class="labelClass">Tahun Ajaran <span class="text-red-500">*</span></label>
                                <select v-model="studentForm.academic_year_id" :class="selectClass" required>
                                    <option :value="null">Pilih Tahun Ajaran</option>
                                    <option v-for="ay in props.academicYears" :key="ay.id" :value="ay.id">{{ ay.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label :class="labelClass">Status Siswa</label>
                                <select v-model="studentForm.student_status_id" :class="selectClass">
                                    <option :value="null">Pilih Status</option>
                                    <option v-for="st in props.studentStatuses" :key="st.id" :value="st.id">{{ st.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label :class="labelClass">No. WhatsApp / Telepon</label>
                                <Input type="text" inputmode="tel" v-model="studentForm.phone" placeholder="08xxxxxxxxxx" :class="inputClass" />
                            </div>
                            <div>
                                <label :class="labelClass">Email</label>
                                <Input type="email" v-model="studentForm.email" placeholder="siswa@sekolah.sch.id" :class="inputClass" />
                            </div>
                            <div class="sm:col-span-2">
                                <label :class="labelClass">Alamat Lengkap</label>
                                <Input v-model="studentForm.address" placeholder="Jl. Raya No. 1..." :class="inputClass" />
                            </div>
                            <div>
                                <label :class="labelClass">Kode Pos</label>
                                <Input type="text" inputmode="numeric" v-model="studentForm.postal_code" placeholder="xxxxx" :class="inputClass" />
                            </div>
                        </div>

                    </div>

                    <div v-show="currentStep.id === 'family'" class="space-y-6">
                        <div class="border-b border-neutral-100 dark:border-neutral-800 pb-4">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-white flex items-center gap-2">
                                <Users class="h-4 w-4 text-neutral-500" /> Data Orang Tua & Wali
                            </h2>
                        </div>

                        <div class="rounded-xl border border-neutral-200/80 p-4 dark:border-neutral-800 space-y-3">
                            <h3 class="text-xs font-bold uppercase text-neutral-700 dark:text-neutral-300">Data Ayah Kandung</h3>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div>
                                    <label :class="labelClass">Nama Ayah<span class="text-red-500">*</span></label>
                                    <Input v-model="studentForm.family.father_name" placeholder="Nama Ayah" :class="inputClass" required />
                                </div>
                                <div>
                                    <label :class="labelClass">Pekerjaan</label>
                                    <select v-model="studentForm.family.father_occupation_id" :class="selectClass">
                                        <option :value="null">Pilih Pekerjaan</option>
                                        <option v-for="occ in props.occupations" :key="occ.id" :value="occ.id">{{ occ.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label :class="labelClass">Penghasilan</label>
                                    <select v-model="studentForm.family.father_income_category_id" :class="selectClass">
                                        <option :value="null">Pilih Penghasilan</option>
                                        <option v-for="inc in props.incomeCategories" :key="inc.id" :value="inc.id">{{ inc.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label :class="labelClass">No HP Ayah</label>
                                    <Input type="text" inputmode="tel" v-model="studentForm.family.father_phone" placeholder="08xxxxxxxxxx" :class="inputClass" />
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-neutral-200/80 p-4 dark:border-neutral-800 space-y-3">
                            <h3 class="text-xs font-bold uppercase text-neutral-700 dark:text-neutral-300">Data Ibu Kandung</h3>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div>
                                    <label :class="labelClass">Nama Ibu<span class="text-red-500">*</span></label>
                                    <Input v-model="studentForm.family.mother_name" placeholder="Nama Ibu" :class="inputClass" required />
                                </div>
                                <div>
                                    <label :class="labelClass">Pekerjaan</label>
                                    <select v-model="studentForm.family.mother_occupation_id" :class="selectClass">
                                        <option :value="null">Pilih Pekerjaan</option>
                                        <option v-for="occ in props.occupations" :key="occ.id" :value="occ.id">{{ occ.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label :class="labelClass">Penghasilan</label>
                                    <select v-model="studentForm.family.mother_income_category_id" :class="selectClass">
                                        <option :value="null">Pilih Penghasilan</option>
                                        <option v-for="inc in props.incomeCategories" :key="inc.id" :value="inc.id">{{ inc.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label :class="labelClass">No HP Ibu</label>
                                    <Input type="text" inputmode="tel" v-model="studentForm.family.mother_phone" placeholder="08xxxxxxxxxx" :class="inputClass" />
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-neutral-200/80 p-4 dark:border-neutral-800 space-y-3">
                            <h3 class="text-xs font-bold uppercase text-neutral-700 dark:text-neutral-300">Data Wali (Opsional)</h3>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div>
                                    <label :class="labelClass">Nama Wali</label>
                                    <Input v-model="studentForm.family.guardian_name" placeholder="Nama Wali" :class="inputClass" />
                                </div>
                                <div>
                                    <label :class="labelClass">Pekerjaan</label>
                                    <select v-model="studentForm.family.guardian_occupation_id" :class="selectClass">
                                        <option :value="null">Pilih Pekerjaan</option>
                                        <option v-for="occ in props.occupations" :key="occ.id" :value="occ.id">{{ occ.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label :class="labelClass">Penghasilan</label>
                                    <select v-model="studentForm.family.guardian_income_category_id" :class="selectClass">
                                        <option :value="null">Pilih Penghasilan</option>
                                        <option v-for="inc in props.incomeCategories" :key="inc.id" :value="inc.id">{{ inc.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label :class="labelClass">No HP Wali</label>
                                    <Input type="text" inputmode="tel" v-model="studentForm.family.guardian_phone" placeholder="08xxxxxxxxxx" :class="inputClass" />
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-neutral-200/80 p-4 dark:border-neutral-800 space-y-3">
                            <h3 class="text-xs font-bold uppercase text-neutral-700 dark:text-neutral-300">Kontak Darurat</h3>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <div>
                                    <label :class="labelClass">Nama Kontak Darurat<span class="text-red-500">*</span></label>
                                    <Input v-model="studentForm.family.emergency_contact_name" placeholder="Nama Penanggung Jawab" :class="inputClass" required />
                                </div>
                                <div>
                                    <label :class="labelClass">No HP Darurat</label>
                                    <Input type="text" inputmode="tel" v-model="studentForm.family.emergency_contact_phone" placeholder="08xxxxxxxxxx" :class="inputClass" />
                                </div>
                                <div>
                                    <label :class="labelClass">Hubungan</label>
                                    <select v-model="studentForm.family.relationship_type_id" :class="selectClass">
                                        <option :value="null">Pilih Hubungan</option>
                                        <option v-for="rel in props.relationshipTypes" :key="rel.id" :value="rel.id">{{ rel.name }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="family-notes" :class="labelClass">Catatan Keluarga</label>
                            <p class="mb-2 text-xs text-neutral-500 dark:text-neutral-400">
                                Tambahkan informasi keluarga yang perlu diketahui sekolah.
                            </p>
                            <textarea
                                id="family-notes"
                                v-model="studentForm.family.notes"
                                rows="4"
                                maxlength="2000"
                                placeholder="Contoh: kondisi khusus, kebutuhan pendampingan, atau informasi penting lainnya…"
                                class="w-full resize-y rounded-xl border border-neutral-200 bg-white px-4 py-3 text-sm leading-relaxed text-neutral-900 shadow-sm outline-none transition placeholder:text-neutral-400 focus:border-neutral-400 focus:ring-4 focus:ring-neutral-100 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-100 dark:placeholder:text-neutral-500 dark:focus:border-neutral-600 dark:focus:ring-neutral-800/60"
                            />
                            <div class="mt-1 flex justify-end text-[11px] text-neutral-400">
                                {{ studentForm.family.notes.length }}/2000
                            </div>
                        </div>
                    </div>

                    <div v-show="currentStep.id === 'health'" class="space-y-6">
                        <div class="border-b border-neutral-100 dark:border-neutral-800 pb-4">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-white flex items-center gap-2">
                                <HeartPulse class="h-4 w-4 text-neutral-500" /> Catatan Kesehatan
                            </h2>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label :class="labelClass">Golongan Darah</label>
                                <select v-model="studentForm.health.blood_type_id" :class="selectClass">
                                    <option :value="null">Pilih Golongan Darah</option>
                                    <option v-for="bt in props.bloodTypes" :key="bt.id" :value="bt.id">{{ bt.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label :class="labelClass">Tinggi Badan (cm)</label>
                                <Input type="text" inputmode="numeric" v-model="studentForm.health.height" placeholder="170" :class="inputClass" />
                            </div>
                            <div>
                                <label :class="labelClass">Berat Badan (kg)</label>
                                <Input type="text" inputmode="numeric" v-model="studentForm.health.weight" placeholder="60" :class="inputClass" />
                            </div>
                            <div class="sm:col-span-3">
                                <label :class="labelClass">Alergi Makanan / Obat</label>
                                <Input v-model="studentForm.health.allergies" placeholder="Catatan alergi jika ada" :class="inputClass" />
                            </div>
                            <div class="sm:col-span-3">
                                <label :class="labelClass">Riwayat Penyakit Khusus / Disabilitas</label>
                                <Input v-model="studentForm.health.medical_history" placeholder="Riwayat kesehatan yang perlu diperhatikan" :class="inputClass" />
                            </div>
                            <div class="sm:col-span-1">
                                <label :class="labelClass">Dokter Pribadi</label>
                                <Input v-model="studentForm.health.doctor" placeholder="DR. Contoh " :class="inputClass" />
                            </div>
                            <div class="sm:col-span-1">
                                <label :class="labelClass">Rumah Sakit</label>
                                <Input v-model="studentForm.health.hospital" placeholder="Rumah Sakit Contoh" :class="inputClass" />
                            </div>
                            <div class="sm:col-span-1">
                                <label :class="labelClass">Pengobatan</label>
                                <Input v-model="studentForm.health.medications" placeholder="Nama obat / alamat rumah sakit" :class="inputClass" />
                            </div>
                            <div class="sm:col-span-3">
                                <label :class="labelClass">Catatan Kesehatan</label>
                                <Input v-model="studentForm.health.notes" placeholder="Catatan kesehatan tambahan" :class="inputClass" />
                            </div>
                        </div>
                    </div>

                    <div v-show="currentStep.id === 'education'" class="space-y-6">
                        <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-4">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-white flex items-center gap-2">
                                <History class="h-4 w-4 text-neutral-500" /> Riwayat Pendidikan
                            </h2>
                            <Button type="button" size="sm" variant="outline" class="h-8 rounded-lg text-xs" @click="addEducation">
                                <Plus class="mr-1 h-3.5 w-3.5" /> Tambah Sekolah
                            </Button>
                        </div>

                        <div v-if="studentForm.education_histories.length === 0" class="py-12 text-center border border-dashed border-neutral-200 dark:border-neutral-800 rounded-xl">
                            <p class="text-xs text-neutral-400 font-medium">Belum ada data riwayat sekolah sebelumnya.</p>
                        </div>

                        <div v-for="(edu, idx) in studentForm.education_histories" :key="idx" class="rounded-xl border border-neutral-200/80 p-4 dark:border-neutral-800 relative space-y-3">
                            <button type="button" class="absolute top-3 right-3 text-neutral-400 hover:text-red-500 transition-colors" @click="removeEducation(idx)">
                                <Trash2 class="h-4 w-4" />
                            </button>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 pr-6">
                                <div>
                                    <label :class="labelClass">Jenjang</label>
                                    <select v-model="edu.education_level_id" :class="selectClass">
                                        <option :value="null">Pilih Jenjang</option>
                                        <option v-for="el in props.educationLevels" :key="el.id" :value="el.id">{{ el.name }}</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-1">
                                    <label :class="labelClass">Nama Sekolah</label>
                                    <Input v-model="edu.school_name" placeholder="Nama Sekolah Sebelumnya" :class="inputClass" />
                                </div>
                                <div>
                                    <label :class="labelClass">NPSN Sekolah</label>
                                    <Input type="text" inputmode="numeric" maxlength="10" v-model="edu.npsn" placeholder="312312" :class="inputClass" />
                                </div>
                                <div class="sm:col-span-3">
                                    <label :class="labelClass">Alamat Sekolah</label>
                                    <Input v-model="edu.address" placeholder="Alamat lengkap sekolah" :class="inputClass" />
                                </div>
                                <div>
                                    <label :class="labelClass">Tahun Masuk</label>
                                    <Input type="text" inputmode="numeric" maxlength="4" v-model="edu.entry_year" placeholder="2018" :class="inputClass" />
                                </div>
                                <div>
                                    <label :class="labelClass">Tahun Lulus</label>
                                    <Input type="text" inputmode="numeric" maxlength="4" v-model="edu.graduation_year" placeholder="2021" :class="inputClass" />
                                </div>
                                <div>
                                    <label :class="labelClass">Nilai Ujian / Rata-rata</label>
                                    <Input v-model="edu.final_score" maxlength="5" @blur="normalizeScoreInput(edu)" placeholder="85.50" :class="inputClass" />
                                </div>
                                <div class="sm:col-span-3">
                                    <label :class="labelClass">Foto / PDF Ijazah atau Dokumen Kelulusan (maks. 5MB)</label>
                                    <Input type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" :class="inputClass" @change="(event: Event) => handleEducationCertificateChange(event, idx)" />
                                    <div v-if="typeof edu.certificate === 'string' && edu.certificate && edu.id" class="mt-2 flex flex-wrap items-center gap-2 text-xs text-neutral-500">
                                        <span class="mr-1">Sertifikat tersimpan: {{ storedFileName(edu.certificate) }}</span>
                                        <Button type="button" variant="outline" size="sm" class="h-8 text-xs" @click="previewEducationCertificate(edu.certificate, edu.id)"><Eye class="mr-1 h-3.5 w-3.5" /> Preview</Button>
                                        <a :href="`/student-education-histories/${edu.id}/certificate/download`" class="inline-flex h-8 items-center rounded-md border px-3 text-xs font-medium"><Download class="mr-1 h-3.5 w-3.5" /> Unduh</a>
                                        <Button type="button" variant="destructive" size="sm" class="h-8 text-xs" @click="deleteEducationCertificate(idx)"><Trash2 class="mr-1 h-3.5 w-3.5" /> Hapus</Button>
                                    </div>
                                    <Button v-else-if="isEducationCertificateFile(edu.certificate)" type="button" variant="outline" size="sm" class="mt-2 h-8 text-xs" @click="previewEducationCertificate(edu.certificate, edu.id)">
                                        <Eye class="mr-1 h-3.5 w-3.5" /> Lihat berkas terpilih
                                    </Button>
                                </div>
                            </div>
                             <div class="flex items-center gap-2 pt-4">
                                <input
                                    type="checkbox"
                                    :id="'graduated_' + idx"
                                    v-model="edu.is_graduated"
                                    class="rounded border-input"
                                />
                                <Label
                                    :for="'graduated_' + idx"
                                    class="cursor-pointer text-xs font-normal"
                                    >Lulus dari Sekolah Ini</Label
                                >
                            </div>
                        </div>
                    </div>

                    <div v-show="currentStep.id === 'socials'" class="space-y-6">
                        <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-4">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-white flex items-center gap-2">
                                <Share2 class="h-4 w-4 text-neutral-500" /> Media Sosial
                            </h2>
                            <Button type="button" size="sm" variant="outline" class="h-8 rounded-lg text-xs" @click="addSocial">
                                <Plus class="mr-1 h-3.5 w-3.5" /> Tambah Akun
                            </Button>
                        </div>

                        <div v-if="studentForm.socials.length === 0" class="py-12 text-center border border-dashed border-neutral-200 dark:border-neutral-800 rounded-xl">
                            <p class="text-xs text-neutral-400 font-medium">Belum ada akun media sosial yang didaftarkan.</p>
                        </div>

                        <div v-for="(soc, idx) in studentForm.socials" :key="idx" class="flex items-center gap-3 rounded-xl border border-neutral-200/80 p-3 dark:border-neutral-800">
                            <div class="w-1/3">
                                <label :class="labelClass">Platform</label>
                                <select v-model="soc.social_platform_id" :class="selectClass">
                                    <option :value="null">Pilih Platform</option>
                                    <option v-for="sp in props.socialPlatforms" :key="sp.id" :value="sp.id">{{ sp.name }}</option>
                                </select>
                            </div>
                            <div class="flex-1">
                                <label :class="labelClass">Username</label>
                                <Input v-model="soc.username" placeholder="@username" :class="inputClass" />
                            </div>
                            <div class="flex-1">
                                <label :class="labelClass">URL</label>
                                <Input v-model="soc.url" placeholder="https://example.com/@username" :class="inputClass" />
                            </div>
                            <button type="button" class="mt-5 text-neutral-400 hover:text-red-500 transition-colors p-2" @click="removeSocial(idx)">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div v-show="currentStep.id === 'achievements'" class="space-y-6">
                        <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-4">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-white flex items-center gap-2">
                                <Trophy class="h-4 w-4 text-neutral-500" /> Rekam Prestasi
                            </h2>
                            <Button type="button" size="sm" variant="outline" class="h-8 rounded-lg text-xs" @click.stop="addAchievement">
                                <Plus class="mr-1 h-3.5 w-3.5" /> Tambah Prestasi
                            </Button>
                        </div>

                        <div v-if="studentForm.achievements.length === 0" class="py-12 text-center border border-dashed border-neutral-200 dark:border-neutral-800 rounded-xl">
                            <p class="text-xs text-neutral-400 font-medium">Belum ada data prestasi yang dicatat.</p>
                        </div>

                        <div v-for="(ach, idx) in studentForm.achievements" :key="idx" class="rounded-xl border border-neutral-200/80 p-4 dark:border-neutral-800 relative space-y-3">
                            <button type="button" class="absolute top-3 right-3 text-neutral-400 hover:text-red-500 transition-colors" @click="removeAchievement(idx)">
                                <Trash2 class="h-4 w-4" />
                            </button>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 pr-6">
                                <div class="sm:col-span-1">
                                    <label :class="labelClass">Nama Kejuaraan / Lomba</label>
                                    <Input v-model="ach.title" placeholder="Juara 1 O2SN..." :class="inputClass" />
                                </div>
                                <div>
                                    <label :class="labelClass">Penyelenggara</label>
                                    <Input v-model="ach.organizer" placeholder="Instansi / Dinas" :class="inputClass" />
                                </div>
                                <div>
                                    <label :class="labelClass">Kategori</label>
                                    <select
                                        v-model="ach.category"
                                        class="w-full rounded-md border border-input bg-background p-3 text-xs"
                                    >
                                        <option value="">Pilih Kategori</option>
                                        <option value="Akademik">Akademik</option>
                                        <option value="Non Akademik">Non Akademik</option>
                                    </select>
                                </div>
                                <div>
                                    <label :class="labelClass">Tingkat</label>
                                    <Input v-model="ach.level" placeholder="Kota / Provinsi / Nasional" :class="inputClass" />
                                </div>
                                <div>
                                    <label :class="labelClass">Peringkat</label>
                                    <Input type="text" maxlength="6" inputmode="numeric" v-model="ach.rank" placeholder="1" :class="inputClass" />
                                </div>
                                <div>
                                    <label :class="labelClass">Tanggal Kejuaraan</label>
                                    <Input type="date" v-model="ach.achievement_date" :class="inputClass" />
                                </div>

                                <!-- TAMBAHAN INPUT SERTIFIKAT -->
                                <div class="sm:col-span-3">
                                    <label :class="labelClass">Berkas Sertifikat / Piagam (PDF, JPG, PNG - Max 2MB)</label>
                                    <Input
                                        :id="`certificate-${idx}`"
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png,image/jpeg,image/png,application/pdf"
                                        :class="inputClass"
                                        @change="(e: Event) => handleCertificateFileChange(e, idx)"
                                    />
                                    <Button
                                        v-if="isCertificateFile(ach.certificate)"
                                        type="button" variant="outline" size="sm" class="mt-2 h-8 text-xs"
                                        @click="previewCertificate(ach.certificate)"
                                    ><Eye class="mr-1 h-3.5 w-3.5" /> Lihat sertifikat</Button>
                                    
                                    <!-- Status Sertifikat Tersimpan (Edit Mode) -->
                                    <div v-if="typeof ach.certificate === 'string' && ach.certificate" class="mt-2 flex items-center gap-2 text-xs text-neutral-500">
                                        <span>Sertifikat tersimpan: {{ storedFileName(ach.certificate) }}</span>
                                        <Button type="button" variant="outline" size="sm" class="h-7 text-[11px]" @click="openFilePreview(`/storage/${ach.certificate}`, 'Sertifikat prestasi')"><Eye class="mr-1 h-3 w-3" /> Lihat</Button>
                                        <a :href="`/storage/${ach.certificate}`" download class="inline-flex h-7 items-center rounded-md border px-2 text-[11px] font-medium"><Download class="mr-1 h-3 w-3" /> Unduh</a>
                                        <Button v-if="ach.id" type="button" variant="destructive" size="sm" class="h-7 text-[11px]" @click="deleteAchievementCertificate(idx)">Hapus sertifikat</Button>
                                    </div>
                                    
                                    <!-- Error Message dari Inertia -->
                                    <p v-if="studentForm.errors[`achievements.${idx}.certificate`]" class="mt-1 text-xs text-red-500">
                                        {{ studentForm.errors[`achievements.${idx}.certificate`] }}
                                    </p>
                                </div>

                                <div class="sm:col-span-3">
                                    <label :class="labelClass">Deskripsi</label>
                                    <Input v-model="ach.description" placeholder="Deskripsi Singkat" :class="inputClass" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="currentStep.id === 'violations'" class="space-y-6">
                        <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-4">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-white flex items-center gap-2">
                                <Shield class="h-4 w-4 text-neutral-500" /> Catatan Kedisiplinan
                            </h2>
                            <Button type="button" size="sm" variant="outline" class="h-8 rounded-lg text-xs" @click="addViolation">
                                <Plus class="mr-1 h-3.5 w-3.5" /> Catat Pelanggaran
                            </Button>
                        </div>

                        <div v-if="studentForm.violations.length === 0" class="py-12 text-center border border-dashed border-neutral-200 dark:border-neutral-800 rounded-xl">
                            <p class="text-xs text-neutral-400 font-medium">Siswa tidak memiliki rekam pelanggaran.</p>
                        </div>

                        <div v-for="(vio, idx) in studentForm.violations" :key="idx" class="rounded-xl border border-neutral-200/80 p-4 dark:border-neutral-800 relative space-y-3">
                            <button type="button" class="absolute top-3 right-3 text-neutral-400 hover:text-red-500 transition-colors" @click="removeViolation(idx)">
                                <Trash2 class="h-4 w-4" />
                            </button>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 pr-6">
                                <div class="sm:col-span-2">
                                    <label :class="labelClass">Bentuk Pelanggaran</label>
                                    <Input v-model="vio.title" placeholder="Jenis pelanggaran" :class="inputClass" />
                                </div>
                                <div>
                                    <label :class="labelClass">Poin Kedisiplinan</label>
                                    <Input type="text" inputmode="numeric" v-model="vio.point" placeholder="10" :class="inputClass" />
                                </div>
                                <div>
                                    <label :class="labelClass">Tanggal Kejadian</label>
                                    <Input type="date" v-model="vio.violation_date" :class="inputClass" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label :class="labelClass">Keterangan / Tindakan</label>
                                    <Input v-model="vio.description" placeholder="Penanganan atau catatan" :class="inputClass" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-show="currentStep.id === 'documents'" class="space-y-6">
                        <div class="border-b border-neutral-100 dark:border-neutral-800 pb-4">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-white flex items-center gap-2">
                                <FileText class="h-4 w-4 text-neutral-500" /> Berkas & Dokumen Siswa
                            </h2>
                        </div>

                        <div class="rounded-xl border border-neutral-200/80 bg-neutral-50/50 p-4 dark:border-neutral-800 dark:bg-neutral-950/40 space-y-3">
                            <h3 class="text-xs font-bold uppercase text-neutral-700 dark:text-neutral-300">Unggah Berkas Baru</h3>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label :class="labelClass">Jenis Dokumen</label>
                                    <select v-model="studentForm.document_type_id" :class="selectClass">
                                        <option :value="null">Pilih Tipe Dokumen</option>
                                        <option v-for="dt in props.documentTypes" :key="dt.id" :value="dt.id">{{ dt.name }}</option>
                                    </select>
                                </div>
                                  <div class="space-y-2">
                                    <Label class="text-xs"
                                        >Keterangan / Nama Dokumen</Label
                                    >
                                    <Input
                                        v-model="studentForm.new_document_notes"
                                        placeholder="Misal: Ijazah SMP, KK, Akta"
                                        class="text-sm"
                                    />
                                </div>
                                <div>
                                    <label :class="labelClass">File Berkas (PDF / Gambar)</label>
                                    <Input type="file" @change="handleDocumentFileChange" class="h-10 text-xs file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:bg-neutral-200 file:text-neutral-700 hover:file:bg-neutral-300 dark:file:bg-neutral-800 dark:file:text-neutral-300" />
                                    <Button v-if="studentForm.new_document_file" type="button" variant="outline" size="sm" class="mt-2 h-8 text-xs" @click="openUploadPreview(studentForm.new_document_file, studentForm.new_document_notes || studentForm.new_document_file.name)"><Eye class="mr-1 h-3.5 w-3.5" /> Lihat berkas</Button>
                                </div>
                            </div>
                            <p v-if="isDocumentTypeExists" class="text-[11px] font-medium text-amber-600 dark:text-amber-400">
                                Catatan: Berkas jenis ini sudah diunggah. Mengunggah kembali akan menggantikan berkas lama.
                            </p>
                        </div>

                        <div v-if="visibleDocuments.length > 0" class="space-y-3 pt-2">
                            <h3 class="text-xs font-bold uppercase text-neutral-400">Dokumen Tersimpan</h3>
                            <div class="divide-y divide-neutral-100 rounded-xl border border-neutral-200/80 bg-white dark:divide-neutral-800 dark:border-neutral-800 dark:bg-neutral-900">
                                <div v-for="doc in visibleDocuments" :key="doc.id" class="flex items-center justify-between p-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-300">
                                            <FileText class="h-4 w-4" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-neutral-800 dark:text-neutral-200">{{ doc.notes || doc.original_name || doc.file_name || 'Dokumen' }}</p>
                                            <p class="text-[11px] text-neutral-400">{{ doc.document_type?.name || 'Dokumen' }}<span v-if="doc.notes && (doc.original_name || doc.file_name)"> · {{ doc.original_name || doc.file_name }}</span></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="rounded-lg border border-neutral-200 px-3 py-1.5 text-[11px] font-medium text-neutral-600 hover:bg-neutral-50 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800" @click="openFilePreview(documentPreviewUrl(doc.id), doc.notes || doc.original_name || doc.file_name || 'Dokumen siswa', doc.mime_type || '')">
                                            Pratinjau
                                        </button>
                                        <a :href="documentDownloadUrl(doc.id)" class="rounded-lg bg-blue-600 px-3 py-1.5 text-[11px] font-medium text-white hover:bg-blue-700"><Download class="mr-1 inline h-3 w-3" /> Unduh</a>
                                        <button type="button" class="p-1.5 text-neutral-400 hover:text-red-500 transition-colors" @click="deleteDocument(doc.id)">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="flex shrink-0 items-center justify-between border-t border-neutral-100 bg-neutral-50/50 p-4 dark:border-neutral-800 dark:bg-neutral-950/50">
                    <Button type="button" variant="ghost" class="h-9 px-4 text-xs font-medium rounded-lg text-neutral-500 hover:text-neutral-900 dark:hover:text-white" @click="handleCancel" :disabled="isFormProcessing">
                        Batal
                    </Button>
                    <div class="flex items-center gap-2">
                        <Button type="button" variant="outline" :disabled="isFirstStep" @click="prevStep" class="h-9 px-4 text-xs rounded-lg border-neutral-200 dark:border-neutral-800">
                            <ChevronLeft class="mr-1 h-3.5 w-3.5" /> Kembali
                        </Button>
                        <Button v-if="!isLastStep" type="button" @click="nextStep" class="h-9 px-4 text-xs rounded-lg bg-neutral-900 text-white hover:bg-black dark:bg-white dark:text-black dark:hover:bg-neutral-200 font-medium">
                            Lanjut <ChevronRight class="ml-1 h-3.5 w-3.5" />
                        </Button>
                        <Button v-else type="submit" :disabled="isFormProcessing || isReadOnlyForStudent" class="h-9 px-5 text-xs rounded-lg bg-neutral-900 text-white hover:bg-black dark:bg-white dark:text-black dark:hover:bg-neutral-200 font-semibold shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <Loader2 v-if="isFormProcessing" class="mr-2 h-3.5 w-3.5 animate-spin" />
                            <span v-if="isReadOnlyForStudent">Data Terverifikasi (Terkunci)</span>
                            <span v-else>Simpan Perubahan</span>
                        </Button>
                    </div>
                </div>
            </form>
        </main>

        <div v-if="filePreview" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm" @click.self="closeFilePreview" @keydown.esc="closeFilePreview">
            <section class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-neutral-200 bg-white shadow-2xl dark:border-neutral-800 dark:bg-neutral-900" role="dialog" aria-modal="true" :aria-label="filePreview.title">
                <header class="flex items-center justify-between gap-4 border-b border-neutral-200 px-4 py-3 dark:border-neutral-800">
                    <h2 class="truncate text-sm font-semibold">{{ filePreview.title }}</h2>
                    <button type="button" class="rounded-lg border border-neutral-200 px-3 py-1.5 text-xs font-semibold text-neutral-600 hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800" aria-label="Tutup pratinjau (Esc)" @click="closeFilePreview">Esc</button>
                </header>
                <div class="flex min-h-0 flex-1 items-center justify-center overflow-auto bg-neutral-100 p-3 dark:bg-neutral-950">
                    <iframe v-if="filePreview.isPdf" :src="filePreview.url" class="h-[78vh] w-full rounded-lg bg-white" :title="filePreview.title" />
                    <img v-else :src="filePreview.url" :alt="filePreview.title" class="max-h-[78vh] max-w-full rounded-lg object-contain" />
                </div>
            </section>
        </div>

        <div v-if="shouldShowPhotoCrop" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="w-full max-w-sm rounded-2xl bg-neutral-900 p-5 text-white shadow-2xl border border-neutral-800 space-y-4">
                <div class="flex items-center justify-between border-b border-neutral-800 pb-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-300">Penyesuaian Foto</h3>
                    <span class="text-[10px] text-neutral-500">Geser untuk memposisikan</span>
                </div>
                
                <div class="relative h-60 w-full touch-none overflow-hidden rounded-xl border border-neutral-800 bg-black cursor-move select-none"
                    @pointerdown="handleCropPointerDown"
                    @pointermove="handleCropPointerMove"
                    @pointerup="handleCropPointerEnd"
                    @pointercancel="handleCropPointerEnd"
                >
                    <img v-if="studentPhotoSrc" :src="studentPhotoSrc" class="pointer-events-none absolute h-full w-full object-cover"
                        :style="{
                            transform: `scale(${photoZoom / 100})`,
                            transformOrigin: `${photoPosition.x}% ${photoPosition.y}%`,
                            objectPosition: `${photoPosition.x}% ${photoPosition.y}%`,
                        }"
                    />
                </div>

                <p class="text-[11px] text-neutral-400">Geser foto atau gunakan pengatur posisi untuk memilih bagian yang masuk ke bingkai.</p>

                <div class="grid grid-cols-2 gap-4">
                    <label class="space-y-1 text-[11px] text-neutral-400">
                        <span class="flex justify-between"><span>Horizontal</span><span>{{ Math.round(photoPosition.x) }}%</span></span>
                        <input v-model.number="photoPosition.x" type="range" min="0" max="100" class="w-full accent-white" />
                    </label>
                    <label class="space-y-1 text-[11px] text-neutral-400">
                        <span class="flex justify-between"><span>Vertikal</span><span>{{ Math.round(photoPosition.y) }}%</span></span>
                        <input v-model.number="photoPosition.y" type="range" min="0" max="100" class="w-full accent-white" />
                    </label>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between text-[11px] text-neutral-400">
                        <span>Zoom</span>
                        <span class="font-mono text-white">{{ photoZoom }}%</span>
                    </div>
                    <input type="range" min="100" max="200" step="5" v-model.number="photoZoom" class="w-full h-1 bg-neutral-800 rounded-lg appearance-none cursor-pointer accent-white" />
                </div>

                <div class="flex justify-end gap-2 border-t border-neutral-800 pt-3">
                    <button type="button" class="rounded-lg px-3 py-1.5 text-xs text-neutral-400 hover:text-white" @click="photoPosition = { x: 50, y: 50 }; photoZoom = 100">Reset</button>
                    <button type="button" class="rounded-lg bg-white px-4 py-1.5 text-xs font-semibold text-black hover:bg-neutral-200" @click="isPhotoCropOpen = false">Selesai</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
