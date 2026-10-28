<?php

namespace Tests\Feature\Explorer;

use App\Modules\Courses\Enums\CourseStatus;
use App\Modules\Courses\Models\Course;
use Database\Seeders\DemoCourseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoCourseSeeder::class);
    }

    public function test_course_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('Golfclub Lippeauen');
        $this->get('/plaetze?q=Hamm')->assertOk()->assertSee('Golfclub Lippeauen');
        $this->get('/plaetze/'.DemoCourseSeeder::SLUG)->assertOk()->assertSee('Scorecard');
    }

    public function test_hole_page_contains_viewer_data(): void
    {
        $this->get('/plaetze/'.DemoCourseSeeder::SLUG.'/bahn/2?abschlag=white')
            ->assertOk()
            ->assertSee('explorer-data', false)
            ->assertSee('Eiche links');

        $this->get('/plaetze/'.DemoCourseSeeder::SLUG.'/bahn/99')->assertNotFound();
    }

    public function test_explorer_api_returns_linked_gps_nodes(): void
    {
        $hole = Course::where('slug', DemoCourseSeeder::SLUG)->firstOrFail()->holes()->where('number', 1)->firstOrFail();

        $json = $this->getJson("/api/v1/holes/{$hole->id}/explorer")->assertOk()->json();

        $this->assertNotEmpty($json['nodes']);
        $this->assertCount(3, $json['nodes'][0]['gps']);
        $this->assertNotEmpty($json['nodes'][0]['links']);
        $this->assertContains('green', array_column($json['nodes'][0]['markers'], 'id'));
    }

    public function test_unpublished_course_is_hidden(): void
    {
        Course::where('slug', DemoCourseSeeder::SLUG)->update(['status' => CourseStatus::Draft]);

        $this->get('/plaetze/'.DemoCourseSeeder::SLUG)->assertNotFound();
        $this->get('/plaetze/'.DemoCourseSeeder::SLUG.'/bahn/1')->assertNotFound();
    }
}
