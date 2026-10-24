<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard.view',

            'class.view',
            'class.create',
            'class.update',
            'class.delete',

            'subject.view',
            'subject.create',
            'subject.update',
            'subject.delete',

            'student.view',
            'student.create',
            'student.update',
            'student.delete',

            'parent.view',
            'parent.create',
            'parent.update',
            'parent.delete',

            'question.view',
            'question.create',
            'question.update',
            'question.delete',

            'exam.view',
            'exam.create',
            'exam.update',
            'exam.delete',
            'exam.publish',
            'exam.close',

            'exam.take',
            'exam.violation.view',

            'result.view',
            'result.view_own',
            'result.view_child',

            'billing.view',
            'billing.manage',
            'billing.view_own',
            'billing.view_child',
            'billing.payment.submit',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $admin = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);

        $tentor = Role::firstOrCreate([
            'name' => 'Tentor',
            'guard_name' => 'web',
        ]);

        $siswa = Role::firstOrCreate([
            'name' => 'Siswa',
            'guard_name' => 'web',
        ]);

        $orangTua = Role::firstOrCreate([
            'name' => 'Orang Tua',
            'guard_name' => 'web',
        ]);

        $keuangan = Role::firstOrCreate([
            'name' => 'Keuangan',
            'guard_name' => 'web',
        ]);

        $admin->syncPermissions(Permission::all());

        $tentor->syncPermissions([
            'dashboard.view',
            'class.view',
            'subject.view',
            'student.view',
            'parent.view',
            'question.view',
            'question.create',
            'question.update',
            'question.delete',
            'exam.view',
            'exam.create',
            'exam.update',
            'exam.delete',
            'exam.publish',
            'exam.close',
            'result.view',
            'exam.violation.view',
        ]);

        $siswa->syncPermissions([
            'dashboard.view',
            'exam.view',
            'exam.take',
            'result.view_own',
            'billing.view_own',
            'billing.payment.submit',
        ]);

        $orangTua->syncPermissions([
            'dashboard.view',
            'result.view_child',
            'billing.view_child',
            'billing.payment.submit',
        ]);

        $keuangan->syncPermissions([
            'dashboard.view',
            'billing.view',
            'billing.manage',
        ]);
    }
}
