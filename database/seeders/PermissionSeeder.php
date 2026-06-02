<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

final class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->permissions() as $data) {
            Permission::query()->updateOrCreate(
                ['code' => $data['code']],
                $data,
            );
        }
    }

    /**
     * @return array<int, array{code: string, name: string, scope: string, status: string}>
     */
    private function permissions(): array
    {
        return [
            ['code' => 'schools.view', 'name' => 'View schools', 'scope' => 'platform', 'status' => 'active'],
            ['code' => 'schools.manage', 'name' => 'Manage schools', 'scope' => 'platform', 'status' => 'active'],
            ['code' => 'schools.lifecycle', 'name' => 'Manage school lifecycle', 'scope' => 'platform', 'status' => 'active'],
            ['code' => 'users.view', 'name' => 'View users', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'users.manage', 'name' => 'Manage users', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'users.lifecycle', 'name' => 'Manage user lifecycle', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'roles.view', 'name' => 'View roles', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'roles.manage', 'name' => 'Manage roles', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'roles.lifecycle', 'name' => 'Manage role lifecycle', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'permissions.view', 'name' => 'View permissions', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'academic_years.view', 'name' => 'View academic years', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'academic_years.manage', 'name' => 'Manage academic years', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'academic_years.lifecycle', 'name' => 'Manage academic year lifecycle', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'academic_periods.view', 'name' => 'View academic periods', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'academic_periods.manage', 'name' => 'Manage academic periods', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'academic_periods.lifecycle', 'name' => 'Manage academic period lifecycle', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'guardians.view', 'name' => 'View guardians', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'guardians.manage', 'name' => 'Manage guardians', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'guardians.lifecycle', 'name' => 'Manage guardian lifecycle', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'student_profiles.view', 'name' => 'View student profiles', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'student_profiles.manage', 'name' => 'Manage student profiles', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'student_transfers.manage', 'name' => 'Manage student transfers', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'teacher_content.view', 'name' => 'View teacher content', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'teacher_content.manage', 'name' => 'Manage teacher content', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'questionnaires.view', 'name' => 'View questionnaires', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'questionnaires.manage', 'name' => 'Manage questionnaires', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'learning_sets.view', 'name' => 'View learning sets', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'learning_sets.manage', 'name' => 'Manage learning sets', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'grades.view', 'name' => 'View grades', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'grades.manage', 'name' => 'Manage grades', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'attendance.view', 'name' => 'View attendance', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'attendance.manage', 'name' => 'Manage attendance', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'classroom_rosters.manage', 'name' => 'Manage classroom rosters', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'student_self_view.view', 'name' => 'View own student records', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'reports.view', 'name' => 'View school reports', 'scope' => 'school', 'status' => 'active'],
            ['code' => 'reports.request', 'name' => 'Request school reports', 'scope' => 'school', 'status' => 'active'],
        ];
    }
}
