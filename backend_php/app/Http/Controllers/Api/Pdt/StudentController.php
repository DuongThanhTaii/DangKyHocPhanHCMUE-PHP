<?php

namespace App\Http\Controllers\Api\Pdt;

use App\Http\Controllers\Controller;
use App\Application\Pdt\Services\StudentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    protected $studentService;

    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    private function checkPermission()
    {
        $user = Auth::user();
        // TODO: implement permission check if needed
    }

    public function index(Request $request)
    {
        try {
            $limit = $request->get('limit', 100);
            $search = $request->get('search');

            $paginator = $this->studentService->getStudents($limit, $search);

            // Map to camelCase for frontend
            $items = collect($paginator->items())->map(function ($sv) {
                return [
                    'id' => $sv->id,
                    'maSoSinhVien' => $sv->ma_so_sinh_vien,
                    'hoTen' => $sv->user?->ho_ten ?? '',
                    'lop' => $sv->lop ?? '',
                    'khoaHoc' => $sv->khoa_hoc ?? '',
                    'tenKhoa' => $sv->khoa?->ten_khoa ?? '',
                    'tenNganh' => $sv->nganh?->ten_nganh ?? '',
                    'trangThaiHoatDong' => $sv->trang_thai_hoat_dong ?? true,
                    'email' => $sv->user?->email ?? '',
                    'ngayNhapHoc' => $sv->ngay_nhap_hoc,
                    'khoaId' => $sv->khoa_id,
                    'nganhId' => $sv->nganh_id,
                ];
            });

            return response()->json([
                'isSuccess' => true,
                'data' => [
                    'items' => $items,
                    'total' => $paginator->total(),
                    'page' => $paginator->currentPage(),
                    'pageSize' => $paginator->perPage(),
                ],
                'message' => 'Lấy danh sách sinh viên thành công'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Map camelCase (frontend) -> snake_case (backend)
            $request->merge([
                'ma_so_sinh_vien' => trim((string) ($request->input('maSoSinhVien') ?? $request->input('ma_so_sinh_vien') ?? '')),
                'ho_ten' => trim((string) ($request->input('hoTen') ?? $request->input('ho_ten') ?? '')),
                'email' => trim((string) ($request->input('email') ?? $request->input('maSoSinhVien') . '@student.edu.vn' ?? '')),
                'khoa_id' => $request->input('khoaId') ?? $request->input('maKhoa') ?? $request->input('khoa_id'),
                'nganh_id' => $request->input('nganhId') ?? $request->input('maNganh') ?? $request->input('nganh_id'),
                'lop' => $request->input('lop'),
                'khoa_hoc' => $request->input('khoaHoc') ?? $request->input('khoa_hoc'),
                'ngay_nhap_hoc' => $request->input('ngayNhapHoc') ?? $request->input('ngay_nhap_hoc'),
                'password' => $request->input('matKhau') ?? $request->input('password'),
                'ten_dang_nhap' => $request->input('tenDangNhap') ?? $request->input('ten_dang_nhap') ?? $request->input('maSoSinhVien'),
            ]);

            $validated = $request->validate([
                'ma_so_sinh_vien' => 'required|unique:sinh_vien,ma_so_sinh_vien',
                'ho_ten' => 'required|string',
                'khoa_id' => 'required|uuid',
                'lop' => 'nullable|string',
                'khoa_hoc' => 'nullable|string',
                'ngay_nhap_hoc' => 'nullable|date',
                'nganh_id' => 'nullable|uuid',
                'password' => 'nullable|string',
                'ten_dang_nhap' => 'nullable|string',
                'email' => 'nullable|string',
            ]);

            $student = $this->studentService->createStudent($validated);

            return response()->json([
                'isSuccess' => true,
                'data' => $student,
                'message' => 'Tạo sinh viên thành công'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi validation: ' . implode(', ', $e->validator->errors()->all()),
                'errors' => $e->validator->errors()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        // trim nếu có
        if ($request->has('email'))
            $request->merge(['email' => trim((string) $request->input('email'))]);
        if ($request->has('ho_ten'))
            $request->merge(['ho_ten' => trim((string) $request->input('ho_ten'))]);
        if ($request->has('ma_so_sinh_vien'))
            $request->merge(['ma_so_sinh_vien' => trim((string) $request->input('ma_so_sinh_vien'))]);

        $request->validate([
            'ma_so_sinh_vien' => 'sometimes|unique:sinh_vien,ma_so_sinh_vien,' . $id . ',id',
            'email' => 'sometimes|email|unique:users,email,' . $id . ',id',
            'ho_ten' => 'sometimes|string',
            'khoa_id' => 'sometimes|required|uuid', // ✅ nếu gửi lên thì không được null
            'lop' => 'sometimes|nullable|string',
            'khoa_hoc' => 'sometimes|nullable|string',
            'ngay_nhap_hoc' => 'sometimes|nullable|date',
            'nganh_id' => 'sometimes|nullable|uuid',
        ]);

        $student = $this->studentService->updateStudent($id, $request->all());
        return response()->json($student);
    }

    public function destroy($id)
    {
        try {
            $this->studentService->deleteStudent($id);
            return response()->json([
                'isSuccess' => true,
                'data' => null,
                'message' => 'Xóa sinh viên thành công'
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'isSuccess' => false,
                'data' => null,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function import(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        try {
            $result = $this->studentService->importStudents($request->file('file'));
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
