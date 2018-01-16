<?php

namespace Usedesk\EmailIntegration\Test\Integration\Events;

use Illuminate\Support\Facades\Event;

use Usedesk\EmailIntegration\Events\IntegrationHasFailed;
use Usedesk\EmailIntegration\Test\Integration\TestCase;

class IntegrationHasFailedTest extends TestCase
{
    public function setUp()
    {
        parent::setup();
        Event::fake();
    }
    /** @test */
    public function it_will_fire_the_media_added_event()
    {
        $this->testModel->addMedia($this->getTestJpg())->toMediaCollection();
        Event::assertDispatched(MediaHasBeenAdded::class);
    }

    /** @test */
    public function it_will_fire_an_event_when_a_integration_has_failed()
    {
        $this->app['config']->set('backup.backup.destination.disks', ['ftp']);
        $this->expectsEvents(IntegrationHasFailed::class);
        $this->artisan('backup:run', ['--only-files' => true]);
    }
    /** @test */
    public function it_will_fire_a_backup_failed_event_when_there_are_no_files_or_databases_to_be_backed_up()
    {
        $this->app['config']->set('backup.backup.source.files.include', []);
        $this->app['config']->set('backup.backup.source.databases', []);
        $this->expectsEvents(IntegrationHasFailed::class);
        $this->artisan('backup:run');
    }
}