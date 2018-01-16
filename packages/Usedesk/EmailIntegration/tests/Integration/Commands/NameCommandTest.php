<?php

namespace Usedesk\EmailIntegration\Test\Integration\Commands;

use Artisan;
use Carbon\Carbon;
use Usedesk\EmailIntegration\Models\Model;
use Usedesk\EmailIntegration\Test\Integration\TestCase;

class NameCommandTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        
        Carbon::setTestNow(Carbon::create(2016, 1, 1, 00, 00, 00));

        $this->app['config']->set('usedesk.delete_records_older_than_days', 31);
    }
    /** @test */
    public function it_can_clean_the_activity_log()
    {
        collect(range(1, 60))->each(function (int $index) 
        {
            Model::create([
                'description' => "item {$index}",
                'created_at'  => Carbon::now()->subDays($index)->startOfDay(),
            ]);
        });

        $this->assertCount(60, Model::all());

        Artisan::call('usedesk:clean');

        $this->assertCount(31, Model::all());

        $cutOffDate = Carbon::now()->subDays(31)->format('Y-m-d H:i:s');

        $this->assertCount(0, Model::where('created_at', '<', $cutOffDate)->get());
    }
}