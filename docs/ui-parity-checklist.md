# UI parity checklist (code vs Figma)

Цель: зафиксировать текущий статус приведения реализованного UI к макетам Figma и согласованным токенам из `technical-spec-figma.md`.

## Область проверки

- Frontend shell: `frontend/apps/host`
- MFE рецептов: `frontend/apps/mf-recipes`
- MFE планировщика: `frontend/apps/mf-planner`
- MFE покупок: `frontend/apps/mf-shopping`

## Базовые критерии parity

- Mobile-first: базовые стили для mobile, затем брейкпоинты `768` и `1200`.
- Токены: цвета/радиусы/spacing/типографика только из `@meal/ui-tokens`.
- Touch targets: интерактивные controls не меньше `44px`.
- Состояния: loading/error/empty визуально согласованы и читаемы.
- Навигация и business-flow не ломаются при визуальных изменениях.

## Статус по модулям

### `host`

- Статус: **готово (этап 1)**.
- Выполнено:
  - shell/topbar и навигация переведены на mobile-first;
  - главная (`HomeView`) приведена к карточным паттернам Figma;
  - брейкпоинты `768/1200` и token-based стили применены.
- Риски:
  - формы auth (`LoginView`/`RegisterView`) не проходили отдельный parity-pass.

### `mf-recipes`

- Статус: **готово (основной сценарий)**.
- Выполнено:
  - `Library`, `RecipeDetail`, `ImportView`, `RecipeForm` приведены к mobile-first;
  - кнопки/поля/карточки/модалки переведены на токены и единый визуальный язык;
  - сетки карточек и форм адаптированы под `768/1200`.
- Риски:
  - точечная пиксельная сверка с конкретными node-id Figma ещё не зафиксирована.

### `mf-planner`

- Статус: **готово (основной экран)**.
- Выполнено:
  - `PlannerPage` переведён на mobile-first;
  - панель периода, sidebar и недельный layout адаптированы для `768/1200`;
  - token-based кнопки/инпуты/overlay и focus state.
- Риски:
  - нужен отдельный UX-pass по dense-состояниям (много рецептов в слотах, длинные строки).

### `mf-shopping`

- Статус: **готово (основной экран)**.
- Выполнено:
  - `ShoppingPage` переведён на mobile-first;
  - группы, строки, toolbar и ручной ввод выровнены по токенам;
  - адаптивная компоновка на `768/1200`.
- Риски:
  - полезно добавить визуальный smoke для очень длинных названий/merge-note.

## Минимальный regression checklist

- `host`: переходы `/recipes`, `/planner`, logout/login/register.
- `mf-recipes`: поиск, действия карточки, модалка `В план`, переходы в detail/edit/import.
- `mf-planner`: смена недели, открытие календаря, добавление рецепта в слот, build shopping list.
- `mf-shopping`: toggle purchased, удаление строки, manual add, copy/export.

## Что дальше

1. Сделать финальный parity-pass по auth-экранам в `host`.
2. Добавить e2e-скриншотные проверки на 3 ширинах: mobile/tablet/desktop.
3. При значимых правках Figma повторно сверять с `technical-spec-figma.md` и обновлять этот чеклист.
