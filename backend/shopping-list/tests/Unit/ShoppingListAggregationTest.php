<?php

namespace App\Tests\Unit;

use App\Shopping\Database;
use App\Shopping\ShoppingListRepository;
use PHPUnit\Framework\TestCase;

final class ShoppingListAggregationTest extends TestCase
{
    private function repo(): ShoppingListRepository
    {
        // aggregateSnapshot does not open a connection; dummy DSN is enough.
        return new ShoppingListRepository(new Database('pgsql:host=127.0.0.1;dbname=shopping', 'shopping', 'shopping'));
    }

    public function testEmptyAssignmentsYieldEmptySnapshot(): void
    {
        self::assertSame([], $this->repo()->aggregateSnapshot([], []));
    }

    public function testSumsSameIngredientAndUnit(): void
    {
        $r1 = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa1';
        $r2 = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa2';
        $lines = $this->repo()->aggregateSnapshot(
            [
                ['date' => '2026-03-02', 'slotCode' => 'DINNER', 'recipeId' => $r1],
                ['date' => '2026-03-03', 'slotCode' => 'DINNER', 'recipeId' => $r2],
            ],
            [
                $r1 => ['ingredients' => [['name' => 'Мука', 'quantity' => 200, 'unit' => 'г', 'productCategory' => 'бакалея']]],
                $r2 => ['ingredients' => [['name' => 'мука', 'quantity' => 100, 'unit' => 'г', 'productCategory' => 'бакалея']]],
            ]
        );

        self::assertCount(1, $lines);
        self::assertSame('Мука', $lines[0]['displayName']);
        self::assertSame(300.0, $lines[0]['quantity']);
        self::assertSame('г', $lines[0]['unit']);
        self::assertNull($lines[0]['mergeNote']);
        self::assertSame([$r1, $r2], $lines[0]['sourceRecipeIds']);
    }

    public function testDifferentUnitsAreListedSeparately(): void
    {
        $r1 = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa1';
        $r2 = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa2';
        $lines = $this->repo()->aggregateSnapshot(
            [
                ['date' => '2026-03-02', 'slotCode' => 'LUNCH', 'recipeId' => $r1],
                ['date' => '2026-03-03', 'slotCode' => 'LUNCH', 'recipeId' => $r2],
            ],
            [
                $r1 => ['ingredients' => [['name' => 'Молоко', 'quantity' => 200, 'unit' => 'мл', 'productCategory' => 'молочные']]],
                $r2 => ['ingredients' => [['name' => 'Молоко', 'quantity' => 1, 'unit' => 'л', 'productCategory' => 'молочные']]],
            ]
        );

        self::assertCount(2, $lines);
        $units = array_column($lines, 'unit');
        sort($units);
        self::assertSame(['л', 'мл'], $units);
    }

    public function testToTasteDoesNotForceQuantity(): void
    {
        $rid = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaa1';
        $lines = $this->repo()->aggregateSnapshot(
            [['date' => '2026-03-02', 'slotCode' => 'BREAKFAST', 'recipeId' => $rid]],
            [
                $rid => ['ingredients' => [['name' => 'Соль', 'quantity' => null, 'unit' => '', 'productCategory' => '']]],
            ]
        );

        self::assertCount(1, $lines);
        self::assertNull($lines[0]['quantity']);
        self::assertNull($lines[0]['unit']);
    }
}
