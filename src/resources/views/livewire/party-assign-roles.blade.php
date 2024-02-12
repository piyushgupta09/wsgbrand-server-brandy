<div class="card border-0">

    <div class="card-header rounded-0 bg-dark py-2 d-flex justify-content-between align-items-center w-100">
        <button class="btn border-0 ps-0 text-white flex-fill text-start" type="button" 
            data-bs-toggle="collapse" data-bs-target="#modelAssignRoles" 
            aria-expanded="true" aria-controls="modelAssignRoles">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="bi bi-chevron-down"></i>
                    <strong class="mx-2 font-quick">Role</strong>
                    <span class="ls-1">(Request for {{ Str::afterLast($user->type, '-') }} role)</span>
                </div>
            </div>
        </button>
        <div class="">
            <button 
                class="btn border-0 text-white font-quick ls-1 fw-bold" 
                type="button" wire:click='reloadData'>
                <i class="bi bi-arrow-clockwise"></i>
            </button>
            <button 
                class="btn border-0 text-white font-quick ls-1 fw-bold" 
                type="button" wire:click="toggleForm">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>
    </div>

    <div x-data="{ show: @entangle('showForm') }" x-show="show" x-transition.delay.100ms class="card-body p-0 text-bg-secondary">

        <div class="row m-0 py-3">

            <div class="col-md-6">
                <div class="form-floating">
                    <select wire:model="role" class="form-control" id="role">
                        <option value="">Select Role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role['id'] }}">{{ $role['name'] }}</option>
                        @endforeach
                    </select>
                    <label for="role">Role</label>
                    @error('role') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="col-md-6">
                <div class="btn-group w-100 h-100">
                    <button wire:click="assignRole" class="btn btn-primary">Assign Role</button>
                    <button wire:click="removeAllRoles" class="btn btn-danger">Remove All</button>
                </div>
            </div>
            
        </div>

    </div>

    <div class="collapse show" id="modelAssignRoles">
        <ul class="list-group list-group-flush">
            @if (count($modelRoles))
                @foreach ($modelRoles as $role)
                    <div class="list-group-item rounded-0">
                        <div class="d-flex justify-content-between">
                            <div class="">
                                <span class="fw-bold">{{ $loop->iteration }}.</span>
                                <span class="fw-bold text-capitalize">{{ $role }}</span>
                            </div>
                            @if ($role != 'user')     
                                <button wire:click='removeRole("{{ $role }}")' class="btn btn-sm btn-outline-danger text-capitalize">
                                    <i class="bi bi-x-lg"></i>
                                    <span class="ps-2">Remove</span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            @else   
                <div class="list-group-item rounded-0">
                    No Roles assigned yet        
                </div>
            @endif
        </ul>
    </div>

    @include('panel::includes.livewire-alert')

</div>