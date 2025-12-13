<?php

namespace App\Application\Pdt\Services;

use App\Infrastructure\Pdt\Persistence\Repositories\StudentRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentService
{
    protected $studentRepository;

    public function __construct(StudentRepository $studentRepository)
    {
        $this->studentRepository = $studentRepository;
    }

    public function getStudents($limit = 10, $search = null)
    {
        return $this->studentRepository->paginate($limit, $search);
    }

    public function createStudent(array $data)
    {
        if (empty($data['khoa_id'])) {
            throw ValidationException::withMessages(['khoa_id' => 'khoa_id is required']);
        }

        if ($this->studentRepository->findByStudentCode($data['ma_so_sinh_vien'])) {
            throw ValidationException::withMessages(['ma_so_sinh_vien' => 'Code already exists']);
        }

        return $this->studentRepository->create($data);
    }

    public function updateStudent($id, array $data)
    {
        return $this->studentRepository->update($id, $data);
    }

    public function deleteStudent($id)
    {
        return $this->studentRepository->delete($id);
    }

    public function importStudents($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $count = 0;
        $errors = [];
        $rows = [];

        // CSV
        if (in_array($extension, ['csv', 'txt'])) {
            $handle = fopen($file->getRealPath(), "r");
            $header = fgetcsv($handle);

            while (($csvRow = fgetcsv($handle)) !== false) {
                if (count($header) === count($csvRow)) {
                    $cleanHeader = array_map(function ($h) {
                        return strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h)));
                    }, $header);

                    $rows[] = array_combine($cleanHeader, $csvRow);
                }
            }

            fclose($handle);
        }

        // Excel
        if (class_exists(\Maatwebsite\Excel\Facades\Excel::class) && in_array($extension, ['xlsx', 'xls'])) {
            try {
                $data = \Maatwebsite\Excel\Facades\Excel::toArray(new \App\Application\Pdt\Imports\StudentsImport, $file);
                $rows = $data[0] ?? [];
            } catch (\Throwable $e) {
                $errors[] = "Excel read failed: " . $e->getMessage();
            }
        }

        $getValue = function ($keys, $dataRow) {
            foreach ($keys as $k) {
                if (isset($dataRow[$k])) return $dataRow[$k];
            }
            return null;
        };

        foreach ($rows as $row) {
            $maSo = trim((string) $getValue(['ma_so_sinh_vien', 'mssv', 'code'], $row));
            $hoTen = trim((string) $getValue(['ho_ten', 'full_name', 'name'], $row));
            $email = trim((string) $getValue(['email', 'mail'], $row));
            $khoaId = trim((string) $getValue(['khoa_id', 'khoa'], $row));

            if ($maSo === '' || $hoTen === '') {
                continue;
            }

            // DB sinh_vien.khoa_id NOT NULL => thiếu là skip
            if ($khoaId === '') {
                $errors[] = "Skip {$maSo}: missing khoa_id";
                continue;
            }

            // Email: tránh fail CHECK do khoảng trắng / rỗng
            if ($email === '') {
                $email = $maSo . '@student.hcmue.edu.vn';
            } else {
                $email = trim($email);
            }

            $studentData = [
                'ma_so_sinh_vien' => $maSo,
                'ho_ten' => $hoTen,
                'email' => $email,
                'lop' => $row['lop'] ?? null,
                'khoa_id' => $khoaId,
                'khoa_hoc' => $row['khoa_hoc'] ?? null,
                'password' => $row['password'] ?? $maSo,
            ];

            try {
                $existing = $this->studentRepository->findByStudentCode($maSo);

                if ($existing) {
                    $this->studentRepository->update($existing->id, $studentData);
                } else {
                    $this->studentRepository->create($studentData);
                }

                $count++;
            } catch (\Throwable $e) {
                // đảm bảo không kẹt transaction level (tránh 25P02 dây chuyền khi import)
                while (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }

                $errors[] = "Error importing {$maSo}: " . $e->getMessage();
            }
        }

        return [
            'count' => $count,
            'errors' => $errors
        ];
    }
}
