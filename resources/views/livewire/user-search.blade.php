<div class="row">
    <div class="col-md-12">
        @include('flash::message')
    </div>
    <div class="container" xmlns:wire="http://www.w3.org/1999/xhtml">
            <div class="row">
                <div class="card shadow col-md-12 mb-4">
                    <div class="card-header">
                        <h6 class="font-weight-bold text-primary">
                            Select a UCM Cluster
                        </h6>
                    </div>
                    <div class="card-body">
                        @if(!empty($ucmClusters))
                            <div class="text-center">
                                @foreach($ucmClusters as $cluster)
                                    <button
                                        type="button"
                                        class="btn btn-{{ isset($selectedCluster['name']) && $selectedCluster['name'] === $cluster->name ? 'success' : 'primary' }}"
                                        wire:click.prevent="clusterSelectionMade('{{ $cluster->id }}')"
                                    >
                                        {{ $cluster->name }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Search for a User</h6>
                    </div>
                    <div class="card-body">
                        <form wire:submit.prevent="search" class="mr-auto ml-md-3 my-2 my-md-0 mw-100 pb-2 navbar-search">
                            <div class="input-group">
                                <input
                                    wire:model="search"
                                    type="text"
                                    class="form-control bg-light"
                                    placeholder="{{ empty($search) ? 'Search....' : $search }}"
                                    aria-label="Search"
                                    aria-describedby="basic-addon2"
                                    {{isset($selectedCluster['name']) ? '' : 'readonly'}}
                                >
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
                            Select a user to provision
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
                                <div class="card bg-{{ (isset($selectedUser['userid']) && $selectedUser['userid'] == $user['userid']) ? 'success': 'primary'  }} text-white shadow mb-2">
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
                        Select a device to copy settings from
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
                        Select a primary line for the new Jabber device
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
                        Select an available Jabber device type to provision
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
                                    <div class="card bg-{{ in_array($enum, array_keys($newJabberDevices)) ? 'success': 'primary'}} text-white shadow mb-2">
                                        <div class="card-body">
                                            {{ $details['type'] }}
                                            <div class="text-white-50 small float-right">
                                                {{ in_array($enum, array_keys($newJabberDevices)) ? '' : 'Click to Add' }}
                                            </div>
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


        @if(count($availableServiceProfiles))
        <div class="row">
            <div class="card shadow col-md-12 mb-4">
                <div class="card-header">
                    <h6 class="font-weight-bold text-primary">
                        Select a Service Profile for the User
                        <div class="float-right">
                            <div wire:loading.delay wire:target="selectJabberToProvision">
                                <div class="spinner-border text-primary float-right" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </h6>
                </div>
                <div class="card-body">
                @foreach($availableServiceProfiles as $sp)
                    <a href="#" wire:click.prevent="selectServiceProfile('{{ $sp }}', true)">
                        <div class="card bg-{{ ($serviceProfile === $sp) ? 'success': 'primary'}} text-white shadow mb-2">
                            <div class="card-body">
                                {{ $sp }}
                            </div>
                        </div>
                    </a>
                @endforeach
                </div>
            </div>
        </div>
        @endif

        <div class="row">
            <div class="card shadow col-md-12 mb-4">
                <div class="card-header">
                    <div class="font-weight-bold text-danger">
                        Provisioning Confirmation
                        <div class="float-right">
                            <div wire:loading.delay wire:target={{count($availableServiceProfiles) ? 'selectServiceProfile' : 'selectJabberToProvision'}}>
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
                    </div>
                </div>
                @if($stagedForProvisioning)
                    <div class="col-md-12 pt-2">
                        <b>Source Device:</b> {{ $selectedDeviceDetails['name'] }} ({{ $selectedDeviceDetails['description'] }})  @if($isHlog) <small class="text-danger">*HLOG Device</small> @endif
                    </div>
                    <div class="card-body">
                        @foreach($newJabberDevices as $enum => $deviceName)
                            <ul>
                                <li><b>New Device Name:</b> {{ $deviceName }}</li>
                                <li><b>New Device Type:</b> {{ $jabberDevicesList[$enum]['type'] }}</li>
                                <li><b>New Device Primary Line:</b> {{ $primaryLine['dnorpattern'] }} in {{ $primaryLine['partition'] }}</li>
                                <li><b>New Device User Association:</b> {{ $selectedUser['userid'] }}</li>
                                <li><b>User Service Profile:</b> {{ $serviceProfile ?? $selectedUser['serviceprofile'] }}</li>
                                <li><b>Jabber Configuration File:</b> {{ $isHlog ? 'HLOG XML' : 'Standard XML' }}</li>
                            </ul>
                            @if(!$loop->last)<hr>@endif
                        @endforeach

                    </div>
                    <div class="card-footer text-muted float-right">
                        <button wire:click.prevent="proceedToProvisioning" type="button" class="btn btn-success">Accept</button>
                        <button wire:click.prevent="cancelOperation" type="button" class="btn btn-danger">Cancel</button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

