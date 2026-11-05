<?php

namespace Tests\Feature;

use App\Services\ChangelogService;
use Tests\TestCase;

class ChangelogTest extends TestCase
{
    public function test_changelog_service_reads_version_and_releases_from_markdown(): void
    {
        $version = ChangelogService::getCurrentVersion();
        $this->assertNotEmpty($version);
        $this->assertEquals('1.0.5', $version);

        $releases = ChangelogService::getReleases();
        $this->assertNotEmpty($releases);
        $this->assertEquals('1.0.5', $releases[0]['version']);
        $this->assertArrayHasKey('html', $releases[0]);
    }

    public function test_changelog_page_renders_successfully(): void
    {
        $response = $this->get('/changelog');

        $response->assertStatus(200);
        $response->assertSee('v1.0.5');
        $response->assertSee('System Evolution');
        $response->assertSee('CHANGELOG.md');
    }
}
