<?php

namespace App\Http\Livewire;

use SoapClient;
use Livewire\Component;

class UserSearch extends Component
{
    public $search = '';
    public $userList = [];
    public $deviceLines = [];
    public $selectedUser = '';
    public $selectedDevice = '';
    public $primaryLine = [];
    public $nonJabberDevices = [];
    public $currentJabberDevices = [];
    public $jabberModelToAdd = '';
    public $jabberDevicesList = [
        '562' => 'Cisco Dual Mode for iPhone',
        '575' => 'Cisco Dual Mode for Android',
        '503' => 'Cisco Unified Client Services Framework',
        '652' => 'Cisco Jabber for Tablet'
    ];
    public $stagedForProvisioning = false;


    public function search()
    {
        $data = $this->validate([
            'search' => 'required|min:1'
        ]);

        $this->resetProps();

        $axl = new SoapClient(storage_path('axl/AXLAPI.wsdl'),
            [
                'trace'=>1,
                'exceptions'=>true,
                'location'=>"https://10.175.200.10:8443/axl/",
                'login'=>'Administrator',
                'password'=>'A$h8urn!',
                'stream_context' => stream_context_create([
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'ciphers' => 'SHA1'
                        ]
                    ]
                ),
            ]
        );

        $search = strtolower($data['search']);

        try {
            $res = $axl->executeSQLQuery([
                'sql' => "SELECT userid, firstname, lastname, mailid FROM enduser WHERE lower(userid) LIKE '%$search%'"
            ]);

            $data = isset($res->return->row) ? is_array($res->return->row) ? $res->return->row : [$res->return->row] : [];
            $this->userList = json_decode(json_encode($data), true);

        } catch(\SoapFault $e) {
            logger()->error('Uh oh....', [
                'message' => $e->getMessage()
            ]);

            request()->session()->flash('status', $e->getMessage());
        }
    }

    public function getUserDevices($user)
    {
        $this->selectedUser = $user;

        $axl = new SoapClient(storage_path('axl/AXLAPI.wsdl'),
            [
                'trace'=>1,
                'exceptions'=>true,
                'location'=>"https://10.175.200.10:8443/axl/",
                'login'=>'Administrator',
                'password'=>'A$h8urn!',
                'stream_context' => stream_context_create([
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'ciphers' => 'SHA1'
                        ]
                    ]
                ),
            ]
        );

        try {
            $res = $axl->executeSQLQuery([
                'sql' => "SELECT d.name, d.description, t.enum, t.name model FROM device d JOIN enduserdevicemap eudm ON eudm.fkdevice = d.pkid JOIN enduser eu ON eudm.fkenduser = eu.pkid JOIN typemodel t ON t.enum = d.tkmodel WHERE eu.userid = '{$this->selectedUser}'"
            ]);

            $data = isset($res->return->row) ? is_array($res->return->row) ? $res->return->row : [$res->return->row] : [];
            $data = json_decode(json_encode($data), true);

            $jabberList = array_keys($this->jabberDevicesList);

            $this->currentJabberDevices = array_values(array_filter(
                array_map(function ($device) use ($jabberList) {
                    return in_array($device['enum'], $jabberList) ? $device : '';
                }, $data)
            ));

            $this->nonJabberDevices = array_values(array_filter(
                array_map(function ($device) use ($jabberList) {
                    return !in_array($device['enum'], $jabberList) ? $device : '';
                }, $data)
            ));

        } catch(\SoapFault $e) {
            logger()->error('Uh oh....', [
                'message' => $e->getMessage()
            ]);

            request()->session()->flash('status', $e->getMessage());
        }
    }

    public function getDeviceLines($device)
    {
        $this->selectedDevice = $device;

        $axl = new SoapClient(storage_path('axl/AXLAPI.wsdl'),
            [
                'trace'=>1,
                'exceptions'=>true,
                'location'=>"https://10.175.200.10:8443/axl/",
                'login'=>'Administrator',
                'password'=>'A$h8urn!',
                'stream_context' => stream_context_create([
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'ciphers' => 'SHA1'
                        ]
                    ]
                ),
            ]
        );

        try {
            $res = $axl->executeSQLQuery([
                'sql' => "SELECT n.pkid, n.dnorpattern, n.description, m.numplanindex, p.name as partition FROM numplan n JOIN devicenumplanmap m ON n.pkid = m.fknumplan JOIN device d ON d.pkid = m.fkdevice JOIN routepartition p ON n.fkroutepartition = p.pkid WHERE d.name = '$device'"
            ]);

            $data = isset($res->return->row) ? is_array($res->return->row) ? $res->return->row : [$res->return->row] : [];
            $this->deviceLines = json_decode(json_encode($data), true);

        } catch(\SoapFault $e) {
            logger()->error('Uh oh....', [
                'message' => $e->getMessage()
            ]);
            request()->session()->flash('status', $e->getMessage());
        }
    }

    public function setPrimaryLine($linePkid)
    {
        $this->primaryLine = $this->deviceLines[array_search($linePkid, array_column($this->deviceLines, 'pkid'))];
        sleep(1);
    }

    public function selectJabberToProvision($jabberModelName)
    {
        $this->jabberModelToAdd = $jabberModelName;
        $this->stagedForProvisioning = true;
        sleep(1);
    }

    public function cancelOperation()
    {
        $this->resetProps();
    }

    private function resetProps()
    {
        $this->userList = [];
        $this->deviceLines = [];
        $this->selectedUser = '';
        $this->selectedDevice = '';
        $this->primaryLine = [];
        $this->nonJabberDevices = [];
        $this->currentJabberDevices = [];
        $this->jabberModelToAdd = '';
        $this->stagedForProvisioning = false;
    }

    public function render()
    {
        return view('livewire.user-search');
    }
}
