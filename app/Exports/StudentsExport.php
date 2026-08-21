<?php

namespace App\Exports;

use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    /** @var array<string, mixed> */
    protected array $filters;

    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder|Relation
    {
        $query = Student::query()->with([
            'currentEnrollment.classroom.major',
            'currentEnrollment.academicYear',
            'currentEnrollment.status',
            'gender',
            'religion',
            'health',
        ]);

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        if (! empty($this->filters['citizenship_id'])) {
            $query->where('citizenship_id', $this->filters['citizenship_id']);
        }

        if (! empty($this->filters['gender_id'])) {
            $query->where('gender_id', $this->filters['gender_id']);
        }

        if (! empty($this->filters['religion_id'])) {
            $query->where('religion_id', $this->filters['religion_id']);
        }

        if (! empty($this->filters['blood_type_id'])) {
            $query->whereHas('health', function ($q) {
                $q->where('blood_type_id', $this->filters['blood_type_id']);
            });
        }

        $query->whereHas('currentEnrollment', function ($q) {
            if (! empty($this->filters['classroom_id'])) {
                $q->where('classroom_id', $this->filters['classroom_id']);
            }

            if (! empty($this->filters['academic_year_id'])) {
                $q->where('academic_year_id', $this->filters['academic_year_id']);
            }

            if (! empty($this->filters['student_status_id'])) {
                $q->where('student_status_id', $this->filters['student_status_id']);
            }

            if (! empty($this->filters['major_id'])) {
                $q->whereHas('classroom', function ($cq) {
                    $cq->where('major_id', $this->filters['major_id']);
                });
            }
        });

        return $query;
    }

    public function headings(): array
    {
        return [
            'NO',
            'NAMA LENGKAP',
            'NISN',
            'NIS',
            'KELAS',
            'JURUSAN',
            'TAHUN AJARAN',
            'STATUS SISWA',
            'JENIS KELAMIN',
            'AGAMA',
            'NO. TELEPON',
            'EMAIL',
        ];
    }

    private int $rowNumber = 0;

    /**
     * @param Student $student
     */
    public function map($student): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $student->full_name,
            $student->nisn ? "'{$student->nisn}" : '-',
            $student->nis ? "'{$student->nis}" : '-',
            $student->classroom->name ?? '-',
            $student->major->name ?? '-',
            $student->academic_year->name ?? '-',
            $student->student_status->name ?? '-',
            $student->gender->name ?? '-',
            $student->religion->name ?? '-',
            $student->phone ?? '-',
            $student->email ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => '0D9488'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $cellRange = "A1:{$highestColumn}{$highestRow}";

                $sheet->getRowDimension(1)->setRowHeight(28);

                $sheet->getStyle($cellRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'CBD5E1'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle("A2:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C2:D{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("G2:J{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}