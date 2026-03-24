<?php

namespace App\Shopping;

use PDO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;

final class ShoppingListRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    public function createSchema(): void
    {
        $pdo = $this->database->pdo();
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS shopping_lists (
                id UUID PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                period_from DATE NOT NULL,
                period_to DATE NOT NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                UNIQUE (user_id, period_from, period_to)
            )'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS shopping_lines (
                id UUID PRIMARY KEY,
                list_id UUID NOT NULL REFERENCES shopping_lists(id) ON DELETE CASCADE,
                display_name VARCHAR(512) NOT NULL,
                quantity DOUBLE PRECISION NULL,
                unit VARCHAR(64) NULL,
                product_category VARCHAR(128) NULL,
                purchased BOOLEAN NOT NULL DEFAULT FALSE,
                merge_note TEXT NULL,
                source_recipe_ids JSONB NOT NULL DEFAULT \'[]\',
                is_manual BOOLEAN NOT NULL DEFAULT FALSE
            )'
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS shopping_lines_list_idx ON shopping_lines (list_id)');
    }

    public function deleteListForPeriod(string $userId, string $from, string $to): bool
    {
        $stmt = $this->database->pdo()->prepare(
            'DELETE FROM shopping_lists WHERE user_id = :u AND period_from = :f::date AND period_to = :t::date'
        );
        $stmt->execute(['u' => $userId, 'f' => $from, 't' => $to]);

        return $stmt->rowCount() > 0;
    }

    public function insertList(string $userId, string $from, string $to): string
    {
        $id = Uuid::v4()->toRfc4122();
        $stmt = $this->database->pdo()->prepare(
            'INSERT INTO shopping_lists (id, user_id, period_from, period_to) VALUES (:id, :u, :f::date, :t::date)'
        );
        $stmt->execute(['id' => $id, 'u' => $userId, 'f' => $from, 't' => $to]);

        return $id;
    }

    /**
     * @param list<array{displayName: string, quantity?: float|null, unit?: string|null, productCategory?: string|null, sourceRecipeIds: list<string>, mergeNote?: string|null}> $lines
     */
    public function insertSnapshotLines(string $listId, array $lines): void
    {
        $pdo = $this->database->pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO shopping_lines (id, list_id, display_name, quantity, unit, product_category, purchased, merge_note, source_recipe_ids, is_manual)
             VALUES (:id, :list, :dn, :q, :unit, :pc, FALSE, :mn, CAST(:src AS JSONB), FALSE)'
        );
        foreach ($lines as $line) {
            $stmt->execute([
                'id' => Uuid::v4()->toRfc4122(),
                'list' => $listId,
                'dn' => $line['displayName'],
                'q' => $line['quantity'] ?? null,
                'unit' => $line['unit'] ?? null,
                'pc' => $line['productCategory'] ?? null,
                'mn' => $line['mergeNote'] ?? null,
                'src' => \json_encode($line['sourceRecipeIds'], JSON_THROW_ON_ERROR),
            ]);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getListDetail(string $userId, string $listId): ?array
    {
        $stmt = $this->database->pdo()->prepare(
            'SELECT id, period_from::text, period_to::text FROM shopping_lists WHERE id = :id AND user_id = :u LIMIT 1'
        );
        $stmt->execute(['id' => $listId, 'u' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $linesStmt = $this->database->pdo()->prepare(
            'SELECT id, display_name, quantity, unit, product_category, purchased, merge_note, source_recipe_ids::text, is_manual
             FROM shopping_lines WHERE list_id = :l ORDER BY display_name'
        );
        $linesStmt->execute(['l' => $listId]);
        $lines = [];
        while ($l = $linesStmt->fetch(PDO::FETCH_ASSOC)) {
            /** @var list<string> $src */
            $src = \json_decode((string) $l['source_recipe_ids'], true, 512, JSON_THROW_ON_ERROR);
            $lines[] = [
                'lineId' => $l['id'],
                'displayName' => $l['display_name'],
                'quantity' => $l['quantity'] !== null ? (float) $l['quantity'] : null,
                'unit' => $l['unit'],
                'productCategory' => $l['product_category'],
                'purchased' => (bool) $l['purchased'],
                'mergeNote' => $l['merge_note'],
                'sourceRecipeIds' => $src,
            ];
        }

        return [
            'listId' => $row['id'],
            'from' => $row['period_from'],
            'to' => $row['period_to'],
            'lines' => $lines,
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>|null line
     */
    public function addManualLine(string $userId, string $listId, array $data): ?array
    {
        if (!$this->listBelongsToUser($listId, $userId)) {
            return null;
        }
        $name = isset($data['displayName']) && \is_string($data['displayName']) ? $data['displayName'] : '';
        if ($name === '') {
            return null;
        }
        $id = Uuid::v4()->toRfc4122();
        $stmt = $this->database->pdo()->prepare(
            'INSERT INTO shopping_lines (id, list_id, display_name, quantity, unit, product_category, purchased, merge_note, source_recipe_ids, is_manual)
             VALUES (:id, :l, :dn, :q, :u, :pc, FALSE, NULL, \'[]\', TRUE)'
        );
        $stmt->execute([
            'id' => $id,
            'l' => $listId,
            'dn' => $name,
            'q' => $data['quantity'] ?? null,
            'u' => $data['unit'] ?? null,
            'pc' => $data['productCategory'] ?? null,
        ]);

        return $this->getLine($listId, $id);
    }

    /**
     * @param array<string, mixed> $patch
     *
     * @return array<string, mixed>|null
     */
    public function patchLine(string $userId, string $listId, string $lineId, array $patch): ?array
    {
        if (!$this->listBelongsToUser($listId, $userId)) {
            return null;
        }
        if ($this->getLine($listId, $lineId) === null) {
            return null;
        }

        $sets = [];
        $params = ['lid' => $lineId, 'list' => $listId];
        if (\array_key_exists('displayName', $patch)) {
            $sets[] = 'display_name = :dn';
            $params['dn'] = $patch['displayName'];
        }
        if (\array_key_exists('quantity', $patch)) {
            $sets[] = 'quantity = :q';
            $params['q'] = $patch['quantity'];
        }
        if (\array_key_exists('unit', $patch)) {
            $sets[] = 'unit = :u';
            $params['u'] = $patch['unit'];
        }
        if (\array_key_exists('productCategory', $patch)) {
            $sets[] = 'product_category = :pc';
            $params['pc'] = $patch['productCategory'];
        }
        if (\array_key_exists('purchased', $patch)) {
            $sets[] = 'purchased = :p';
            $params['p'] = (bool) $patch['purchased'];
        }
        if ($sets === []) {
            return $this->getLine($listId, $lineId);
        }
        $sql = 'UPDATE shopping_lines SET '.\implode(', ', $sets).' WHERE id = :lid AND list_id = :list';
        $stmt = $this->database->pdo()->prepare($sql);
        $stmt->execute($params);

        return $this->getLine($listId, $lineId);
    }

    public function deleteLine(string $userId, string $listId, string $lineId): bool
    {
        if (!$this->listBelongsToUser($listId, $userId)) {
            return false;
        }
        $stmt = $this->database->pdo()->prepare('DELETE FROM shopping_lines WHERE id = :id AND list_id = :l');
        $stmt->execute(['id' => $lineId, 'l' => $listId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @param list<array{date: string, slotCode: string, recipeId: string}> $assignments
     * @param array<string, array<string, mixed>>                        $recipesById
     *
     * @return list<array{displayName: string, quantity?: float|null, unit?: string|null, productCategory?: string|null, sourceRecipeIds: list<string>, mergeNote?: string|null}>
     */
    public function aggregateSnapshot(array $assignments, array $recipesById): array
    {
        /** @var array<string, array{displayName: string, quantity: float|null, unit: ?string, productCategory: ?string, sourceRecipeIds: list<string>, mergeNote: ?string}> $acc */
        $acc = [];

        foreach ($assignments as $a) {
            $rid = $a['recipeId'];
            $recipe = $recipesById[$rid] ?? null;
            if ($recipe === null || !isset($recipe['ingredients']) || !\is_array($recipe['ingredients'])) {
                continue;
            }
            foreach ($recipe['ingredients'] as $ing) {
                if (!\is_array($ing) || !isset($ing['name']) || !\is_string($ing['name'])) {
                    continue;
                }
                $name = $ing['name'];
                $unit = isset($ing['unit']) && \is_string($ing['unit']) ? $ing['unit'] : '';
                $cat = isset($ing['productCategory']) && \is_string($ing['productCategory']) ? $ing['productCategory'] : '';
                $q = null;
                if (\array_key_exists('quantity', $ing) && $ing['quantity'] !== null && \is_numeric($ing['quantity'])) {
                    $q = (float) $ing['quantity'];
                }
                $key = \mb_strtolower(\trim($name))."\n".$unit."\n".$cat;

                if (!isset($acc[$key])) {
                    $acc[$key] = [
                        'displayName' => $name,
                        'quantity' => $q,
                        'unit' => $unit !== '' ? $unit : null,
                        'productCategory' => $cat !== '' ? $cat : null,
                        'sourceRecipeIds' => [$rid],
                        'mergeNote' => null,
                    ];
                    continue;
                }

                $cur = &$acc[$key];
                $curUnit = $cur['unit'] ?? '';
                $newUnit = $unit !== '' ? $unit : '';
                if ($curUnit === $newUnit && $this->numericQty($cur['quantity']) && $this->numericQty($q)) {
                    $cur['quantity'] = ($cur['quantity'] ?? 0) + ($q ?? 0);
                } elseif ($curUnit !== $newUnit && ($curUnit !== '' || $newUnit !== '')) {
                    $note = 'Разные единицы для «'.$name.'».';
                    $cur['mergeNote'] = $cur['mergeNote'] ? $cur['mergeNote'].' '.$note : $note;
                }
                if (!\in_array($rid, $cur['sourceRecipeIds'], true)) {
                    $cur['sourceRecipeIds'][] = $rid;
                }
                unset($cur);
            }
        }

        $out = [];
        foreach ($acc as $row) {
            $out[] = [
                'displayName' => $row['displayName'],
                'quantity' => $row['quantity'],
                'unit' => $row['unit'],
                'productCategory' => $row['productCategory'],
                'sourceRecipeIds' => $row['sourceRecipeIds'],
                'mergeNote' => $row['mergeNote'],
            ];
        }

        return $out;
    }

    /**
     * @return array{listId: string, from: string, to: string, replaced: bool, empty: bool}
     */
    public function build(string $userId, string $from, string $to, UpstreamClient $up, Request $req): array
    {
        $replaced = $this->deleteListForPeriod($userId, $from, $to);
        $assignments = $up->fetchAssignments($req, $userId, $from, $to);
        $recipes = [];
        $seen = [];
        foreach ($assignments as $a) {
            $rid = $a['recipeId'];
            if (isset($seen[$rid])) {
                continue;
            }
            $seen[$rid] = true;
            $r = $up->fetchRecipe($req, $userId, $rid);
            if ($r !== null) {
                $recipes[$rid] = $r;
            }
        }

        $lines = $assignments === [] ? [] : $this->aggregateSnapshot($assignments, $recipes);
        $listId = $this->insertList($userId, $from, $to);
        if ($lines !== []) {
            $this->insertSnapshotLines($listId, $lines);
        }

        return [
            'listId' => $listId,
            'from' => $from,
            'to' => $to,
            'replaced' => $replaced,
            'empty' => $assignments === [],
        ];
    }

    private function numericQty(mixed $q): bool
    {
        return $q !== null && \is_numeric($q);
    }

    private function listBelongsToUser(string $listId, string $userId): bool
    {
        $stmt = $this->database->pdo()->prepare('SELECT 1 FROM shopping_lists WHERE id = :id AND user_id = :u LIMIT 1');
        $stmt->execute(['id' => $listId, 'u' => $userId]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getLine(string $listId, string $lineId): ?array
    {
        $stmt = $this->database->pdo()->prepare(
            'SELECT id, display_name, quantity, unit, product_category, purchased, merge_note, source_recipe_ids::text
             FROM shopping_lines WHERE list_id = :l AND id = :id LIMIT 1'
        );
        $stmt->execute(['l' => $listId, 'id' => $lineId]);
        $l = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$l) {
            return null;
        }
        /** @var list<string> $src */
        $src = \json_decode((string) $l['source_recipe_ids'], true, 512, JSON_THROW_ON_ERROR);

        return [
            'lineId' => $l['id'],
            'displayName' => $l['display_name'],
            'quantity' => $l['quantity'] !== null ? (float) $l['quantity'] : null,
            'unit' => $l['unit'],
            'productCategory' => $l['product_category'],
            'purchased' => (bool) $l['purchased'],
            'mergeNote' => $l['merge_note'],
            'sourceRecipeIds' => $src,
        ];
    }
}
