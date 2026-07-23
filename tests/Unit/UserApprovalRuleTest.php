<?php

namespace Tests\Unit;

use App\Models\Position;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserApprovalRuleTest extends TestCase
{
    public function test_manager_can_approve_employee_only_from_same_company_department_and_higher_level(): void
    {
        $managerPosition = new Position(['name' => 'Manager', 'level' => 1]);
        $staffPosition = new Position(['name' => 'Staff', 'level' => 2]);

        $manager = new User(['company_id' => 10, 'department_id' => 20]);
        $manager->setRelation('position', $managerPosition);

        $employeeSameDept = new User(['company_id' => 10, 'department_id' => 20]);
        $employeeSameDept->setRelation('position', $staffPosition);

        $employeeDifferentDept = new User(['company_id' => 10, 'department_id' => 21]);
        $employeeDifferentDept->setRelation('position', $staffPosition);

        $this->assertTrue($manager->canApproveLeaveFor($employeeSameDept));
        $this->assertFalse($manager->canApproveLeaveFor($employeeDifferentDept));
    }

    public function test_manager_cannot_approve_employee_from_different_company(): void
    {
        $managerPosition = new Position(['name' => 'Manager', 'level' => 1]);
        $staffPosition = new Position(['name' => 'Staff', 'level' => 2]);

        $manager = new User(['company_id' => 10, 'department_id' => 20]);
        $manager->setRelation('position', $managerPosition);

        $employee = new User(['company_id' => 11, 'department_id' => 20]);
        $employee->setRelation('position', $staffPosition);

        $this->assertFalse($manager->canApproveLeaveFor($employee));
    }
}
