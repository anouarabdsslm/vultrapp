<?php 
namespace App\Services;

use App\Services\VpsManagement;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class VultrSetvice implements VpsManagement
{
    protected $client;

    public function __construct(Client $client = null)
    {
        if($client) {
            $this->client = $client;
        } else {
            $this->client = $this->getClient();
        }
    }

    protected function getClient() {
        return new \GuzzleHttp\Client([
            'base_uri' => config("app.vultr_api"),
        ]);
    }

    public function getAllRegions() {
        $data = null;
        try {
            $response = $this->client->get('regions/list');
            $data = json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $data = null;
            Log::error("something goes wrong when fetch regions");
        }

        return $data;
    }

    public function checkRegionAvailability($dcId) {
        $data = null;
        try {
            $response = $this->client->get('regions/availability', ['query' => ['DCID' => $dcId]]);
            $data = json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $data = null;
            Log::error("something goes wrong when check region availability");
        }

        return $data;
    }

    public function getAllPlans($type = null) {
        $data = null;
        try {
            if(is_null($type)) {
                $response = $this->client->get('plans/list');

            } else {
                $response = $this->client->get('plans/list', ['query' => ['type' => $type]]);
            }
            $data = json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $data = null;
            Log::error("something goes wrong when fetching the plans");
        }

        return $data;
    }

    public function getAllOs() {
        $data = null;
        try {
            $response = $this->client->get('os/list');
            $data = json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $data = null;
            Log::error("something goes wrong when fetching OSs");
        }

        return $data;
    }

    public function getAllStartupscript() {
        $data = null;
        try {
            $response = $this->client->get('startupscript/list', ['headers' =>['API-Key' => config('app.vultr_api_key')]]);
            $data = json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $data = null;
            Log::error("Something goes wrong when fetching the startupscripts");
        }
        return $data;
    }

    public function createStartupscript($name, $script, $type = 'boot') {
        $allowed = ['boot', 'pxe'];
        if (!in_array($type, $allowed)) {
            throw new \Exception(
                sprintf('Script type must be one of %s.', implode(' or ', $allowed))
            );
        }

        $payload = [
            'name' => $name,
            'script' => $script,
            'type' => $type,
        ];

        $data = null;
        try {
            $response = $this->client->post('startupscript/create', ['headers' =>['API-Key' => config('app.vultr_api_key')], 'form_params' => $payload]);
            $data = json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $data = null;
            Log::error($e);
        }
        return $data;
    }    

    public function deleteStartupscript($scriptId) {
        $args = ['SCRIPTID' => (int) $scriptId];
        try {
            $response = $this->client->post('startupscript/destroy', ['headers' =>['API-Key' => config('app.vultr_api_key')], 'form_params' => $args]);
            return json_decode($response->getStatusCode(), true);
        } catch (\Exception $e) {
            Log::error($e);
        }
        return null;
    }

    public function createVps($config = []) {
        $regionId = (int) $config['DCID'];
        $planId   = (int) $config['VPSPLANID'];
        if(!$this->isAvailable($regionId, $planId)) {
            return null;
        }
        $data = null;
        try {
            $response = $this->client->post('server/create', ['headers' =>['API-Key' => config('app.vultr_api_key')], 'form_params' => $config]);
            $data = json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $data = null;
            Log::error($e);
        }
        return $data;
    }

    public function deleteVps($serverId) {
        $args = ['SUBID' => (int) $serverId];
        try {
            $response = $this->client->post('server/destroy', ['headers' =>['API-Key' => config('app.vultr_api_key')], 'form_params' => $args]);
            return json_decode($response->getStatusCode(), true);
       
        } catch (\Exception $e) {
            Log::error($e);
        }

        return null;
    }

    public function isAvailable($regionId, $planId)
    {
        $availability = $this->checkRegionAvailability((int) $regionId);
        if (!in_array((int) $planId, $availability)) {
            return false;
        } 
        return true;
    }
}