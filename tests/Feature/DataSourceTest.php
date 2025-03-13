<?php

namespace Tests\Feature;

use App\Models\DataSource;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DataSourceTest extends TestCase
{
    public function test_unauthenticated_user_cannot_access_crud_route()
    {

        $this->getJson(route('Get Data Sources'))
            ->assertUnauthorized();

        $this->postJson(route('Create Data Source'))
            ->assertUnauthorized();

        $this->getJson(route('Get Data Source', ['data_source' => 1]))
            ->assertUnauthorized();

        $this->putJson(route('Update Data Source', ['data_source' => 1]))
            ->assertUnauthorized();

        $this->deleteJson(route('Delete Data Source', ['data_source' => 1]))
            ->assertUnauthorized();
    }

    public function test_can_create_data_source()
    {
        Sanctum::actingAs(User::factory()->create());

        $countBefore = count(DataSource::all());

        $this->postJson(route('Create Data Source'), [
            'name' => 'Test Data Source',
            'identifier' => 'test-data-source',
            'uri' => 'https://example.com/data-source',
            'is_active' => true,
            'sync_interval' => 60,
            'filters' => [
                'page' => 1,
                'limit' => 10,
            ],
        ])->assertCreated()
            ->assertJson([
                'name' => 'Test Data Source',
                'identifier' => 'test-data-source',
                'uri' => 'https://example.com/data-source',
                'is_active' => true,
                'sync_interval' => 60,
                'filters' => ['filter1', 'filter2'],
            ]);

        $this->assertEquals($countBefore + 1, count(DataSource::all()));
    }

    public function test_it_can_get_all_data_sources()
    {
        Sanctum::actingAs(User::factory()->create());

        DataSource::factory()->count(5)->create();

        $this->getJson(route('Get Data Sources'))
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'identifier',
                        'uri',
                        'is_active',
                        'sync_interval',
                        'filters',
                    ],
                ],
            ]);
    }

    public function test_it_can_search_data_source()
    {
        Sanctum::actingAs(User::factory()->create());

        DataSource::factory()->count(5)->create();

        DataSource::factory()->create([
            'name' => 'Test Data Source',
            'identifier' => 'test-data-source',
            'uri' => 'https://example.com/data-source',
            'is_active' => true,
            'sync_interval' => 60,
            'filters' => ['filter1', 'filter2'],
        ]);

        $this->getJson(route('Get Data Sources', ['search' => 'test Data']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'identifier',
                        'uri',
                        'is_active',
                        'sync_interval',
                        'filters',
                    ],
                ],
            ]);
    }

    public function test_it_can_update_data_source()
    {
        Sanctum::actingAs(User::factory()->create());

        $dataSource = DataSource::factory()->create();

        $this->putJson(route('Update Data Source', ['data_source' => $dataSource->id]), [
            'name' => 'Updated Data Source',
            'uri' => 'https://example.com/updated-data-source',
            'is_active' => false,
            'sync_interval' => 120,
            'filters' => ['filter3', 'filter4'],
        ])->assertOk()
            ->assertJson([
                'name' => 'Updated Data Source',
                'uri' => 'https://example.com/updated-data-source',
                'is_active' => false,
                'sync_interval' => 120,
                'filters' => ['filter3', 'filter4'],
            ]);

        $this->assertEquals('Updated Data Source', $dataSource->refresh()->name);
    }

    public function test_it_can_delete_data_source()
    {
        Sanctum::actingAs(User::factory()->create());

        $dataSource = DataSource::factory()->create();

        $countBefore = count(DataSource::all());

        $this->deleteJson(route('Delete Data Source', ['data_source' => $dataSource->id]))
            ->assertNoContent();

        $this->assertEquals($countBefore - 1, count(DataSource::all()));

        $this->assertNull(DataSource::find($dataSource->id));
    }

    public function test_it_can_get_data_source()
    {
        Sanctum::actingAs(User::factory()->create());

        $dataSource = DataSource::factory()->create();

        $this->getJson(route('Get Data Source', ['data_source' => $dataSource->id]))
            ->assertOk()
            ->assertJson([
                'name' => $dataSource->name,
                'identifier' => $dataSource->identifier,
                'uri' => $dataSource->uri,
                'sync_interval' => $dataSource->sync_interval,
                'filters' => $dataSource->filters,
            ]);
    }
}
