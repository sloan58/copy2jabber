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
    public $jabberModelToAdd = [];
    public $stagedForProvisioning = false;
    public $ucmClusters = [];
    public $selectedCluster = [];
    public $newJabberDevices = [];
    public $serviceProfile = null;
    public $availableServiceProfiles = [];
    public $jabberDevicesList = [
        '562' => [
            'type' => 'Cisco Dual Mode for iPhone',
            'prefix' => 'TCT',
            'length' => '15'
        ],
        '575' => [
            'type' => 'Cisco Dual Mode for Android',
            'prefix' => 'BOT',
            'length' => '15'
        ],
        '503' => [
            'type' => 'Cisco Unified Client Services Framework',
            'prefix' => 'CSF',
            'length' => '18'
        ],
        '652' => [
            'type' => 'Cisco Jabber for Tablet',
            'prefix' => 'TAB',
            'length' => '15'
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
                'sql' => "SELECT u.userid, u.firstname, u.lastname, u.mailid, sp.name serviceprofile FROM enduser u LEFT JOIN ucserviceprofile sp ON u.fkucserviceprofile = sp.pkid WHERE lower(userid) LIKE '%$search%'"
            ]);

            $data = isset($res->return->row) ? is_array($res->return->row) ? $res->return->row : [$res->return->row] : [];
            $this->userList = json_decode(json_encode($data), true);


        } catch(\SoapFault $e) {
            logger()->error('UserSearch@search', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'stack' => $e
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
            logger()->error('UserSearch@getUserDevices', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'stack' => $e
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
            logger()->error('UserSearch@deviceSelectionMade', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'stack' => $e
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
                'sql' => "SELECT n.pkid, n.dnorpattern, n.description, m.numplanindex, p.name partition FROM numplan n JOIN devicenumplanmap m ON n.pkid = m.fknumplan JOIN device d ON d.pkid = m.fkdevice JOIN routepartition p ON n.fkroutepartition = p.pkid WHERE d.name = '$this->selectedDevice'"
            ]);

            $data = isset($res->return->row) ? is_array($res->return->row) ? $res->return->row : [$res->return->row] : [];
            $this->deviceLines = json_decode(json_encode($data), true);

        } catch(\SoapFault $e) {
            logger()->error('UserSearch@getDeviceLines', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'stack' => $e
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
        if(in_array($jabberEnum, array_keys($this->newJabberDevices))) {
            unset($this->newJabberDevices[$jabberEnum]);
        } else {
            $this->newJabberDevices[$jabberEnum] = substr(
                strtoupper($this->jabberDevicesList[$jabberEnum]['prefix'] . $this->selectedUser['firstname'][0] . $this->selectedUser['lastname']),
                0,
                $this->jabberDevicesList[$jabberEnum]['length']
            );
        }

        $this->checkServiceProfile();
    }

    /**
     * Figure out or provide selection for
     * the UC Service Profile
     */
    private function checkServiceProfile()
    {
        if(preg_match('/(.*)_DP/', $this->selectedDeviceDetails['devicePoolName']['_'], $matches)) {

            try {
                $res = $this->getAxl()->executeSQLQuery([
                    'sql' => "SELECT name FROM ucserviceprofile WHERE name LIKE '$matches[1]%'"
                ]);

                $data = isset($res->return->row) ? is_array($res->return->row) ? $res->return->row : [$res->return->row] : [];

                if(count($data) <= 1) {
                    $this->selectServiceProfile($data[0]->name ?? null);
                } else {
                    $this->availableServiceProfiles = array_map(function($profile) {
                        return $profile->name;
                    }, $data);
                }

            } catch(\SoapFault $e) {
                logger()->error('UserSearch@checkServiceProfile', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'stack' => $e
                ]);

                flash($e->getMessage())->error();
            }
        }
    }

    /**
     * Set the UC Service Profile
     *
     * @param $name
     */
    public function selectServiceProfile($name)
    {
        $this->serviceProfile = $name;
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
     * @return void
     */
    public function proceedToProvisioning()
    {

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

        array_walk($this->newJabberDevices, function($name, $enum) use ($jabberLine) {

            $deviceType = $this->jabberDevicesList[$enum]['type'];

            $newPhone = [
                'name' => $name,
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
                'description' => $this->selectedDeviceDetails['description'],
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
                ],
                'ownerUserName' => [
                    '_' => $this->selectedUser['userid']
                ]
            ];

            try {
                $this->getAxl()->addPhone([
                    'phone' => $newPhone
                ]);

            } catch(\SoapFault $e) {
                logger()->error('UserSearch@proceedToProvisioning:addPhone', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'stack' => $e
                ]);

                $this->resetProps();
                flash($e->getMessage())->error();
                return redirect()->back();
            }

            try {
                $res = $this->getAxl()->getUser([
                    'userid' => $this->selectedUser['userid'],
                    'returnedTags' => [
                        'associatedDevices' => ''
                    ]
                ]);

                $associatedDeviceList = isset($res->return->user->associatedDevices->device) ? is_array($res->return->user->associatedDevices->device) ? $res->return->user->associatedDevices->device : [$res->return->user->associatedDevices->device] : [];
                $associatedDeviceList[] = $name;
                $this->serviceProfile = $this->serviceProfile ?? $this->selectedUser['serviceprofile'];
                info('service profile', [is_array($this->serviceProfile)]);

            } catch(\SoapFault $e) {
                logger()->error('UserSearch@proceedToProvisioning:getUser', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'stack' => $e
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
                    ],
                    'serviceProfile' => [
                        '_' => $this->serviceProfile
                    ]
                ]);

            } catch(\SoapFault $e) {
                logger()->error('UserSearch@proceedToProvisioning:updateUser', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'stack' => $e
                ]);

                $this->resetProps();
                flash($e->getMessage())->error();
            }

            return true;
        });

        $this->resetProps();

        flash("Device(s) Provisioned!")->success();
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
                'cache_wsdl' => WSDL_CACHE_NONE,
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
        $this->jabberModelToAdd = [];
        $this->stagedForProvisioning = false;
        $this->newJabberDevices = [];
        $this->serviceProfile = null;
        $this->availableServiceProfiles = [];
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
