import type { BffErrorBody } from './bff-types';

/**
 * Ошибка ответа BFF: JSON `{ code, message, details? }` либо сырой текст/HTML.
 * Соответствует `components/schemas/ErrorBody` в `contracts/openapi/bff.openapi.yaml`.
 */
export class BffHttpError extends Error {
  readonly status: number;
  readonly code?: string;
  readonly details?: Record<string, unknown>;
  /** Исходное тело ответа (для отладки). */
  readonly rawBody: string;

  constructor(
    status: number,
    message: string,
    opts?: { code?: string; details?: Record<string, unknown>; rawBody?: string },
  ) {
    super(message);
    this.name = 'BffHttpError';
    this.status = status;
    this.code = opts?.code;
    this.details = opts?.details;
    this.rawBody = opts?.rawBody ?? '';
  }
}

export function isBffHttpError(e: unknown): e is BffHttpError {
  return e instanceof BffHttpError;
}

/** Разбор тела ошибки BFF из текста ответа. */
export function parseBffErrorBody(text: string): BffErrorBody | null {
  const t = text.trim();
  if (!t) {
    return null;
  }
  try {
    const j = JSON.parse(t) as unknown;
    if (!j || typeof j !== 'object') {
      return null;
    }
    const o = j as Record<string, unknown>;
    if (typeof o.code !== 'string' || typeof o.message !== 'string') {
      return null;
    }
    let details: Record<string, unknown> | undefined;
    if (o.details !== undefined && o.details !== null && typeof o.details === 'object') {
      details = o.details as Record<string, unknown>;
    }
    return { code: o.code, message: o.message, details };
  } catch {
    return null;
  }
}

/** Собрать {@link BffHttpError} из уже прочитанного `Response` (один раз читает `res.text()`). */
export async function bffErrorFromResponse(res: Response): Promise<BffHttpError> {
  let raw = '';
  try {
    raw = await res.text();
  } catch {
    raw = '';
  }
  const parsed = parseBffErrorBody(raw);
  if (parsed) {
    return new BffHttpError(res.status, parsed.message, {
      code: parsed.code,
      details: parsed.details,
      rawBody: raw,
    });
  }
  return new BffHttpError(res.status, raw || res.statusText, { rawBody: raw });
}

/** Если `!res.ok`, выбросить {@link BffHttpError}. Иначе — ничего. */
export async function throwIfBffNotOk(res: Response): Promise<void> {
  if (!res.ok) {
    throw await bffErrorFromResponse(res);
  }
}

/** Текст для UI из любого исключения после вызовов BFF. */
export function bffErrorMessage(e: unknown): string {
  if (isBffHttpError(e)) {
    return e.message;
  }
  if (e instanceof Error) {
    return e.message;
  }
  return 'Ошибка';
}
