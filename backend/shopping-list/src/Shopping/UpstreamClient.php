<?php

namespace App\Shopping;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class UpstreamClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $internalAuthToken,
        private readonly string $planningBaseUri,
        private readonly string $catalogBaseUri,
    ) {
    }

    /**
     * @return list<array{date: string, slotCode: string, recipeId: string}>
     */
    public function fetchAssignments(Request $incoming, string $userId, string $from, string $to): array
    {
        $url = \rtrim($this->planningBaseUri, '/').'/assignments/plan?'.\http_build_query(['from' => $from, 'to' => $to]);
        try {
            $resp = $this->httpClient->request('GET', $url, ['headers' => $this->headers($incoming, $userId)]);
            $status = $resp->getStatusCode();
            if ($status !== 200) {
                throw new UpstreamUnavailableException('meal-planning HTTP '.$status);
            }
            /** @var array{items?: list<array<string, mixed>>} $data */
            $data = $resp->toArray(false);
        } catch (UpstreamUnavailableException $e) {
            throw $e;
        } catch (TransportExceptionInterface $e) {
            throw new UpstreamUnavailableException('meal-planning unreachable', 0, $e);
        }

        $out = [];
        foreach ($data['items'] ?? [] as $row) {
            if (!isset($row['date'], $row['slotCode'], $row['recipeId']) || !\is_string($row['recipeId'])) {
                continue;
            }
            $out[] = [
                'date' => (string) $row['date'],
                'slotCode' => (string) $row['slotCode'],
                'recipeId' => (string) $row['recipeId'],
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchRecipe(Request $incoming, string $userId, string $recipeId): ?array
    {
        $url = \rtrim($this->catalogBaseUri, '/').'/recipes/'.\rawurlencode($recipeId);
        try {
            $resp = $this->httpClient->request('GET', $url, ['headers' => $this->headers($incoming, $userId)]);
            $status = $resp->getStatusCode();
            if ($status === 404) {
                return null;
            }
            if ($status !== 200) {
                throw new UpstreamUnavailableException('recipe-catalog HTTP '.$status);
            }

            return $resp->toArray(false);
        } catch (UpstreamUnavailableException $e) {
            throw $e;
        } catch (TransportExceptionInterface $e) {
            throw new UpstreamUnavailableException('recipe-catalog unreachable', 0, $e);
        }
    }

    /**
     * @return array<string, string>
     */
    private function headers(Request $incoming, string $userId): array
    {
        $h = [
            'X-Internal-Auth' => $this->internalAuthToken,
            'X-User-Id' => $userId,
        ];
        $cid = $incoming->headers->get('X-Correlation-Id');
        if (\is_string($cid) && $cid !== '') {
            $h['X-Correlation-Id'] = $cid;
        }

        return $h;
    }
}
