<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Services\ActaEquipoSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActaEquipoSearchServiceSqlTest extends TestCase
{
    use RefreshDatabase;

    public function test_relevance_sorting_uses_text_cast_for_uuid_in_lower_expression(): void
    {
        $service = app(ActaEquipoSearchService::class);
        $builder = Equipo::query();

        $method = new \ReflectionMethod($service, 'applyRelevanceSorting');
        $method->setAccessible(true);
        $method->invoke($service, $builder, '32dcff4a', true, true, true);

        $sql = $builder->toSql();

        $this->assertStringContainsString('lower((equipos.uuid)::text)', $sql);
        $this->assertStringNotContainsString('lower(equipos.uuid)', $sql);
    }

    public function test_search_conditions_use_ilike_with_text_cast_for_uuid(): void
    {
        $service = app(ActaEquipoSearchService::class);
        $builder = Equipo::query();

        $method = new \ReflectionMethod($service, 'applySearchConditions');
        $method->setAccessible(true);
        $method->invoke($service, $builder, '32dcff4a', true, true, true);

        $sql = $builder->toSql();

        $this->assertStringContainsString('(equipos.uuid)::text ilike ?', $sql);
        $this->assertStringNotContainsString('"equipos"."uuid" ilike ?', $sql);
    }
}
