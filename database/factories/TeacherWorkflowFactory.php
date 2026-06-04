<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\AttendanceRecord;
use App\Models\GradeRecord;
use App\Models\LearningSet;
use App\Models\LearningSetAssignment;
use App\Models\LearningSetEntry;
use App\Models\Questionnaire;
use App\Models\QuestionnaireQuestion;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\TeacherContentFolder;
use App\Models\TeacherContentItem;
use App\Models\User;
use Illuminate\Support\Str;

final class TeacherWorkflowFactory
{
    public static function folder(School $school, User $owner, array $attributes = []): TeacherContentFolder
    {
        return TeacherContentFolder::query()->create($attributes + [
            'school_id' => $school->id,
            'owner_user_id' => $owner->id,
            'name' => 'Resources',
            'status' => 'active',
        ]);
    }

    public static function cleanContent(School $school, User $owner, array $attributes = []): TeacherContentItem
    {
        return TeacherContentItem::query()->create($attributes + [
            'school_id' => $school->id,
            'owner_user_id' => $owner->id,
            'title' => 'Teacher Content',
            'description' => $attributes['description'] ?? null,
            'content_type' => 'pdf',
            'declared_content_type' => 'application/pdf',
            'detected_content_type' => 'application/pdf',
            'file_size_bytes' => 1024,
            'storage_path' => $school->uuid.'/'.Str::uuid().'/content.pdf',
            'scan_status' => 'clean',
            'status' => 'active',
        ]);
    }

    public static function questionnaire(School $school, User $owner, array $attributes = []): Questionnaire
    {
        $questionnaire = Questionnaire::query()->create($attributes + [
            'school_id' => $school->id,
            'owner_user_id' => $owner->id,
            'title' => 'Quiz',
            'description' => $attributes['description'] ?? null,
            'status' => 'active',
        ]);

        QuestionnaireQuestion::query()->create([
            'questionnaire_id' => $questionnaire->id,
            'question_type' => 'true_false',
            'prompt' => 'Ready?',
            'sequence' => 1,
        ]);

        return $questionnaire->load('questions');
    }

    public static function learningSet(School $school, User $owner, AcademicPeriod $period, StudentProfile $student): LearningSet
    {
        $learningSet = LearningSet::query()->create([
            'school_id' => $school->id,
            'owner_user_id' => $owner->id,
            'academic_period_id' => $period->id,
            'title' => 'Learning Set',
            'status' => 'published',
            'published_at' => now(),
        ]);

        LearningSetAssignment::query()->create([
            'school_id' => $school->id,
            'learning_set_id' => $learningSet->id,
            'student_profile_id' => $student->id,
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        return $learningSet->load('assignments');
    }

    public static function learningSetEntry(School $school, LearningSet $learningSet, string $entryType, int $referenceId, int $sequence = 1): LearningSetEntry
    {
        return LearningSetEntry::query()->create([
            'school_id' => $school->id,
            'learning_set_id' => $learningSet->id,
            'entry_type' => $entryType,
            'entry_reference_id' => $referenceId,
            'sequence' => $sequence,
        ]);
    }

    public static function grade(School $school, User $teacher, AcademicPeriod $period, StudentProfile $student): GradeRecord
    {
        return GradeRecord::query()->create([
            'school_id' => $school->id,
            'student_profile_id' => $student->id,
            'academic_period_id' => $period->id,
            'recorded_by_user_id' => $teacher->id,
            'grade_value' => 95,
            'grade_label' => 'A',
            'recorded_at' => now(),
            'status' => 'active',
        ]);
    }

    public static function attendance(School $school, User $teacher, AcademicPeriod $period, StudentProfile $student): AttendanceRecord
    {
        return AttendanceRecord::query()->create([
            'school_id' => $school->id,
            'student_profile_id' => $student->id,
            'academic_period_id' => $period->id,
            'recorded_by_user_id' => $teacher->id,
            'attendance_date' => now()->toDateString(),
            'attendance_status' => 'present',
            'status' => 'active',
        ]);
    }
}
