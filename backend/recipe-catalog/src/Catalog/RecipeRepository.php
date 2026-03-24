<?php

namespace App\Catalog;

use PDO;
use Symfony\Component\Uid\Uuid;

final class RecipeRepository
{
    public function __construct(private readonly Database $database)
    {
    }

    public function createSchema(): void
    {
        $this->database->pdo()->exec(
            'CREATE TABLE IF NOT EXISTS catalog_recipes (
                id UUID PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                title VARCHAR(512) NOT NULL,
                steps JSONB NOT NULL DEFAULT \'[]\',
                cook_time_minutes INT NULL,
                meal_category VARCHAR(128) NULL,
                nutrition JSONB NULL,
                ingredients JSONB NOT NULL,
                source_url TEXT NULL,
                created_at TIMESTAMPTZ NOT NULL,
                updated_at TIMESTAMPTZ NOT NULL
            )'
        );
        $this->database->pdo()->exec('CREATE INDEX IF NOT EXISTS catalog_recipes_user_idx ON catalog_recipes (user_id)');
        $this->database->pdo()->exec('CREATE INDEX IF NOT EXISTS catalog_recipes_user_updated_idx ON catalog_recipes (user_id, updated_at DESC)');
    }

    /**
     * @param array<string, mixed> $data RecipeCreate payload
     *
     * @return array<string, mixed>|null recipe row as API Recipe
     */
    public function create(string $userId, array $data): ?array
    {
        $err = $this->validateCreate($data);
        if ($err !== null) {
            return null;
        }

        $id = Uuid::v4()->toRfc4122();
        $steps = $data['steps'] ?? [];
        $ingredients = $data['ingredients'];
        $nutrition = $data['nutrition'] ?? null;

        $stmt = $this->database->pdo()->prepare(
            'INSERT INTO catalog_recipes (
                id, user_id, title, steps, cook_time_minutes, meal_category, nutrition, ingredients, source_url, created_at, updated_at
            ) VALUES (
                :id, :user_id, :title, CAST(:steps AS JSONB), :cook_time_minutes, :meal_category, CAST(:nutrition AS JSONB), CAST(:ingredients AS JSONB), :source_url, NOW(), NOW()
            )'
        );
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
            'title' => $data['title'],
            'steps' => \json_encode($steps, JSON_THROW_ON_ERROR),
            'cook_time_minutes' => $data['cookTimeMinutes'] ?? null,
            'meal_category' => $data['mealCategory'] ?? null,
            'nutrition' => $nutrition === null ? 'null' : \json_encode($nutrition, JSON_THROW_ON_ERROR),
            'ingredients' => \json_encode($ingredients, JSON_THROW_ON_ERROR),
            'source_url' => $data['sourceUrl'] ?? null,
        ]);

        return $this->findById($userId, $id);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(string $userId, string $recipeId): ?array
    {
        $stmt = $this->database->pdo()->prepare(
            'SELECT id, title, steps, cook_time_minutes, meal_category, nutrition, ingredients, source_url, created_at, updated_at
             FROM catalog_recipes WHERE id = :id AND user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['id' => $recipeId, 'user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->rowToRecipe($row) : null;
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function list(
        string $userId,
        ?string $q,
        ?string $mealCategory,
        ?int $maxCookTimeMinutes,
        int $limit,
        int $offset,
    ): array {
        $where = ['user_id = :user_id'];
        $params = ['user_id' => $userId];
        if ($q !== null && $q !== '') {
            $where[] = 'title ILIKE :q';
            $params['q'] = '%'.$q.'%';
        }
        if ($mealCategory !== null && $mealCategory !== '') {
            $where[] = 'meal_category = :meal';
            $params['meal'] = $mealCategory;
        }
        if ($maxCookTimeMinutes !== null) {
            $where[] = 'cook_time_minutes IS NOT NULL AND cook_time_minutes <= :maxc';
            $params['maxc'] = $maxCookTimeMinutes;
        }
        $sqlWhere = \implode(' AND ', $where);

        $countStmt = $this->database->pdo()->prepare('SELECT COUNT(*) FROM catalog_recipes WHERE '.$sqlWhere);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $listStmt = $this->database->pdo()->prepare(
            'SELECT id, title, cook_time_minutes, meal_category FROM catalog_recipes WHERE '.$sqlWhere.
            ' ORDER BY updated_at DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $k => $v) {
            $listStmt->bindValue(':'.$k, $v);
        }
        $listStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $listStmt->execute();

        $items = [];
        while ($row = $listStmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'cookTimeMinutes' => $row['cook_time_minutes'] !== null ? (int) $row['cook_time_minutes'] : null,
                'mealCategory' => $row['meal_category'],
            ];
        }

        return ['items' => $items, 'total' => $total];
    }

    /**
     * @param array<string, mixed> $patch
     *
     * @return array<string, mixed>|null full recipe or null if not found / invalid
     */
    public function patch(string $userId, string $recipeId, array $patch): ?array
    {
        if ($this->findById($userId, $recipeId) === null) {
            return null;
        }

        $sets = [];
        $params = ['id' => $recipeId, 'user_id' => $userId];

        if (\array_key_exists('title', $patch)) {
            $sets[] = 'title = :title';
            $params['title'] = $patch['title'];
        }
        if (\array_key_exists('steps', $patch)) {
            $sets[] = 'steps = CAST(:steps AS JSONB)';
            $params['steps'] = \json_encode($patch['steps'] ?? [], JSON_THROW_ON_ERROR);
        }
        if (\array_key_exists('cookTimeMinutes', $patch)) {
            $sets[] = 'cook_time_minutes = :cook_time_minutes';
            $params['cook_time_minutes'] = $patch['cookTimeMinutes'];
        }
        if (\array_key_exists('mealCategory', $patch)) {
            $sets[] = 'meal_category = :meal_category';
            $params['meal_category'] = $patch['mealCategory'];
        }
        if (\array_key_exists('nutrition', $patch)) {
            $sets[] = 'nutrition = CAST(:nutrition AS JSONB)';
            $n = $patch['nutrition'];
            $params['nutrition'] = $n === null ? 'null' : \json_encode($n, JSON_THROW_ON_ERROR);
        }
        if (\array_key_exists('ingredients', $patch)) {
            $ing = $patch['ingredients'];
            if (!\is_array($ing)) {
                throw new \InvalidArgumentException('ingredients must be an array.');
            }
            $err = $this->validateIngredients($ing);
            if ($err !== null) {
                throw new \InvalidArgumentException('Invalid ingredients: '.$err);
            }
            $sets[] = 'ingredients = CAST(:ingredients AS JSONB)';
            $params['ingredients'] = \json_encode($ing, JSON_THROW_ON_ERROR);
        }
        if (\array_key_exists('sourceUrl', $patch)) {
            $sets[] = 'source_url = :source_url';
            $params['source_url'] = $patch['sourceUrl'];
        }

        if ($sets === []) {
            return $this->findById($userId, $recipeId);
        }

        $sets[] = 'updated_at = NOW()';
        $sql = 'UPDATE catalog_recipes SET '.\implode(', ', $sets).' WHERE id = :id AND user_id = :user_id';
        $stmt = $this->database->pdo()->prepare($sql);
        $stmt->execute($params);

        return $this->findById($userId, $recipeId);
    }

    public function delete(string $userId, string $recipeId): bool
    {
        $stmt = $this->database->pdo()->prepare('DELETE FROM catalog_recipes WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $recipeId, 'user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateCreate(array $data): ?string
    {
        if (!isset($data['title']) || !\is_string($data['title']) || \trim($data['title']) === '') {
            return 'title';
        }
        if (!isset($data['ingredients']) || !\is_array($data['ingredients']) || $data['ingredients'] === []) {
            return 'ingredients';
        }

        return $this->validateIngredients($data['ingredients']);
    }

    /**
     * @param list<mixed> $ingredients
     */
    private function validateIngredients(array $ingredients): ?string
    {
        foreach ($ingredients as $ing) {
            if (!\is_array($ing)) {
                return 'ingredient';
            }
            if (!isset($ing['name']) || !\is_string($ing['name']) || $ing['name'] === '') {
                return 'ingredient.name';
            }
            if (!isset($ing['productCategory']) || !\is_string($ing['productCategory']) || $ing['productCategory'] === '') {
                return 'ingredient.productCategory';
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function rowToRecipe(array $row): array
    {
        $steps = \json_decode((string) $row['steps'], true, 512, JSON_THROW_ON_ERROR);
        $ingredients = \json_decode((string) $row['ingredients'], true, 512, JSON_THROW_ON_ERROR);
        $nutritionRaw = $row['nutrition'];
        $nutrition = null;
        if ($nutritionRaw !== null && $nutritionRaw !== '') {
            $nutrition = \json_decode((string) $nutritionRaw, true, 512, JSON_THROW_ON_ERROR);
        }

        $created = $row['created_at'] instanceof \DateTimeInterface
            ? $row['created_at']->format(DATE_ATOM)
            : (string) $row['created_at'];
        $updated = $row['updated_at'] instanceof \DateTimeInterface
            ? $row['updated_at']->format(DATE_ATOM)
            : (string) $row['updated_at'];

        return [
            'id' => $row['id'],
            'title' => $row['title'],
            'steps' => $steps,
            'cookTimeMinutes' => $row['cook_time_minutes'] !== null ? (int) $row['cook_time_minutes'] : null,
            'mealCategory' => $row['meal_category'],
            'nutrition' => $nutrition,
            'ingredients' => $ingredients,
            'sourceUrl' => $row['source_url'],
            'createdAt' => $created,
            'updatedAt' => $updated,
        ];
    }
}
