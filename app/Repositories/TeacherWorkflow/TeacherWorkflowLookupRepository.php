<?php

declare(strict_types=1);

namespace App\Repositories\TeacherWorkflow;

use App\Models\AcademicPeriod;
use App\Models\AttendanceRecord;
use App\Models\ClassSection;
use App\Models\GradeRecord;
use App\Models\LearningSet;
use App\Models\Questionnaire;
use App\Models\RosterMembership;
use App\Models\StudentProfile;
use App\Models\TeacherAssignment;
use App\Models\TeacherContentItem;
use App\Models\User;

final class TeacherWorkflowLookupRepository
{
    public function findTeacherContent(string $uuid, int $schoolId): ?TeacherContentItem
    {
        return TeacherContentItem::query()->withTrashed()->where('uuid', $uuid)->where('school_id', $schoolId)->first();
    }

    public function findQuestionnaire(string $uuid, int $schoolId): ?Questionnaire
    {
        return Questionnaire::query()->withTrashed()->where('uuid', $uuid)->where('school_id', $schoolId)->first();
    }

    public function findLearningSet(string $uuid, int $schoolId): ?LearningSet
    {
        return LearningSet::query()->withTrashed()->where('uuid', $uuid)->where('school_id', $schoolId)->first();
    }

    public function findGrade(string $uuid, int $schoolId): ?GradeRecord
    {
        return GradeRecord::query()->withTrashed()->where('uuid', $uuid)->where('school_id', $schoolId)->first();
    }

    public function findAttendance(string $uuid, int $schoolId): ?AttendanceRecord
    {
        return AttendanceRecord::query()->withTrashed()->where('uuid', $uuid)->where('school_id', $schoolId)->first();
    }

    public function findClassSection(int $id, int $schoolId): ?ClassSection
    {
        return ClassSection::query()->whereKey($id)->where('school_id', $schoolId)->first();
    }

    public function findRosterMembership(int $id, int $schoolId): ?RosterMembership
    {
        return RosterMembership::query()->whereKey($id)->where('school_id', $schoolId)->first();
    }

    public function findTeacherAssignment(int $id, int $schoolId): ?TeacherAssignment
    {
        return TeacherAssignment::query()->whereKey($id)->where('school_id', $schoolId)->first();
    }

    public function findStudentProfile(string $uuid, int $schoolId): ?StudentProfile
    {
        return StudentProfile::query()->where('uuid', $uuid)->where('school_id', $schoolId)->first();
    }

    public function findTeacher(string $uuid, int $schoolId): ?User
    {
        return User::query()->where('uuid', $uuid)->where('school_id', $schoolId)->first();
    }

    public function findAcademicPeriod(string $uuid, int $schoolId): ?AcademicPeriod
    {
        return AcademicPeriod::query()->where('uuid', $uuid)->where('school_id', $schoolId)->first();
    }
}
