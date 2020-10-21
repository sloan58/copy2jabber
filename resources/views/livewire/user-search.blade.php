<div class="container">
    <div class="row">
        <!-- Content Column -->
        <div class="col-lg-6 mb-4">
            <!-- Project Card Example -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Search for a User</h6>
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="search" class="mr-auto ml-md-3 my-2 my-md-0 mw-100 pb-2 navbar-search">
                        <div class="input-group">
                            <input wire:model="username" type="text" class="form-control bg-light" placeholder="Search...." aria-label="Search" aria-describedby="basic-addon2">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    @error('username') <span class="text-danger pl-4 pt-1">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <!-- Illustrations -->
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="font-weight-bold text-primary">
                        Results:
                        <div class="float-right">
                            <div wire:loading wire:target="search">
                                <div class="spinner-border text-primary float-right" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </h6>
                </div>
                <div class="card-body">

                    @if($queryError)
                        <span class="text-danger pt-1">{{ $queryError}}</span>
                    @endif

                    @foreach($userList as $user)
                        <a href="#" wire:click.prevent="getUserDevices('{{$user['userid']}}')">
                            <div class="card bg-primary text-white shadow mb-2">
                                <div class="card-body">
                                    {{ $user['firstname'] }}  {{ $user['lastname'] }} [{{ $user['userid']}}]
                                    <div class="text-white-50 small float-right">{{ $user['mailid'] }}</div>
                                </div>
                            </div>
                        </a>
                    @endforeach

                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div wire:loading wire:target="getUserDevices">
            <div class="spinner-border text-primary float-right" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <ul>
            @foreach($userDevices as $device)
                <li>{{ $device['name'] }}: {{ $device['description'] }}</li>
            @endforeach
        </ul>
    </div>
</div>
