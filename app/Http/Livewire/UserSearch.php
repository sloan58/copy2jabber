<?php

namespace App\Http\Livewire;

use SoapClient;
use App\Models\Ucm;
use Livewire\Component;

class UserSearch extends Component
{
    public $search = '';
    public $userList = [];
    public $deviceLines = [];
    public $selectedUser = [];
    public $selectedDevice = [];
    public $selectedDeviceDetails = [];
    public $primaryLine = [];
    public $nonJabberDevices = [];
    public $currentJabberDevices = [];
    public $jabberModelToAdd = '';
    public $stagedForProvisioning = false;
    public $ucmClusters = [];
    public $selectedCluster = [];
    public $newDeviceName = '';
    public $jabberDevicesList = [
        '562' => [
            'type' => 'Cisco Dual Mode for iPhone',
            'prefix' => 'TCT',
            'length' => '12'
        ],
        '575' => [
            'type' => 'Cisco Dual Mode for Android',
            'prefix' => 'BOT',
            'length' => '12'
        ],
        '503' => [
            'type' => 'Cisco Unified Client Services Framework',
            'prefix' => 'CSF',
            'length' => '15'
        ],
        '652' => [
            'type' => 'Cisco Jabber for Tablet',
            'prefix' => 'TAB',
            'length' => '12'
        ]
    ];

    /**
     * Livewire component was mounted
     */
    public function mount()
    {
        $this->ucmClusters = Ucm::all();
    }

    /**
     * UCM Cluster was selected
     *
     * @param $clusterId
     */
    public function clusterSelectionMade($clusterId)
    {
        $this->resetProps();
        $this->selectedCluster = Ucm::find($clusterId);
    }

    /**
     * Search for a UCM user by userid
     */
    public function search()
    {
        $data = $this->validate([
            'search' => 'required|min:1'
        ]);

        $this->resetProps();

        $search = strtolower($data['search']);

        try {
            $res = $this->getAxl()->executeSQLQuery([
                'sql' => "SELECT userid, firstname, lastname, mailid FROM enduser WHERE lower(userid) LIKE '%$search%'"
            ]);

            $data = isset($res->return->row) ? is_array($res->return->row) ? $res->return->row : [$res->return->row] : [];
            $this->userList = json_decode(json_encode($data), true);


        } catch(\SoapFault $e) {
            logger()->error('Uh oh....', [
                'message' => $e->getMessage()
            ]);

            flash($e->getMessage())->error();
        }
    }

    /**
     * Get devices associated to the selected user
     *
     * @param $selectedUser
     */
    public function getUserDevices($selectedUser)
    {

        $this->selectedUser = $this->userList[array_search($selectedUser, array_column($this->userList, 'userid'))];

        try {
            $res = $this->getAxl()->executeSQLQuery([
                'sql' => "SELECT d.name, d.description, t.enum, t.name model FROM device d JOIN enduserdevicemap eudm ON eudm.fkdevice = d.pkid JOIN enduser eu ON eudm.fkenduser = eu.pkid JOIN typemodel t ON t.enum = d.tkmodel WHERE eu.userid = '{$this->selectedUser['userid']}'"
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

            flash($e->getMessage())->error();
        }
    }

    /**
     * Source device was selected
     *
     * @param $device
     */
    public function deviceSelectionMade($device)
    {
        $this->selectedDevice = $device;

        try {
            $res = $this->getAxl()->getPhone([
                'name' => $this->selectedDevice
            ]);

            $this->selectedDeviceDetails = json_decode(json_encode($res->return->phone), true);

            $this->getDeviceLines();

        } catch(\SoapFault $e) {
            logger()->error('Uh oh....', [
                'message' => $e->getMessage()
            ]);
            flash($e->getMessage())->error();
        }

    }

    /**
     * Get the lines associated
     * with the selected device
     */
    public function getDeviceLines()
    {
        try {
            $res = $this->getAxl()->executeSQLQuery([
                'sql' => "SELECT n.pkid, n.dnorpattern, n.description, m.numplanindex, p.name as partition FROM numplan n JOIN devicenumplanmap m ON n.pkid = m.fknumplan JOIN device d ON d.pkid = m.fkdevice JOIN routepartition p ON n.fkroutepartition = p.pkid WHERE d.name = '$this->selectedDevice'"
            ]);

            $data = isset($res->return->row) ? is_array($res->return->row) ? $res->return->row : [$res->return->row] : [];
            $this->deviceLines = json_decode(json_encode($data), true);

        } catch(\SoapFault $e) {
            logger()->error('Uh oh....', [
                'message' => $e->getMessage()
            ]);
            flash($e->getMessage())->error();
        }
    }

    /**
     * Set the line to be used for
     * the new Jabber device
     *
     * @param $linePkid
     */
    public function setPrimaryLine($linePkid)
    {
        $this->primaryLine = $this->deviceLines[array_search($linePkid, array_column($this->deviceLines, 'pkid'))];
        sleep(1);
    }

    /**
     * Set the new Jabber device type
     *
     * @param $jabberEnum
     */
    public function selectJabberToProvision($jabberEnum)
    {
        $this->jabberModelToAdd = $this->jabberDevicesList[$jabberEnum];

        $devicePrefix = $this->jabberModelToAdd['prefix'];
        $deviceNameLength = $this->jabberModelToAdd['length'];
        $this->newDeviceName = substr(
            strtoupper($devicePrefix . $this->selectedUser['firstname'][0] . $this->selectedUser['lastname']),
            0,
            $deviceNameLength
        );

        $this->stagedForProvisioning = true;
        sleep(1);
    }

    /**
     * Cancel provisioning
     */
    public function cancelOperation()
    {
        $this->resetProps();
    }

    /**
     * Provision the new Jabber device
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function proceedToProvisioning()
    {
        $deviceType = $this->jabberModelToAdd['type'];

        $jabberLine = [];
        $lineIterator = isset($this->selectedDeviceDetails['lines']['line']) ? isset($this->selectedDeviceDetails['lines']['line'][0]) ? $this->selectedDeviceDetails['lines']['line'] : [$this->selectedDeviceDetails['lines']['line']] : [];

        foreach($lineIterator as $line) {
            if($line['dirn']['pattern'] === $this->primaryLine['dnorpattern']) {
                $jabberLine = $line;
                $jabberLine['index'] = '1';
                $jabberLine['maxNumCalls'] = '2';
                $jabberLine['busyTrigger'] = '2';
            }
        }

        $newPhone = [
            'name' => $this->newDeviceName,
            'product' => $deviceType,
            'class' => 'Phone',
            'protocol' => 'SIP',
            'protocolSide' => 'User',
            'commonPhoneConfigName' => [
                '_' => $this->selectedDeviceDetails['commonPhoneConfigName']['_']
            ],
            'locationName' => [
                '_' => $this->selectedDeviceDetails['locationName']['_']
            ],
            'useTrustedRelayPoint' => 'Default',
            'builtInBridgeStatus' => 'Default',
            'packetCaptureMode' => 'None',
            'certificateOperation' => 'No Pending Operation',
            'deviceMobilityMode' => 'Default',
            'devicePoolName' => [
                '_' => $this->selectedDeviceDetails['devicePoolName']['_']
            ],
            'description' => sprintf("%s %s %s", $this->selectedUser['firstname'], $this->selectedUser['lastname'], $deviceType),
            'callingSearchSpaceName' => [
                '_' => $this->selectedDeviceDetails['callingSearchSpaceName']['_']
            ],
            'mediaResourceListName' => [
                '_' => $this->selectedDeviceDetails['mediaResourceListName']['_']
            ],
            'networkHoldMohAudioSourceId' => $this->selectedDeviceDetails['networkHoldMohAudioSourceId'],
            'userHoldMohAudioSourceId' => $this->selectedDeviceDetails['userHoldMohAudioSourceId'],
            'sipProfileName' => [
                '_' => '' // Need to figure this one out
            ],
            'cgpnTransformationCssName' => [
                '_' => $this->selectedDeviceDetails['cgpnTransformationCssName']['_']
            ],
            'useDevicePoolCgpnTransformCss' => $this->selectedDeviceDetails['useDevicePoolCgpnTransformCss'],
            'lines' => [
                'line' => $jabberLine
            ]
        ];

        try {
            $res = $this->getAxl()->addPhone([
                'phone' => $newPhone
            ]);

            $url = sprintf('https://hq-cucm-pub.karmatek.io/ccmadmin/phoneEdit.do?key=%s', strtolower(str_replace(['{', '}'], '', $res->return)));

        } catch(\SoapFault $e) {
            logger()->error('Uh oh....', [
                'message' => $e->getMessage()
            ]);
            $this->resetProps();
            flash($e->getMessage())->error();
            return redirect()->back();
        }

        try {
            $res = $this->getAxl()->getUser([
                'userid' => $this->selectedUser['userid']
            ]);

            $associatedDeviceList = isset($res->return->user->associatedDevices->device) ? is_array($res->return->user->associatedDevices->device) ? $res->return->user->associatedDevices->device : [$res->return->user->associatedDevices->device] : [];
            $associatedDeviceList[] = $this->newDeviceName;

        } catch(\SoapFault $e) {
            logger()->error('Uh oh....', [
                'message' => $e->getMessage()
            ]);
            $this->resetProps();
            flash($e->getMessage())->error();
            return redirect()->back();
        }

        try {
            $this->getAxl()->updateUser([
                'userid' => $this->selectedUser['userid'],
                'associatedDevices' => [
                    'device' => $associatedDeviceList
                ]
            ]);

            $this->resetProps();

            flash("Device Provisioned!  You can visit the device page at <a href=\"$url\" target=\"_blank\" >by clicking here.</a>")->success();

        } catch(\SoapFault $e) {
            logger()->error('Uh oh....', [
                'message' => $e->getMessage()
            ]);
            $this->resetProps();
            flash($e->getMessage())->error();
        }
    }

    /**
     * Get the AXL API client
     *
     * @return SoapClient
     * @throws \SoapFault
     */
    private function getAxl()
    {
        return new SoapClient(storage_path('axl/AXLAPI.wsdl'),
            [
                'trace' => 1,
                'exceptions' => true,
                'location' => "https://{$this->selectedCluster->ip_address}:8443/axl/",
                'login' => $this->selectedCluster->username,
                'password' => $this->selectedCluster->password,
                'connection_timeout' => '10',
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
    }

    /**
     * Reset props to start over
     */
    private function resetProps()
    {
        $this->userList = [];
        $this->deviceLines = [];
        $this->selectedUser = '';
        $this->selectedDevice = '';
        $this->selectedDeviceDetails = [];
        $this->primaryLine = [];
        $this->nonJabberDevices = [];
        $this->currentJabberDevices = [];
        $this->jabberModelToAdd = '';
        $this->stagedForProvisioning = false;
        $this->newDeviceName = '';
    }

    /**
     * Render the Livewire view
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.user-search');
    }
}
