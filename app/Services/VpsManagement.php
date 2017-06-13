<?php 
namespace App\Services;
interface VpsManagement {
    public function getAllRegions();
    public function checkRegionAvailability($dcId);
    public function getAllPlans();
    public function getAllOs();
    public function getAllStartupscript();
    public function createStartupscript($name, $script, $type);
    public function createVps();
    public function deleteVps($serverId);
}