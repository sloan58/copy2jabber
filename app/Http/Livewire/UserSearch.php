<?php

namespace App\Http\Livewire;

use SoapClient;
use Livewire\Component;

class UserSearch extends Component
{
    public $username = '';
    public $userList = [];
    public $queryError = false;
    public $selectedUser = '';
    public $userDevices = [];


    public function search()
    {
        $data = $this->validate([
            'username' => 'required|min:1'
        ]);

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

        $search = strtolower($data['username']);

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

            $this->queryError = $e->getMessage();
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
                'sql' => "SELECT d.name, d.description FROM device d JOIN enduserdevicemap eudm ON eudm.fkdevice = d.pkid JOIN enduser eu ON eudm.fkenduser = eu.pkid WHERE eu.userid = '{$user}'"
            ]);

            $data = isset($res->return->row) ? is_array($res->return->row) ? $res->return->row : [$res->return->row] : [];
            $this->userDevices = json_decode(json_encode($data), true);

        } catch(\SoapFault $e) {
            logger()->error('Uh oh....', [
                'message' => $e->getMessage()
            ]);

            $this->queryError = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.user-search');
    }
}
