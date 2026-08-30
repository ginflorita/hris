<div class="modal fade" id="addEmploymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.employees.employments.store', $employee) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Record employment change</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Change type</label>
                            <select name="change_type" class="form-select" required>
                                @foreach (\App\Enums\EmploymentChangeType::cases() as $case)
                                    <option value="{{ $case->value }}">{{ ucwords(str_replace('_', ' ', $case->value)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Effective date</label>
                            <input type="date" name="effective_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">None</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Position</label>
                            <select name="position_id" class="form-select">
                                <option value="">None</option>
                                @foreach ($positions as $position)
                                    <option value="{{ $position->id }}">{{ $position->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="">None</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Location</label>
                            <select name="location_id" class="form-select">
                                <option value="">None</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Salary grade</label>
                        <select name="salary_grade_id" class="form-select">
                            <option value="">None</option>
                            @foreach ($salaryGrades as $salaryGrade)
                                <option value="{{ $salaryGrade->id }}">{{ $salaryGrade->name }} ({{ number_format($salaryGrade->min_salary, 0) }}–{{ number_format($salaryGrade->max_salary, 0) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Manager</label>
                        <select name="manager_id" class="form-select">
                            <option value="">None</option>
                            @foreach ($managers as $manager)
                                <option value="{{ $manager->id }}">{{ $manager->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-4 mb-3">
                            <label class="form-label">Employment type</label>
                            <select name="employment_type" class="form-select" required>
                                @foreach (\App\Enums\EmploymentType::cases() as $case)
                                    <option value="{{ $case->value }}">{{ ucwords(str_replace('_', ' ', $case->value)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label">Work arrangement</label>
                            <select name="work_arrangement" class="form-select">
                                <option value="">None</option>
                                @foreach (\App\Enums\WorkArrangement::cases() as $case)
                                    <option value="{{ $case->value }}">{{ ucfirst($case->value) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                @foreach (\App\Enums\EmploymentStatus::cases() as $case)
                                    <option value="{{ $case->value }}">{{ ucwords(str_replace('_', ' ', $case->value)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Basic salary</label>
                        <input type="number" step="0.01" min="0" name="basic_salary" class="form-control">
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Probation ends</label>
                            <input type="date" name="probation_ends_at" class="form-control">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Regularized on</label>
                            <input type="date" name="regularized_at" class="form-control">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Contract start</label>
                            <input type="date" name="contract_start_date" class="form-control">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Contract end</label>
                            <input type="date" name="contract_end_date" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Separation reason</label>
                        <input type="text" name="separation_reason" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" rows="2" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
