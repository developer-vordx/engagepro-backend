<?php

namespace Database\Seeders;

use App\Models\Broker;
use App\Models\BrokerCompany;
use App\Models\BrokerLicense;
use App\Models\Driver;
use App\Models\DriverEmployment;
use App\Models\DriverEquipment;
use App\Models\DriverLicense;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Factories\DriverFactory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $user =  User::factory()->create();
    }
}
