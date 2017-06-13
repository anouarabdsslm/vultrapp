<?php 

use App\Services\VultrSetvice;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;
class VultrSetviceTest extends TestCase
{
    public function test_it_should_return_all_regions()
    {

        $expectedResponse = [
            "1" => [
                "DCID"=> "1",
                "name"=> "New Jersey",
                "country"=> "US",
            ],
            "2" => [
                "DCID"=> "2",
                "name"=> "Chicago",
                "country"=> "US",
            ]
        ];
        $vultr = new VultrSetvice($this->getMockWithResponse($expectedResponse));
        $regions = $vultr->getAllRegions();
        $this->assertEquals("New Jersey", $regions[1]['name']);
        $this->assertEquals("Chicago", $regions[2]['name']);
    } 

    public function test_it_should_check_region_availability()
    {

        $expectedResponse = [201,202,203];
        $vultr = new VultrSetvice($this->getMockWithResponse($expectedResponse));
        $regionAvailability = $vultr->checkRegionAvailability(1);
        $this->assertEquals(203, $regionAvailability[2]);
        $this->assertEquals(202, $regionAvailability[1]);
        $this->assertEquals(201, $regionAvailability[0]);

    }
    public function test_it_should_return_all_plans()
    {

        $expectedResponse = [
            "1" => [
                "VPSPLANID"=> "1",
                "name"=> "Starter",
                "vcpu_count"=> "1",
                "available_locations" => [1,2,4]
            ],
            "2" => [
                "VPSPLANID"=> "2",
                "name"=> "Basic",
                "vcpu_count"=> "1",
                "available_locations" => []
            ]
        ];
        $vultr = new VultrSetvice($this->getMockWithResponse($expectedResponse));
        $plans = $vultr->getAllPlans();
        $this->assertEquals("Starter", $plans[1]['name']);
        $this->assertEquals("Basic", $plans[2]['name']);
    }     

    public function test_it_should_return_all_os()
    {

        $expectedResponse = [
            "127" => [
                "OSID" => 127,
                "name" => "CentOS 6 x64",
                "arch" => "x64"
            ],
            "161" => [
                "OSID" => 161,
                "name" => "Ubuntu 14.04 i386",
                "arch" => "i386"
            ]
        ];
        $vultr = new VultrSetvice($this->getMockWithResponse($expectedResponse));
        $os = $vultr->getAllOs();
        $this->assertEquals("CentOS 6 x64", $os[127]['name']);
        $this->assertEquals("Ubuntu 14.04 i386", $os[161]['name']);
    }    

    public function test_it_should_return_all_startupscripts()
    {

        $expectedResponse = [
            "14107" => [
                "SCRIPTID" => "14107",
                "date_created" => "2016-01-21 20:31:58",
                "date_modified" => "2016-01-21 20:31:58",
                "name" => "Salt Master",
                "script" => "
                  #!/bin/sh\n
                  \n
                  curl -L https://bootstrap.saltstack.com -o install_salt.sh\n
                  sudo sh install_salt.sh -P -M
                "
            ],
            "54749" => [
                "SCRIPTID" => "54749",
                "date_created" => "2017-04-12 15:52:25",
                "date_modified" => "2017-04-12 15:52:25",
                "name" => "rancher-cluster-1-2",
                "script" => "
                  #!/bin/sh\n
                  \n
                  curl -L https://bootstrap.saltstack.com -o install_salt.sh\n
                  sudo sh install_salt.sh -P -M
                "
            ]
        ];
        $vultr = new VultrSetvice($this->getMockWithResponse($expectedResponse));
        $startupscripts = $vultr->getAllStartupscript();
        $this->assertEquals("Salt Master", $startupscripts[14107]['name']);
        $this->assertEquals("rancher-cluster-1-2", $startupscripts[54749]['name']);
    }    

    // integation tests
    public function test_it_should_create_and_destroy_startupscript()
    {
        $vultr = new VultrSetvice();
        $result = $vultr->createStartupscript("demo", "#!/bin/bash\necho hello world > /root/hello");
        $this->assertNotNull($result['SCRIPTID']);
        $statusCode = $vultr->deleteStartupscript($result['SCRIPTID']);
        $this->assertEquals(200, $statusCode);
    }

    public function getMockWithResponse(array $response) {

        $json = json_encode($response);

        $mock = new MockHandler([
            new Response(200, [], $json),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        return $client;
    }
}