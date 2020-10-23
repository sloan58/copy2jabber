<div class="container" xmlns:wire="http://www.w3.org/1999/xhtml">
    @if (session('status'))
        <div class="alert alert-{{ session('alert-class') ?? 'danger' }} border-left-{{ session('alert-class') ?? 'danger' }}" role="alert">
                {!! session('status') !!}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Search for a User</h6>
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="search" class="mr-auto ml-md-3 my-2 my-md-0 mw-100 pb-2 navbar-search">
                        <div class="input-group">
                            <input wire:model="search" type="text" class="form-control bg-light" placeholder="{{ empty($search) ? 'Search....' : $search }}" aria-label="Search" aria-describedby="basic-addon2">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    @error('search') <span class="text-danger pl-4 pt-1">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="font-weight-bold text-primary">
                        Select a User to Provision:
                        <div class="float-right">
                            <div wire:loading.delay wire:target="search">
                                <div class="spinner-border text-primary float-right" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </h6>
                </div>
                <div class="card-body">

                    @foreach($userList as $user)
                        <a href="#" wire:click.prevent="getUserDevices('{{ $user['userid'] }}')">
                            <div class="card bg-{{ $selectedUser == $user['userid'] ? 'success': 'primary'  }} text-white shadow mb-2">
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
        <div class="card shadow col-md-12 mb-4">
            <div class="card-header">
                <h6 class="font-weight-bold text-primary">
                    Step 1: Select a Device to Copy
                    <div class="float-right">
                        <div wire:loading.delay wire:target="getUserDevices">
                            <div class="spinner-border text-primary float-right" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                    </div>
                </h6>
            </div>
            <div class="card-body">
                @if(!empty($nonJabberDevices))
                    @foreach($nonJabberDevices as $device)
                        <a href="#" wire:click.prevent="deviceSelectionMade('{{ $device['name'] }}')">
                            <div class="card bg-{{ $selectedDevice == $device['name'] ? 'success': 'primary'  }} text-white shadow mb-2">
                                <div class="card-body">
                                    {{ $device['model'] }}
                                    <div class="text-white-50 small float-right">{{ $device['name'] }} | {{ $device['description'] }}</div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="card shadow col-md-12 mb-4">
            <div class="card-header">
                <h6 class="font-weight-bold text-primary">
                    Step 2: Select a Primary Line:
                    <div class="float-right">
                        <div wire:loading.delay wire:target="deviceSelectionMade">
                            <div class="spinner-border text-primary float-right" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                    </div>
                </h6>
            </div>
            <div class="card-body">
                @if(!empty($deviceLines))
                    @foreach($deviceLines as $line)
                        <a href="#" wire:click.prevent="setPrimaryLine('{{$line['pkid']}}')">
                            <div class="card bg-{{ isset($primaryLine['pkid']) &&  $primaryLine['pkid'] == $line['pkid'] ? 'success': 'primary'  }} text-white shadow mb-2">
                                <div class="card-body">
                                    {{ $line['numplanindex'] }}: {{ $line['dnorpattern'] }} in {{ $line['partition'] }}
                                    <div class="text-white-50 small float-right">{{ $line['description'] }}</div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="card shadow col-md-12 mb-4">
            <div class="card-header">
                <h6 class="font-weight-bold text-primary">
                    Step 3: Select a Jabber device type to Provision:
                    <div class="float-right">
                        <div wire:loading.delay wire:target="setPrimaryLine">
                            <div class="spinner-border text-primary float-right" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                    </div>
                </h6>
            </div>
            <div class="card-body">
                @if(!empty($primaryLine))
                    @foreach($jabberDevicesList as $enum => $details)
                        @if(!in_array($enum, array_column($currentJabberDevices, 'enum')))
                            <a href="#" wire:click.prevent="selectJabberToProvision('{{ $enum }}')">
                                <div class="card bg-{{ (isset($jabberModelToAdd['type']) && $details['type'] === $jabberModelToAdd['type']) ? 'success': 'primary'}} text-white shadow mb-2">
                                    <div class="card-body">
                                        {{ $details['type'] }}
                                        <div class="text-white-50 small float-right">{{ (isset($jabberModelToAdd['type']) && $details['type'] === $jabberModelToAdd['type']) ? '' : 'Click to Add' }}</div>
                                    </div>
                                </div>
                            </a>
                        @else
                            <div class="card bg-secondary text-white shadow mb-2">
                                <div class="card-body">
                                    {{ $details['type'] }}
                                    <div class="text-white-50 small float-right">Configured</div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="card shadow col-md-12 mb-4">
            <div class="card-header">
                <h6 class="font-weight-bold text-danger">
                    Provisioning Confirmation:
                    <div class="float-right">
                        <div wire:loading.delay wire:target="selectJabberToProvision">
                            <div class="spinner-border text-primary float-right" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                        <div wire:loading.delay wire:target="proceedToProvisioning">
                            <div class="spinner-border text-primary float-right" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                    </div>
                </h6>
            </div>
            @if($stagedForProvisioning)
            <div class="card-body">
                <h5>
                    The following operation will take the settings from <b>{{ $selectedDevice }}</b> and copy those into a new
                    Jabber device, <b>{{ $jabberModelToAdd['type'] }}</b>.  It will use the primary line <b>{{ $primaryLine['dnorpattern'] }}</b>
                    in the <b>{{ $primaryLine['partition'] }}</b> and associate the device with the user <b>{{ $selectedUser['userid'] }}</b>.
                    <br><br>
                    Please select 'Accept' to provision the device or 'Cancel' to clear this operation.
                </h5>
            </div>
            <div class="card-footer text-muted float-right">
                <button wire:click.prevent="proceedToProvisioning" type="button" class="btn btn-success">Accept</button>
                <button wire:click.prevent="cancelOperation" type="button" class="btn btn-danger">Cancel</button>
            </div>
            @endif
        </div>
    </div>

</div>
